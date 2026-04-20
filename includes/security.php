<?php
/**
 * AttendEase Pro - Security & Rate Limiting Engine
 */

class AttendEaseSecurity {
    
    private static $limit = 300; // Requests per minute
    private static $storage_dir = __DIR__ . '/../temp/rate_limit';
    
    public static function init() {
        self::checkRateLimit();
        self::setSecurityHeaders();
    }
    
    /**
     * Advanced Rate Limiting System
     */
    private static function checkRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($ip === '::1') $ip = '127.0.0.1'; // Normalize localhost
        
        $now = time();
        $db = get_db_connection();
        
        try {
            $stmt = $db->prepare("SELECT * FROM rate_limits WHERE ip = ?");
            $stmt->execute([$ip]);
            $data = $stmt->fetch();
            
            if ($data) {
                // Check if currently blocked
                if ($data['blocked_until'] > $now) {
                    http_response_code(429);
                    include __DIR__ . '/../errors/429.php';
                    exit;
                }
                
                // Reset window if last request was more than 60s ago
                if ($data['last_request'] < ($now - 60)) {
                    $count = 1;
                } else {
                    $count = $data['request_count'] + 1;
                }
                
                $blocked_until = 0;
                if ($count > self::$limit) {
                    $blocked_until = $now + 60; // Block for 1 minute
                }
                
                $stmt = $db->prepare("UPDATE rate_limits SET request_count = ?, last_request = ?, blocked_until = ? WHERE ip = ?");
                $stmt->execute([$count, $now, $blocked_until, $ip]);
                
                if ($blocked_until > 0) {
                    http_response_code(429);
                    include __DIR__ . '/../errors/429.php';
                    exit;
                }
            } else {
                // First request from this IP
                $stmt = $db->prepare("INSERT INTO rate_limits (ip, request_count, last_request, blocked_until) VALUES (?, 1, ?, 0)");
                $stmt->execute([$ip, $now]);
            }
        } catch (PDOException $e) {
            // Silently fail or log to error_log to prioritize availability of the app
            error_log("Rate Limit DB Error: " . $e->getMessage());
        }
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
