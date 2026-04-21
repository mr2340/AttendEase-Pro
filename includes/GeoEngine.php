<?php
/**
 * AttendEase Pro - Geo-Spatial Intelligence Engine
 */

class GeoEngine {
    
    /**
     * Calculate distance between two points in meters using Haversine formula
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            
        return $angle * $earthRadius;
    }

    /**
     * Check if a student is within range of a session center
     * Default radius: 50 meters
     */
    public static function isWithinRange($studentLat, $studentLng, $sessionLat, $sessionLng, $radius = 50) {
        if (is_null($sessionLat) || is_null($sessionLng)) return true; // Geo-fencing disabled for this session
        
        $distance = self::calculateDistance($studentLat, $studentLng, $sessionLat, $sessionLng);
        return $distance <= $radius;
    }
}
