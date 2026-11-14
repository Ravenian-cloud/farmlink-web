<?php

namespace App\Models;

use App\Core\Model;
use Throwable;

class Bid extends Model
{
    protected string $table = 'bids';

    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
                (listing_id, buyer_id, seller_id, offered_unit_price, requested_qty, buyer_note, status)
                VALUES
                (:listing_id, :buyer_id, :seller_id, :offered_unit_price, :requested_qty, :buyer_note, 'pending')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'listing_id'         => (int) $data['listing_id'],
            'buyer_id'           => (int) $data['buyer_id'],
            'seller_id'          => (int) $data['seller_id'],
            'offered_unit_price' => (float) $data['offered_unit_price'],
            'requested_qty'      => (float) $data['requested_qty'],
            'buyer_note'         => !empty($data['buyer_note']) ? trim($data['buyer_note']) : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getForUser(int $userId): array
    {
        $sql = "SELECT b.*, l.title AS listing_title, l.unit, l.unit_price AS asking_price,
                       buyer.full_name AS buyer_name, buyer.county AS buyer_county,
                       seller.full_name AS seller_name
                FROM bids b
                INNER JOIN listings l ON b.listing_id = l.id
                INNER JOIN users buyer ON b.buyer_id = buyer.id
                INNER JOIN users seller ON b.seller_id = seller.id
                WHERE b.buyer_id = :uid_buyer OR b.seller_id = :uid_seller
                ORDER BY b.updated_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid_buyer' => $userId, 'uid_seller' => $userId]);
        return $stmt->fetchAll();
    }

    public function getThreadForListing(int $listingId, int $userId): array
    {
        $sql = "SELECT b.*, buyer.full_name AS buyer_name, seller.full_name AS seller_name, l.unit
                FROM bids b
                INNER JOIN users buyer ON b.buyer_id = buyer.id
                INNER JOIN users seller ON b.seller_id = seller.id
                INNER JOIN listings l ON b.listing_id = l.id
                WHERE b.listing_id = :listing_id
                  AND (b.buyer_id = :uid_buyer OR b.seller_id = :uid_seller)
                ORDER BY b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'listing_id' => $listingId,
            'uid_buyer'  => $userId,
            'uid_seller' => $userId,
        ]);
        return $stmt->fetchAll();
    }

    public function counterOffer(int $bidId, float $counterPrice, ?string $sellerNote): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET counter_unit_price = :counter_price,
                 seller_note = :seller_note,
                 status = 'countered'
             WHERE id = :id"
        );
        return $stmt->execute([
            'counter_price' => $counterPrice,
            'seller_note'   => $sellerNote ? trim($sellerNote) : null,
            'id'            => $bidId,
        ]);
    }

    public function reject(int $bidId, ?string $note = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'rejected',
                 seller_note = COALESCE(:note, seller_note)
             WHERE id = :id"
        );
        return $stmt->execute(['note' => $note, 'id' => $bidId]);
    }

    /**
     * Atomically accepts a bid and creates an awaiting_payment order at the agreed price.
     */
    public function acceptAndCreateOrder(int $bidId, string $deliveryCounty, string $deliveryAddress): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT b.*, l.quantity_available, l.status AS listing_status
                 FROM bids b
                 INNER JOIN listings l ON b.listing_id = l.id
                 WHERE b.id = :id FOR UPDATE"
            );
            $stmt->execute(['id' => $bidId]);
            $bid = $stmt->fetch();

            if (!$bid || !in_array($bid['status'], ['pending', 'countered'], true)) {
                throw new \RuntimeException('Bid is no longer open for acceptance.');
            }

            if ((float) $bid['quantity_available'] < (float) $bid['requested_qty']) {
                throw new \RuntimeException('Insufficient listing stock to fulfill this bid.');
            }

            // Use counter price if seller countered and buyer accepted; otherwise use buyer's offered price
            $agreedPrice = ($bid['status'] === 'countered' && $bid['counter_unit_price'] !== null)
                ? (float) $bid['counter_unit_price']
                : (float) $bid['offered_unit_price'];

            $qty = (float) $bid['requested_qty'];
            $totalAmount = round($agreedPrice * $qty, 2);

            $updateBid = $this->db->prepare("UPDATE bids SET status = 'accepted' WHERE id = :id");
            $updateBid->execute(['id' => $bidId]);

            $orderRef = 'FL-' . strtoupper(bin2hex(random_bytes(4)));

            $insertOrder = $this->db->prepare(
                "INSERT INTO orders
                 (order_reference, listing_id, bid_id, buyer_id, seller_id, agreed_unit_price, quantity, total_amount, delivery_county, delivery_address, status)
                 VALUES
                 (:order_reference, :listing_id, :bid_id, :buyer_id, :seller_id, :agreed_unit_price, :quantity, :total_amount, :delivery_county, :delivery_address, 'awaiting_payment')"
            );
            $insertOrder->execute([
                'order_reference'   => $orderRef,
                'listing_id'        => (int) $bid['listing_id'],
                'bid_id'            => $bidId,
                'buyer_id'          => (int) $bid['buyer_id'],
                'seller_id'         => (int) $bid['seller_id'],
                'agreed_unit_price' => $agreedPrice,
                'quantity'          => $qty,
                'total_amount'      => $totalAmount,
                'delivery_county'   => trim($deliveryCounty),
                'delivery_address'  => trim($deliveryAddress),
            ]);

            $orderId = (int) $this->db->lastInsertId();

            $this->db->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
