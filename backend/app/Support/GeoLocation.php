<?php

namespace App\Support;

class GeoLocation
{
    /**
     * Hitung jarak Haversine antara dua koordinat (dalam meter).
     */
    public static function haversineMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Cek apakah koordinat berada dalam radius (meter) dari pusat.
     */
    public static function withinRadius(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        float $radiusMeters
    ): bool {
        return self::haversineMeters($lat1, $lng1, $lat2, $lng2) <= $radiusMeters;
    }
}
