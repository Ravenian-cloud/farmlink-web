<?php

namespace App\Models;

use App\Core\Model;

class EscrowLedger extends Model
{
    protected string $table = 'escrow_ledger';

    public function findByOrder(int $orderId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE order_id = :order_id LIMIT 1");
        $stmt->execute(['order_id' => $orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
