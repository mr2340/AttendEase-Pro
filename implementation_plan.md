# Implementation Plan - Real-time Broadcast Intelligence

Implement a high-fidelity broadcast system that allows lecturers to send "Push-Up" notifications and in-app alerts to students instantly.

## 🎯 Objectives
- **Lecturer Interface**: Enable instant announcement dispatch with real-time feedback.
- **Push Notifications**: Integrate FCM (Firebase Cloud Messaging) for system-level push-up notifications.
- **In-App Alerts**: Implement dynamic SweetAlert2 pop-ups for active students.
- **Persistence**: Ensure announcements are saved in a permanent feed for future reference.

## 🛠️ Proposed Changes

### 1. Database Schema
#### [NEW] [announcements_table](file:///c:/xampp/htdocs/sodex/includes/schema_update.php)
- Provision the `announcements` table to store broadcast history.
- Columns: `id, course_id, lecturer_id, title, message, created_at`.

### 2. Backend Logic
#### [NEW] [process_broadcast.php](file:///c:/xampp/htdocs/sodex/includes/process_broadcast.php)
- Handle the AJAX submission from the lecturer dashboard.
- Save the broadcast to the database.
- Trigger `NotificationEngine::broadcastToCourse()` to send FCM push notifications to all enrolled students.

#### [MODIFY] [notifications.php](file:///c:/xampp/htdocs/sodex/includes/notifications.php)
- Add a new method `broadcastToCourse($course_id, $title, $body)` to fetch all student tokens for a specific course and batch-send notifications.

### 3. Frontend Integration
#### [MODIFY] [lecturer/dashboard.php](file:///c:/xampp/htdocs/sodex/lecturer/dashboard.php)
- Add JS event listener to `announcementForm` to submit via AJAX.
- Show "Sending..." and "Sent Successfully" states with SweetAlert2.

#### [MODIFY] [assets/js/main.js](file:///c:/xampp/htdocs/sodex/assets/js/main.js)
- Implement a real-time polling or FCM foreground listener to trigger the "Push-Up" SweetAlert2 pop-up when an announcement arrives while the student is in the app.

#### [MODIFY] [student/dashboard.php](file:///c:/xampp/htdocs/sodex/student/dashboard.php)
- Ensure the "Faculty Announcements" section is fully dynamic and includes a "Live" or "New" indicator.

## 🧪 Verification Plan
- **Manual Verification**:
    1. Log in as a Lecturer.
    2. Submit a Broadcast for "Broadcast Intelligence" course.
    3. Verify that a success alert appears in the Lecturer dashboard.
    4. Verify that a Push Notification appears on the student's device/browser.
    5. Verify that a "Push-Up" SweetAlert2 pop-up appears if the student is actively viewing their dashboard.
- **Database Verification**: Confirm the entry is stored correctly in the `announcements` table.

## ❓ Open Questions
- Should we include an option to attach files/links to broadcasts in the future?
- Do you want a sound alert to play when the in-app pop-up appears?
