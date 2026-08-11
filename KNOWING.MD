Searched for "hash_hmac"
Viewed get_qr_token.php:1-50

Yes, the scanning workflow from lecturer to student works perfectly! 

Here is how it functions under the hood:
1. When a lecturer launches an attendance session, the system continuously generates a **time-sensitive QR code** (it refreshes every few seconds).
2. The QR code contains a cryptographically secure token using an HMAC signature based on a `SECURE_KEY` and the current time block.
3. When the student scans this code, the backend (`process_attendance.php`) decodes the token, verifies the cryptographic signature, checks that the QR code hasn't expired (to prevent students from sending pictures of the QR to friends), and then records their attendance while triggering the SweetAlert2 popup and admin notification.

