<?php
/**
 * AttendEase vscode-webview://1en2phd04qt4o5vbc35r61023ttgsv8qpfk9fcf8dc2f8bomugcc/index.html?id=8f9e5a0c-aa75-480c-9930-be7d0e5f0033&parentId=1&origin=bc05882a-762d-4cc6-9069-e70cc0c16a9d&swVersion=4&extensionId=eamodio.gitlens&platform=electron&vscode-resource-base-authority=vscode-resource.vscode-cdn.net&parentOrigin=vscode-file%3A%2F%2Fvscode-app&purpose=webviewView#Pro - Security & Rate Limiting Engine
 */

class AttendEaseSecurity {
    
    private static $limit = 300; // Requests per minute
    private static $storage_dir = __DIR__ . '/../temp/rate_limit';
    
    public static function init() {
        self::secureSession();
        self::checkRateLimit();
        self::setSecurityHeaders();
        self::initCsrf();
    }

    /**
     * Session Security Hardening
     */
    private static function secureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    /**
     * CSRF Protection Engine
     */
    public static function initCsrf() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function validateCsrf($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Security Error: Invalid CRSF Token.']));
        }
        return true;
    }

    public static function getCsrfToken() {
        return $_SESSION['csrf_token'] ?? '';
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
            
            // Hardened CSP (Synchronized with .htaccess)
            $baseUrlHost = '';
            if (defined('BASE_URL')) {
                $parsed = parse_url(BASE_URL);
                if (isset($parsed['scheme']) && isset($parsed['host'])) {
                    $baseUrlHost = ' ' . $parsed['scheme'] . '://' . $parsed['host'];
                    if (isset($parsed['port'])) {
                        $baseUrlHost .= ':' . $parsed['port'];
                    }
                }
            }

            $csp = "default-src 'self'$baseUrlHost; ";
            $csp .= "script-src 'self'$baseUrlHost 'unsafe-inline' 'unsafe-eval' blob: https://unpkg.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://apis.google.com https://upload-widget.cloudinary.com https://widget.cloudinary.com; ";
            $csp .= "style-src 'self'$baseUrlHost 'unsafe-inline' https://fonts.googleapis.com; ";
            $csp .= "font-src 'self'$baseUrlHost https://fonts.gstatic.com; ";
            $csp .= "img-src 'self'$baseUrlHost data: https://api.dicebear.com https://*.googleusercontent.com https://*.cloudinary.com; ";
            $csp .= "connect-src 'self'$baseUrlHost https://fcmregistrations.googleapis.com https://www.gstatic.com https://unpkg.com https://fonts.googleapis.com https://fonts.gstatic.com https://api.cloudinary.com https://widget.cloudinary.com;";
            header("Content-Security-Policy: " . $csp);
        }
    }
}

// Auto-initialize security on include
AttendEaseSecurity::init();
