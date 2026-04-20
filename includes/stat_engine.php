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
     * Get detailed attendance breakdown by course for a student
     */
    public static function getDetailedAttendanceByCourse($student_id) {
        $db = get_db_connection();
        $results = [];

        try {
            // Fetch all enrolled courses
            $stmt = $db->prepare("
                SELECT c.id, c.course_name, c.course_code 
                FROM courses c 
                JOIN enrollments e ON c.id = e.course_id 
                WHERE e.student_id = ?
            ");
            $stmt->execute([$student_id]);
            $courses = $stmt->fetchAll();

            foreach ($courses as $course) {
                // Total closed sessions for this course
                $stmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE course_id = ? AND status = 'closed'");
                $stmt->execute([$course['id']]);
                $total_sessions = (int)$stmt->fetchColumn();

                // Attendance breakdown
                $stmt = $db->prepare("
                    SELECT 
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                    FROM attendance 
                    WHERE student_id = ? AND session_id IN (SELECT id FROM sessions WHERE course_id = ?)
                ");
                $stmt->execute([$student_id, $course['id']]);
                $stats = $stmt->fetch();

                $present = (int)$stats['present'];
                $late = (int)$stats['late'];
                $absent = (int)$stats['absent'];
                
                // Calculate percentage
                $percentage = ($total_sessions > 0) ? round((($present + ($late * 0.5)) / $total_sessions) * 100) : 100;

                $results[] = [
                    'course_name' => $course['course_name'],
                    'course_code' => $course['course_code'],
                    'total_sessions' => $total_sessions,
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'percentage' => $percentage
                ];
            }

            return $results;
        } catch (PDOException $e) {
            error_log("StatEngine Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent attendance logs for activity timeline
     */
    public static function getRecentActivity($student_id, $limit = 5) {
        $db = get_db_connection();
        try {
            $stmt = $db->prepare("
                SELECT a.*, c.course_name, s.topic, s.session_date
                FROM attendance a
                JOIN sessions s ON a.session_id = s.id
                JOIN courses c ON s.course_id = c.id
                WHERE a.student_id = ?
                ORDER BY a.timestamp DESC
                LIMIT ?
            ");
            $stmt->execute([$student_id, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("StatEngine Error: " . $e->getMessage());
            return [];
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
