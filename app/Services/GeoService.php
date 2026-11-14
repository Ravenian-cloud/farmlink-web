<?php

namespace App\Services;

class GeoService
{
    /**
     * Generates a MySQL Haversine distance expression (in kilometers).
     */
    public static function distanceSql(string $latColumn = 'l.latitude', string $lngColumn = 'l.longitude'): string
    {
        return "(6371 * acos(
            cos(radians(:user_lat)) * cos(radians({$latColumn})) *
            cos(radians({$lngColumn}) - radians(:user_lng)) +
            sin(radians(:user_lat_sin)) * sin(radians({$latColumn}))
        ))";
    }

    /**
     * Computes great-circle distance in kilometers between two GPS points.
     */
    public static function calculateKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 1);
    }
}
