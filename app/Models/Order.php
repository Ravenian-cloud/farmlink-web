<?php

namespace App\Models;

use App\Core\Model;

class Order extends Model
{
    protected string $table = 'orders';

    public function createDirect(array $data): int
    {
        $orderRef = 'FL-' . strtoupper(bin2hex(random_bytes(4)));
        $totalAmount = round((float) $data['agreed_unit_price'] * (float) $data['quantity'], 2);

        $sql = "INSERT INTO {$this->table}
                (order_reference, listing_id, bid_id, buyer_id, seller_id, agreed_unit_price, quantity, total_amount, delivery_county, delivery_address, status)
                VALUES
                (:order_reference, :listing_id, NULL, :buyer_id, :seller_id, :agreed_unit_price, :quantity, :total_amount, :delivery_county, :delivery_address, 'awaiting_payment')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_reference'   => $orderRef,
            'listing_id'        => (int) $data['listing_id'],
            'buyer_id'          => (int) $data['buyer_id'],
            'seller_id'         => (int) $data['seller_id'],
            'agreed_unit_price' => (float) $data['agreed_unit_price'],
            'quantity'          => (float) $data['quantity'],
            'total_amount'      => $totalAmount,
            'delivery_county'   => trim($data['delivery_county']),
            'delivery_address'  => trim($data['delivery_address']),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findDetailed(int $id): ?array
    {
        $sql = "SELECT o.*, l.title AS listing_title, l.unit, l.image_path,
                       buyer.full_name AS buyer_name, buyer.phone AS buyer_phone,
                       seller.full_name AS seller_name, seller.phone AS seller_phone,
                       e.gross_amount, e.commission_fee, e.net_seller_payout,
                       e.mpesa_receipt_number, e.status AS escrow_status, e.held_at, e.released_at
                FROM orders o
                INNER JOIN listings l ON o.listing_id = l.id
                INNER JOIN users buyer ON o.buyer_id = buyer.id
                INNER JOIN users seller ON o.seller_id = seller.id
                LEFT JOIN escrow_ledger e ON e.order_id = o.id
                WHERE o.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getForUser(int $userId): array
    {
        $sql = "SELECT o.*, l.title AS listing_title, l.unit,
                       buyer.full_name AS buyer_name, seller.full_name AS seller_name,
                       e.status AS escrow_status
                FROM orders o
                INNER JOIN listings l ON o.listing_id = l.id
                INNER JOIN users buyer ON o.buyer_id = buyer.id
                INNER JOIN users seller ON o.seller_id = seller.id
                LEFT JOIN escrow_ledger e ON e.order_id = o.id
                WHERE o.buyer_id = :uid_b OR o.seller_id = :uid_s
                ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid_b' => $userId, 'uid_s' => $userId]);
        return $stmt->fetchAll();
    }

    public function assignLogistics(int $orderId, string $provider, string $contact): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET logistics_provider = :provider,
                 logistics_contact = :contact,
                 status = 'in_transit'
             WHERE id = :id AND status = 'in_escrow'"
        );
        return $stmt->execute([
            'provider' => trim($provider),
            'contact'  => trim($contact),
            'id'       => $orderId,
        ]);
    }
}
