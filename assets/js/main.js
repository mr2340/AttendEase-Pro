/**
 * AttendEase Pro - Core Application Logic
 */
const App = {
    scanner: null,

    // Auth Handling
    handleLogin: async (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = 'Authenticating...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                submitBtn.innerText = 'Redirecting...';
                setTimeout(() => {
                    window.location.href = 'student/dashboard.php';
                }, 800);
            } else {
                alert(result.message || 'Login failed');
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        } catch (error) {
            console.error('Login Error:', error);
            alert('An error occurred during login.');
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    },

    // QR Scanner Implementation
    startScanner: async () => {
        const overlay = document.getElementById('scanner-overlay');
        overlay.classList.add('active');

        if (!App.scanner) {
            App.scanner = new Html5Qrcode("reader");
        }

        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        try {
            await App.scanner.start(
                { facingMode: "environment" }, 
                config,
                App.onScanSuccess,
                App.onScanFailure
            );
        } catch (err) {
            console.error("Camera access failed", err);
            alert("Could not access camera. Please ensure you have granted permission.");
            App.closeScanner();
        }
    },

    closeScanner: async () => {
        const overlay = document.getElementById('scanner-overlay');
        overlay.classList.remove('active');
        if (App.scanner) {
            try {
                await App.scanner.stop();
            } catch (err) {
                console.warn("Scanner stop failed", err);
            }
        }
    },

    onScanSuccess: async (decodedText, decodedResult) => {
        console.log(`Scan Result: ${decodedText}`);
        
        // Stop scanner to prevent multiple scans
        await App.closeScanner();

        // Process Attendance
        try {
            const response = await fetch('../includes/process_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: decodedText })
            });
            const result = await response.json();

            if (result.success) {
                alert("Success! Your attendance has been marked.");
                location.reload(); // Refresh to see updated stats
            } else {
                alert(result.message || "Failed to mark attendance.");
            }
        } catch (err) {
            alert("Server connection error. Please try again.");
        }
    },

    onScanFailure: (error) => {
        // Silent failure (normal during scanning)
    }
};

// Global Export
window.App = App;

// Auto-init for forms
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', App.handleLogin);
    }
});
