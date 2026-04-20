<?php
/**
 * AttendEase Pro - Push Notification Engine
 */

class NotificationEngine {
    /**
     * Send a notification to a specific user or group
     * Uses Legacy FCM HTTP protocol (simplest for PHP without extra libs)
     */
    public static function send($to_token, $title, $body, $data = []) {
        $server_key = FCM_SERVER_KEY;
        
        if (!$server_key || $server_key === 'YOUR_SERVER_KEY_LEGACY') {
            return ['success' => false, 'message' => 'FCM Server Key not configured'];
        }

        $url = 'https://fcm.googleapis.com/fcm/send';

        $payload = [
            'to' => $to_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => '1',
                'click_action' => 'FCM_PLUGIN_ACTIVITY',
                'icon' => 'fcm_push_icon'
            ],
            'data' => $data,
            'priority' => 'high'
        ];

        $headers = [
            'Authorization: key=' . $server_key,
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

        if ($http_code === 200) {
            return ['success' => true, 'response' => json_decode($result, true)];
        } else {
            return ['success' => false, 'code' => $http_code, 'response' => $result];
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
}
