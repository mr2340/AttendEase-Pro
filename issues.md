# Sodex Attendance System - Engineering Audit Report
**Author:** Senior Software Engineer
**Status:** Deep Audit Complete

This document outlines the critical "torch" areas (refactoring/replacement), missing roadmap features, stability risks, and architectural improvements needed to transition AttendEase Pro from a prototype to a production-grade system.

---

## 🔥 1. "Torch" List (Immediate Refactors Required)

These represent architectural debt or security vulnerabilities that should be rebuilt from scratch.

### 🛡️ QR Integrity & Anti-Spoofing
*   **Current Issue:** The "secure token" is generated using `btoa(sessionId + ":" + timeBlock)`. Any developer-savvy student can inspect the JavaScript, copy the logic, and generate their own "valid" token without scanning a real QR code.
*   **Recommendation:** Move to a **Signed Token (HMAC)** approach. The server should generate a token signed with a secret key, or use a time-based one-time password (TOTP) algorithm where the client only submits a scan and the server validates the timestamp.

### 📡 Push Notification Engine
*   **Current Issue:** `includes/notifications.php` uses the **Legacy FCM HTTP protocol**. Google has deprecated this, and it will eventually stop working.
*   **Recommendation:** Migration to **FCM HTTP v1 API** using OAuth2. This requires integrating the Google Auth Library and using service account JSON credentials.

### 🛑 Rate Limiting Implementation
*   **Current Issue:** Uses file-based storage (`temp/rate_limit`) to track IP requests. This causes high Disk I/O overhead and leads to race conditions on high-traffic servers.
*   **Recommendation:** Use **Redis** or a shared memory cache for rate limiting. If limited to standard PHP/MySQL, move this to a dedicated memory-engine database table or leverage server-level modules (e.g., Nginx `limit_req`).

### 📊 Dashboard Statistics Engine
*   **Current Issue:** Stats like "Total Students" and "Semester Attendance Score (85%)" are currently **hardcoded** or use static variables in PHP.
*   **Recommendation:** Create a centralized `StatEngine` class that performs SQL aggregations (COUNT/AVG) with caching (e.g., store results in a `user_stats` table that updates only when attendance is marked).

---

## 🛠️ 2. Critical Stability & Security Issues

### 🔐 Session Management
*   **Issue:** No `session_regenerate_id(true)` upon login. This makes the system vulnerable to **Session Fixation attacks**.
*   **Issue:** Weak role enforcement. The system checks `role` in session, but doesn't handle hierarchical permissions well (e.g., can a lecturer view *any* session details?).

### 🕸️ CSRF Protection
*   **Issue:** Forms (Login, Create Session, Announcements) lack **CSRF (Cross-Site Request Forgery)** tokens. An attacker could trick an authenticated lecturer into clicking a link that triggers a "Delete Session" or "Send Announcement" action.

### 🧱 Database Schema Maturity
*   **Issue:** Lack of a migration system. `update_db.php` is a manual "run once" script.
*   **Issue:** Missing Foregin Key constraints on many tables, which can lead to orphaned records (e.g., attendance records for a deleted session).

---

## 🚀 3. Features Not Added (Roadmap)

### 👨‍👩‍👧‍👦 Parent Portal (Functional)
*   **Current State:** Only a UI shell exists.
*   **Needed:** Student-Parent mapping logic, automated weekly progress emails, and a "Risk Level" dashboard for guardians.

### 📍 Geofencing & Location Verification
*   **Current State:** Missing.
*   **Proposed:** Capture student GPS coordinates during scan. Compare against Lecturer's "Class Location" (saved in `schedules`) to prevent students from scanning from home via shared photos.

### 🤖 Real AI Risk Prediction
*   **Current State:** Hardcoded "AI RISK" pill based on a static 75% threshold.
*   **Proposed:** Implement a predictive model using attendance patterns (e.g., student misses more classes on Mondays) to flag students likely to fail before they hit the 75% threshold.

### 📅 Advanced Administrative Control
*   **Current State:** System assumes lecturers manage themselves.
*   **Needed:** An Admin module to manage Users, Departments, and Semester dates.

### 📂 Offline Mode & Syncing
*   **Current State:** Basic PWA manifest.
*   **Needed:** Service worker logic to allow scanning while offline (storing in IndexedDB) and syncing when connection is restored.

---

## 🏗️ 4. Architectural Summary

| Layer | Assessment | Recommendation |
| :--- | :--- | :--- |
| **Frontend** | 💎 **Premium** (Excellent UI) | Keep the design; modularize CSS into components. |
| **Authentication** | ⚠️ **Medium Risk** | Implement session regeneration & better hashing. |
| **Business Logic** | 🚧 **Work-in-Progress** | Move logic out of `.php` pages and into Classes/Controllers. |
| **Infrastructure** | 🛠️ **Basic** | Move from manual scripts to a proper framework-like structure. |

---

## 🏁 Conclusion
The UI is enterprise-grade and looks stunning. However, the "engine" under the hood is currently that of a sophisticated prototype. To make this "Sodex" platform truly work at scale, the **QR Security** and **Data Aggregation** layers must be the next priority.
