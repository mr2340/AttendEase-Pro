<?php
/**
 * AttendEase Pro - Push Notification Engine
 */

class NotificationEngine {
    /**
     * Send a notification to a specific user or group
     * Uses Legacy FCM HTTP protocol (simplest for PHP without extra libs)
     */
    /**
     * Send a notification using modern FCM HTTP v1 API
     */
    public static function send($to_token, $title, $body, $data = []) {
        $service_account_path = FCM_SERVICE_ACCOUNT;
        $project_id = FCM_PROJECT_ID;

        if (!file_exists($service_account_path)) {
            error_log("FCM Error: service-account.json not found at $service_account_path. Notification not sent.");
            return ['success' => false, 'message' => 'Service account missing'];
        }

        $access_token = self::getAccessToken($service_account_path);
        if (!$access_token) {
            return ['success' => false, 'message' => 'Failed to generate OAuth token'];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

        $payload = [
            'message' => [
                'token' => $to_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FCM_PLUGIN_ACTIVITY',
                        'icon' => 'fcm_push_icon'
                    ]
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_code === 200) 
            ? ['success' => true, 'response' => json_decode($result, true)]
            : ['success' => false, 'code' => $http_code, 'response' => $result];
    }

    /**
     * Helper: Generate OAuth2 Access Token using Service Account (JWT)
     * Pure PHP implementation to avoid heavy dependencies
     */
    private static function getAccessToken($json_path) {
        try {
            $json = json_decode(file_get_contents($json_path), true);
            $now = time();
            
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $claim = json_encode([
                'iss' => $json['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ]);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));
            
            $signature = '';
            openssl_sign($base64UrlHeader . "." . $base64UrlClaim, $signature, $json['private_key'], 'SHA256');
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            $jwt = $base64UrlHeader . "." . $base64UrlClaim . "." . $base64UrlSignature;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]));

            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);

            return $result['access_token'] ?? null;
        } catch (Exception $e) {
            error_log("OAuth2 Token Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Notify about attendance mark success
     */
    public static function notifyAttendanceMarked($user_id, $course_name) {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT fcm_token FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $token = $stmt->fetchColumn();

        if ($token) {
            return self::send(
                $token, 
                "Attendance Verified", 
                "You have been successfully marked present for $course_name."
            );
        }
        return ['success' => false, 'message' => 'No token found for user'];
    }

    /**
     * Broadcast a notification to all students enrolled in a course
     */
    public static function broadcastToCourse($course_id, $title, $body) {
        $db = get_db_connection();
        
        // Fetch all tokens for students enrolled in the course
        $stmt = $db->prepare("
            SELECT u.fcm_token 
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            WHERE e.course_id = ? AND u.fcm_token IS NOT NULL
        ");
        $stmt->execute([$course_id]);
        $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tokens)) {
            return ['success' => true, 'total' => 0, 'sent' => 0, 'message' => 'No students enrolled with active tokens.'];
        }

        $sent_count = 0;
        foreach ($tokens as $token) {
            $res = self::send($token, $title, $body, [
                'type' => 'broadcast', 
                'course_id' => (string)$course_id,
                'urgent' => 'true'
            ]);
            if ($res['success']) $sent_count++;
        }

        return [
            'success' => true, 
            'total' => count($tokens), 
            'sent' => $sent_count
        ];
    }
}
