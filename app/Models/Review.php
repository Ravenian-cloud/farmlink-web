<?php

namespace App\Models;

use App\Core\Model;
use Throwable;

class Review extends Model
{
    protected string $table = 'reviews';

    public function existsForOrder(int $orderId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE order_id = :order_id LIMIT 1");
        $stmt->execute(['order_id' => $orderId]);
        return (bool) $stmt->fetch();
    }

    public function createAndRecalculate(int $orderId, int $reviewerId, int $revieweeId, int $rating, ?string $comment): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (order_id, reviewer_id, reviewee_id, rating, comment)
                 VALUES (:order_id, :reviewer_id, :reviewee_id, :rating, :comment)"
            );
            $stmt->execute([
                'order_id'    => $orderId,
                'reviewer_id' => $reviewerId,
                'reviewee_id' => $revieweeId,
                'rating'      => max(1, min(5, $rating)),
                'comment'     => $comment ? trim($comment) : null,
            ]);

            $agg = $this->db->prepare(
                "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
                 FROM {$this->table} WHERE reviewee_id = :reviewee_id"
            );
            $agg->execute(['reviewee_id' => $revieweeId]);
            $stats = $agg->fetch();

            $upd = $this->db->prepare(
                "UPDATE users SET rating_avg = :avg, rating_count = :cnt WHERE id = :id"
            );
            $upd->execute([
                'avg' => round((float) $stats['avg_rating'], 2),
                'cnt' => (int) $stats['total_reviews'],
                'id'  => $revieweeId,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
