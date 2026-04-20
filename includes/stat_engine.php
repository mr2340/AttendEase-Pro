<?php
/**
 * AttendEase Pro - Intelligence Statistics Engine
 * Centralized aggregation for attendance data and student metrics.
 */

class StatEngine {

    /**
     * Get attendance score for a specific student
     * Calculates: (Sessions Attended) / (Total Sessions for Enrolled Courses)
     */
    public static function getStudentAttendanceScore($student_id) {
        $db = get_db_connection();
        
        try {
            // Get total sessions for all courses the student is enrolled in
            $stmt = $db->prepare("
                SELECT COUNT(s.id) 
                FROM sessions s 
                JOIN enrollments e ON s.course_id = e.course_id 
                WHERE e.student_id = ? AND s.status = 'closed'
            ");
            $stmt->execute([$student_id]);
            $total_expected = (int)$stmt->fetchColumn();
            
            if ($total_expected === 0) return 100; // No sessions yet, stay positive

            // Get total sessions attended
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM attendance 
                WHERE student_id = ? AND status = 'present'
            ");
            $stmt->execute([$student_id]);
            $total_attended = (int)$stmt->fetchColumn();
            
            return round(($total_attended / $total_expected) * 100);
            
        } catch (PDOException $e) {
            error_log("StatEngine Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get overall lecturer statistics
     */
    public static function getLecturerStats($lecturer_id) {
        $db = get_db_connection();
        
        try {
            // Total students reached (Unique students in all lecturer's courses)
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT e.student_id) 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.lecturer_id = ?
            ");
            $stmt->execute([$lecturer_id]);
            $total_students = (int)$stmt->fetchColumn();

            // Total sessions held
            $stmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE lecturer_id = ?");
            $stmt->execute([$lecturer_id]);
            $total_sessions = (int)$stmt->fetchColumn();

            return [
                'total_students' => $total_students,
                'total_sessions' => $total_sessions,
                'growth' => '+12.5%' // Mock growth for now, could be calculated by comparing weeks
            ];
            
        } catch (PDOException $e) {
            error_log("StatEngine Error: " . $e->getMessage());
            return ['total_students' => 0, 'total_sessions' => 0, 'growth' => '0%'];
        }
    }
}
?>
