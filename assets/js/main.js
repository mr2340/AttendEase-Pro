/**
 * AttendEase Pro - Core Application Logic
 */
const AttendEase = {
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
                    // Role-based redirection
                    const baseUrl = window.AttendEaseConfig ? window.AttendEaseConfig.baseUrl : '/sodex/';
                    const redirectPath = result.role === 'lecturer' ? baseUrl + 'lecturer/dashboard' : baseUrl + 'student/dashboard';
                    window.location.href = redirectPath;
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

        if (!AttendEase.scanner) {
            AttendEase.scanner = new Html5Qrcode("reader");
        }

        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        try {
            // Get available cameras
            const devices = await Html5Qrcode.getCameras();
            if (devices && devices.length > 0) {
                // Try back camera first
                let cameraId = devices[0].id;
                const backCam = devices.find(device => device.label.toLowerCase().includes('back'));
                if (backCam) cameraId = backCam.id;

                await AttendEase.scanner.start(
                    cameraId, 
                    config,
                    AttendEase.onScanSuccess,
                    AttendEase.onScanFailure
                );

                // Setup Switch Camera Button
                const switchBtn = document.getElementById('switchCamBtn');
                if (switchBtn) {
                    switchBtn.onclick = async () => {
                        const currentId = AttendEase.scanner.getCameraId();
                        const nextIndex = (devices.findIndex(d => d.id === currentId) + 1) % devices.length;
                        await AttendEase.scanner.stop();
                        await AttendEase.scanner.start(devices[nextIndex].id, config, AttendEase.onScanSuccess);
                    };
                }
            } else {
                // Fallback to default
                await AttendEase.scanner.start({ facingMode: "environment" }, config, AttendEase.onScanSuccess);
            }
        } catch (err) {
            console.error("Camera access failed", err);
            alert("Could not access camera. Please ensure you have granted permission.");
            AttendEase.closeScanner();
        }
    },

    closeScanner: async () => {
        const overlay = document.getElementById('scanner-overlay');
        overlay.classList.remove('active');
        if (AttendEase.scanner) {
            try {
                await AttendEase.scanner.stop();
            } catch (err) {
                console.warn("Scanner stop failed", err);
            }
        }
    },

    onScanSuccess: async (decodedText, decodedResult) => {
        console.log(`Scan Result: ${decodedText}`);
        
        // Stop scanner to prevent multiple scans
        await AttendEase.closeScanner();

        // Process Attendance
        try {
            const response = await fetch('../includes/process_attendance', {
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
window.AttendEase = AttendEase;
console.log('AttendEase Core Initialized');

// Auto-init for forms
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', AttendEase.handleLogin);
    }
});
