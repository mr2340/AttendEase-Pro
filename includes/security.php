<?php
/**
 * AttendEase Pro - Security & Rate Limiting Engine
 */

class AttendEaseSecurity {
    
    private static $limit = 60; // Requests per minute
    private static $storage_dir = __DIR__ . '/../temp/rate_limit';
    
    public static function init() {
        self::checkRateLimit();
        self::setSecurityHeaders();
    }
    
    /**
     * Advanced Rate Limiting System
     */
    private static function checkRateLimit() {
        if (!is_dir(self::$storage_dir)) {
            mkdir(self::$storage_dir, 0777, true);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $file = self::$storage_dir . '/' . md5($ip) . '.json';
        $now = time();
        
        $data = ['requests' => [], 'blocked_until' => 0];
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }
        
        // Check if currently blocked
        if ($data['blocked_until'] > $now) {
            http_response_code(429);
            include __DIR__ . '/../errors/429.php';
            exit;
        }
        
        // Filter requests in the last 60 seconds
        $data['requests'] = array_filter($data['requests'], function($ts) use ($now) {
            return $ts > ($now - 60);
        });
        
        // Add current request
        $data['requests'][] = $now;
        
        // Check threshold
        if (count($data['requests']) > self::$limit) {
            $data['blocked_until'] = $now + 60; // Block for 1 minute
            file_put_contents($file, json_encode($data));
            
            http_response_code(429);
            include __DIR__ . '/../errors/429.php';
            exit;
        }
        
        file_put_contents($file, json_encode($data));
    }
    
    /**
     * Enforcement of Security Headers (Backup for .htaccess)
     */
    private static function setSecurityHeaders() {
        if (!headers_sent()) {
            header("X-Frame-Options: SAMEORIGIN");
            header("X-XSS-Protection: 1; mode=block");
            header("X-Content-Type-Options: nosniff");
            header("Referrer-Policy: strict-origin-when-cross-origin");
            header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline' 'unsafe-eval' data:; img-src 'self' https: data:; font-src 'self' https: data:;");
        }
    }
}

// Auto-initialize security on include
AttendEaseSecurity::init();
