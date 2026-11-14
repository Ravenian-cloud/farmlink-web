<?php

namespace App\Models;

use App\Core\Model;

class Alert extends Model
{
    protected string $table = 'alerts';

    public function getActiveByCounty(string $county): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE (county = :county OR county = 'All')
                  AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY FIELD(severity, 'critical', 'warning', 'info'), created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['county' => trim($county)]);
        return $stmt->fetchAll();
    }

    public function getAllActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM {$this->table}
             WHERE expires_at IS NULL OR expires_at > NOW()
             ORDER BY created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (county, alert_type, severity, title, message, expires_at)
             VALUES (:county, :alert_type, :severity, :title, :message, :expires_at)"
        );
        $stmt->execute([
            'county'     => trim($data['county']),
            'alert_type' => $data['alert_type'],
            'severity'   => $data['severity'],
            'title'      => trim($data['title']),
            'message'    => trim($data['message']),
            'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
