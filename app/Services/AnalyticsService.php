<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class AnalyticsService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetches historical price snapshots and forecasts next-period price.
     */
    public function getPriceTrendsAndForecast(string $county = 'Nakuru'): array
    {
        $stmt = $this->db->prepare(
            "SELECT crop_name, avg_unit_price, total_volume_traded, recorded_date
             FROM market_price_snapshots
             WHERE county = :county
             ORDER BY recorded_date ASC"
        );
        $stmt->execute(['county' => $county]);
        $rows = $stmt->fetchAll();

        // Fallback to all counties if selected county has no snapshots yet
        if (empty($rows)) {
            $rows = $this->db->query(
                "SELECT crop_name, avg_unit_price, total_volume_traded, recorded_date
                 FROM market_price_snapshots
                 ORDER BY recorded_date ASC LIMIT 12"
            )->fetchAll();
        }

        $forecastPrice = null;
        $trendDirection = 'stable';

        if (count($rows) >= 2) {
            $first = (float) $rows[0]['avg_unit_price'];
            $last  = (float) $rows[count($rows) - 1]['avg_unit_price'];
            $step  = ($last - $first) / (count($rows) - 1);
            $forecastPrice = round(max(0, $last + $step), 2);
            $trendDirection = $step > 5 ? 'rising' : ($step < -5 ? 'falling' : 'stable');
        }

        return [
            'series'          => $rows,
            'forecast_price'  => $forecastPrice,
            'trend_direction' => $trendDirection,
        ];
    }

    /**
     * Ranks top crops by active marketplace demand & completed order volume.
     */
    public function getDemandInsights(): array
    {
        $sql = "SELECT c.name AS category_name,
                       COUNT(DISTINCT l.id) AS active_listings,
                       COALESCE(SUM(o.quantity), 0) AS traded_volume,
                       COALESCE(AVG(l.unit_price), 0) AS avg_asking_price
                FROM categories c
                LEFT JOIN listings l ON l.category_id = c.id AND l.status = 'active'
                LEFT JOIN orders o ON o.listing_id = l.id AND o.status IN ('in_escrow', 'in_transit', 'delivered')
                WHERE c.type = 'produce'
                GROUP BY c.id, c.name
                ORDER BY traded_volume DESC, active_listings DESC";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Recommends matching seeds, fertilizers, and crop protection inputs for a county/crop.
     */
    public function getRecommendedInputs(string $county, string $cropKeyword = 'maize'): array
    {
        $sql = "SELECT l.*, u.full_name AS supplier_name, u.kyc_status
                FROM listings l
                INNER JOIN users u ON l.seller_id = u.id
                WHERE l.listing_type = 'input'
                  AND l.status = 'active'
                  AND (l.county = :county OR l.title LIKE :kw1 OR l.description LIKE :kw2)
                ORDER BY (l.county = :county_sort) DESC, u.rating_avg DESC
                LIMIT 4";

        $kw = '%' . trim($cropKeyword) . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'county'      => $county,
            'kw1'         => $kw,
            'kw2'         => $kw,
            'county_sort' => $county,
        ]);

        return $stmt->fetchAll();
    }
}
