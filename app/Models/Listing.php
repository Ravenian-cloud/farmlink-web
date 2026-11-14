<?php

namespace App\Models;

use App\Core\Model;
use App\Services\GeoService;

class Listing extends Model
{
    protected string $table = 'listings';

    public function getCategories(?string $type = null): array
    {
        if ($type !== null) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = :type ORDER BY name ASC");
            $stmt->execute(['type' => $type]);
            return $stmt->fetchAll();
        }
        return $this->db->query("SELECT * FROM categories ORDER BY type ASC, name ASC")->fetchAll();
    }

    public function search(array $filters = []): array
    {
        $params = [];
        $selectDistance = '';
        $havingClause = '';
        $orderBy = 'l.created_at DESC';

        $hasCoords = !empty($filters['lat']) && !empty($filters['lng']);
        if ($hasCoords) {
            $distanceExpr = GeoService::distanceSql('l.latitude', 'l.longitude');
            $selectDistance = ", {$distanceExpr} AS distance_km";
            $params['user_lat']     = (float) $filters['lat'];
            $params['user_lng']     = (float) $filters['lng'];
            $params['user_lat_sin'] = (float) $filters['lat'];

            if (!empty($filters['radius_km'])) {
                $havingClause = "HAVING distance_km <= :radius_km";
                $params['radius_km'] = (float) $filters['radius_km'];
            }
            $orderBy = 'distance_km ASC, l.created_at DESC';
        }

        $where = ["l.status = 'active'", "l.quantity_available > 0"];

        if (!empty($filters['type']) && in_array($filters['type'], ['produce', 'input'], true)) {
            $where[] = "l.listing_type = :listing_type";
            $params['listing_type'] = $filters['type'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "l.category_id = :category_id";
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['county'])) {
            $where[] = "l.county = :county";
            $params['county'] = trim($filters['county']);
        }

        if (!empty($filters['q'])) {
            $where[] = "(l.title LIKE :q_title OR l.description LIKE :q_desc)";
            $keyword = '%' . trim($filters['q']) . '%';
            $params['q_title'] = $keyword;
            $params['q_desc']  = $keyword;
        }

        if ($hasCoords) {
            $where[] = "l.latitude IS NOT NULL AND l.longitude IS NOT NULL";
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT l.*, c.name AS category_name, u.full_name AS seller_name, 
                       u.kyc_status AS seller_kyc, u.rating_avg AS seller_rating, u.role AS seller_role
                       {$selectDistance}
                FROM listings l
                INNER JOIN categories c ON l.category_id = c.id
                INNER JOIN users u ON l.seller_id = u.id
                WHERE {$whereSql}
                {$havingClause}
                ORDER BY {$orderBy}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findWithSeller(int $id): ?array
    {
        $sql = "SELECT l.*, c.name AS category_name, u.full_name AS seller_name, 
                       u.phone AS seller_phone, u.county AS seller_county, u.sub_county AS seller_sub_county,
                       u.kyc_status AS seller_kyc, u.rating_avg AS seller_rating, u.rating_count AS seller_reviews
                FROM listings l
                INNER JOIN categories c ON l.category_id = c.id
                INNER JOIN users u ON l.seller_id = u.id
                WHERE l.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        if (!empty($data['offline_sync_uuid'])) {
            $check = $this->db->prepare("SELECT id FROM {$this->table} WHERE offline_sync_uuid = :uuid LIMIT 1");
            $check->execute(['uuid' => $data['offline_sync_uuid']]);
            $existing = $check->fetch();
            if ($existing) {
                return (int) $existing['id'];
            }
        }

        $sql = "INSERT INTO {$this->table}
                (seller_id, category_id, listing_type, title, description, unit, unit_price, 
                 quantity_available, min_order_qty, is_negotiable, county, latitude, longitude, 
                 harvest_or_expiry_date, image_path, offline_sync_uuid)
                VALUES
                (:seller_id, :category_id, :listing_type, :title, :description, :unit, :unit_price,
                 :quantity_available, :min_order_qty, :is_negotiable, :county, :latitude, :longitude,
                 :harvest_or_expiry_date, :image_path, :offline_sync_uuid)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'seller_id'              => (int) $data['seller_id'],
            'category_id'            => (int) $data['category_id'],
            'listing_type'           => $data['listing_type'],
            'title'                  => trim($data['title']),
            'description'            => trim($data['description']),
            'unit'                   => trim($data['unit']),
            'unit_price'             => (float) $data['unit_price'],
            'quantity_available'     => (float) $data['quantity_available'],
            'min_order_qty'          => (float) ($data['min_order_qty'] ?? 1),
            'is_negotiable'          => !empty($data['is_negotiable']) ? 1 : 0,
            'county'                 => trim($data['county']),
            'latitude'               => isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            'longitude'              => isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            'harvest_or_expiry_date' => !empty($data['harvest_or_expiry_date']) ? $data['harvest_or_expiry_date'] : null,
            'image_path'             => $data['image_path'] ?? null,
            'offline_sync_uuid'      => $data['offline_sync_uuid'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getBySeller(int $sellerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT l.*, c.name AS category_name 
             FROM listings l 
             INNER JOIN categories c ON l.category_id = c.id 
             WHERE l.seller_id = :seller_id 
             ORDER BY l.created_at DESC"
        );
        $stmt->execute(['seller_id' => $sellerId]);
        return $stmt->fetchAll();
    }
}
