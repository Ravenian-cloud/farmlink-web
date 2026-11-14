<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $normalized = self::normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE phone = :phone LIMIT 1");
        $stmt->execute(['phone' => $normalized]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (full_name, email, phone, password_hash, role, county, sub_county, latitude, longitude, preferred_lang)
                VALUES 
                (:full_name, :email, :phone, :password_hash, :role, :county, :sub_county, :latitude, :longitude, :preferred_lang)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'full_name'      => trim($data['full_name']),
            'email'          => strtolower(trim($data['email'])),
            'phone'          => self::normalizePhone($data['phone']),
            'password_hash'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'           => $data['role'],
            'county'         => trim($data['county']),
            'sub_county'     => !empty($data['sub_county']) ? trim($data['sub_county']) : null,
            'latitude'       => isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            'longitude'      => isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            'preferred_lang' => $data['preferred_lang'] ?? 'en',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function submitKycDocument(int $userId, string $documentPath): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} 
             SET kyc_document_path = :path, kyc_status = 'pending' 
             WHERE id = :id"
        );
        return $stmt->execute([
            'path' => $documentPath,
            'id'   => $userId,
        ]);
    }

    public function updateLanguage(int $userId, string $lang): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET preferred_lang = :lang WHERE id = :id");
        return $stmt->execute(['lang' => $lang, 'id' => $userId]);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254' . substr($digits, 1);
        }
        if (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            return '254' . $digits;
        }
        return $digits;
    }
}
