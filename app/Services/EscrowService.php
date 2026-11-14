<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

class EscrowService
{
    private PDO $db;
    private float $commissionRate;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $cfg = require dirname(__DIR__) . '/Config/app.php';
        $this->commissionRate = (float) ($cfg['commission_rate'] ?? 0.05);
    }

    /**
     * Locks confirmed payment into escrow and decrements listing inventory.
     */
    public function lockFunds(int $orderId, string $mpesaReceipt): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new RuntimeException('Order not found during escrow lock.');
            }

            if ($order['status'] !== 'awaiting_payment') {
                // Idempotency guard against duplicate M-Pesa callbacks
                $this->db->commit();
                return;
            }

            $gross = (float) $order['total_amount'];
            $commission = round($gross * $this->commissionRate, 2);
            $netPayout = round($gross - $commission, 2);

            $insEscrow = $this->db->prepare(
                "INSERT INTO escrow_ledger
                 (order_id, gross_amount, commission_fee, net_seller_payout, mpesa_receipt_number, status)
                 VALUES
                 (:order_id, :gross, :commission, :net_payout, :receipt, 'held')"
            );
            $insEscrow->execute([
                'order_id'   => $orderId,
                'gross'      => $gross,
                'commission' => $commission,
                'net_payout' => $netPayout,
                'receipt'    => $mpesaReceipt,
            ]);

            $updOrder = $this->db->prepare("UPDATE orders SET status = 'in_escrow' WHERE id = :id");
            $updOrder->execute(['id' => $orderId]);

            $updStock = $this->db->prepare(
                "UPDATE listings
                 SET quantity_available = GREATEST(0, quantity_available - :qty),
                     status = IF(quantity_available - :qty_check <= 0, 'sold_out', 'active')
                 WHERE id = :listing_id"
            );
            $updStock->execute([
                'qty'        => (float) $order['quantity'],
                'qty_check'  => (float) $order['quantity'],
                'listing_id' => (int) $order['listing_id'],
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Releases held escrow funds to the seller upon buyer delivery confirmation.
     */
    public function releaseFunds(int $orderId, int $buyerId): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT o.*, e.id AS escrow_id, e.status AS escrow_status
                 FROM orders o
                 INNER JOIN escrow_ledger e ON e.order_id = o.id
                 WHERE o.id = :id FOR UPDATE"
            );
            $stmt->execute(['id' => $orderId]);
            $record = $stmt->fetch();

            if (!$record || (int) $record['buyer_id'] !== $buyerId) {
                throw new RuntimeException('Unauthorized or invalid order for escrow release.');
            }

            if ($record['escrow_status'] !== 'held') {
                throw new RuntimeException('Escrow funds are not in a releasable state.');
            }

            $payoutRef = 'PAY-' . strtoupper(bin2hex(random_bytes(5)));

            $updEscrow = $this->db->prepare(
                "UPDATE escrow_ledger
                 SET status = 'released',
                     payout_reference = :payout_ref,
                     released_at = NOW()
                 WHERE id = :escrow_id"
            );
            $updEscrow->execute([
                'payout_ref' => $payoutRef,
                'escrow_id'  => (int) $record['escrow_id'],
            ]);

            $updOrder = $this->db->prepare(
                "UPDATE orders SET status = 'delivered', delivered_at = NOW() WHERE id = :id"
            );
            $updOrder->execute(['id' => $orderId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
