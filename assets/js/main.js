/**
 * AttendEase Pro - Core Application Logic
 */
const AttendEase = {
    scanner: null,

    // Celebration Engine: Creates floating bubbles
    fireConfetti: () => {
        const container = document.getElementById('confetti-container');
        if (!container) return;

        const colors = ['#0066ff', '#00d2ff', '#10b981', '#f59e0b', '#ef4444'];
        
        for (let i = 0; i < 40; i++) {
            setTimeout(() => {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Randomize appearance
                const size = Math.random() * 20 + 10;
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.backgroundColor = color;
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.bottom = '-20px';
                particle.style.opacity = Math.random() * 0.5 + 0.3;
                
                // Randomize animation duration
                particle.style.animationDuration = `${Math.random() * 2 + 2}s`;
                
                container.appendChild(particle);
                
                // Cleanup
                setTimeout(() => particle.remove(), 4000);
            }, i * 50);
        }
    },

    // Helper to get GPS Location for Geo-Fencing
    getLocation: () => {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error("Geolocation not supported"));
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                (err) => reject(err),
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        });
    },

    // Premium Notifications
    notify: (type, title, text) => {
        return Swal.fire({
            icon: type,
            title: title,
            text: text,
            confirmButtonText: 'Got it',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn-primary swal2-confirm'
            },
            showClass: {
                popup: 'animate__animated animate__fadeInUp animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutDown animate__faster'
            }
        });
    },

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
                AttendEase.notify('error', 'Login Failed', result.message || 'Check your credentials and try again.');
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        } catch (error) {
            console.error('Login Error:', error);
            AttendEase.notify('error', 'Connection Error', 'We couldn\'t reach the server. Please check your internet.');
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
            AttendEase.notify('error', 'Camera Error', 'Could not access camera. Please ensure you have granted permission.');
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

    // FCM Integration
    initFCM: async () => {
        if (!('serviceWorker' in navigator)) return;
        if (!window.AttendEaseConfig || !window.AttendEaseConfig.fcm.apiKey) {
            console.warn('FCM Config missing. Skipping initialization.');
            return;
        }

        try {
            // Register Service Worker
            const registration = await navigator.serviceWorker.register(window.AttendEaseConfig.baseUrl + 'firebase-messaging-sw.js');
            console.log('FCM Service Worker registered');

            // Initialize Firebase in Frontend
            if (!firebase.apps.length) {
                firebase.initializeApp(window.AttendEaseConfig.fcm);
            }
            const messaging = firebase.messaging();

            // Pass config to Service Worker
            if (registration.active) {
                registration.active.postMessage({
                    type: 'INIT_FIREBASE',
                    config: window.AttendEaseConfig.fcm
                });
            }

            // Request Permission
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                const token = await messaging.getToken({
                    vapidKey: window.AttendEaseConfig.fcm.vapidKey,
                    serviceWorkerRegistration: registration
                });

                if (token) {
                    await AttendEase.saveFCMToken(token);
                }
            }

            // Foreground Pulse Listener
            messaging.onMessage((payload) => {
                console.log('Pulse received:', payload);
                const title = payload.notification ? payload.notification.title : (payload.data ? payload.data.title : 'New Pulse');
                const body = payload.notification ? payload.notification.body : (payload.data ? payload.data.message : '');

                // Trigger High-Fidelity Alert
                AttendEase.notify('info', title, body).then(() => {
                    // If we are on the student dashboard, it might be good to reload to show the new card
                    if (window.location.pathname.includes('student/dashboard')) {
                        location.reload();
                    }
                });
            });
        } catch (error) {
            console.error('FCM Initialization Error:', error);
        }
    },

    saveFCMToken: async (token) => {
        try {
            await fetch(window.AttendEaseConfig.baseUrl + 'includes/save_token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token })
            });
        } catch (err) {
            console.error('Token Save Error:', err);
        }
    },

    onScanSuccess: async (decodedText, decodedResult) => {
        console.log(`Scan Result: ${decodedText}`);
        
        // Stop scanner to prevent multiple scans
        await AttendEase.closeScanner();

        // 📍 GEO-FENCING INTEGRATION
        let lat = null, lng = null;
        try {
            // Show scanning location state
            const locationMsg = Swal.fire({
                title: 'Verifying Location...',
                html: 'Ensuring you are within classroom bounds.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const pos = await AttendEase.getLocation();
            lat = pos.lat;
            lng = pos.lng;
            Swal.close();
        } catch (err) {
            console.warn("Location check failed:", err.message);
            // We still proceed, but the server will strictly enforce if required
        }

        // Process Attendance
        try {
            const response = await fetch('../includes/process_attendance', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.AttendEaseConfig ? window.AttendEaseConfig.csrfToken : ''
                },
                body: JSON.stringify({ 
                    session_id: decodedText,
                    lat: lat,
                    lng: lng
                })
            });
            const result = await response.json();

            if (result.success) {
                // SUCCESS CELEBRATION
                AttendEase.fireConfetti();
                
                await Swal.fire({
                    icon: 'success',
                    title: 'Attendance Marked!',
                    text: 'Your attendance has been recorded successfully.',
                    confirmButtonText: 'Great!',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn-primary swal2-confirm'
                    }
                });
                
                location.reload(); // Refresh to see updated stats
            } else {
                AttendEase.notify('warning', 'Almost There', result.message || "Failed to mark attendance.");
            }
        } catch (err) {
            AttendEase.notify('error', 'Server Error', "Server connection error. Please try again.");
        }
    },

    onScanFailure: (error) => {
        // Silent failure (normal during scanning)
    }
};

// Global Export
window.AttendEase = AttendEase;
console.log('AttendEase Core Initialized');

// Global check to hide if already installed/standalone
const checkStandalone = () => {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                        window.navigator.standalone === true ||
                        document.referrer.includes('android-app://');
                        
    if (isStandalone) {
        const installBtn = document.getElementById('pwa-install-mini');
        if (installBtn) {
            installBtn.remove(); // Absolute removal from DOM
        }
    }
    return isStandalone;
};

// PWA Installation Handling
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent default browser prompt
    e.preventDefault();
    
    // Final check: if installed or standalone, kill the button and exit
    if (checkStandalone()) {
        deferredPrompt = null;
        return;
    }

    // Stash the event
    deferredPrompt = e;
    
    // Force show the button with high priority
    const installBtn = document.getElementById('pwa-install-mini');
    if (installBtn) {
        installBtn.style.setProperty('display', 'flex', 'important');
    }
});

window.addEventListener('appinstalled', (e) => {
    console.log('Pulse PWA installed successfully');
    const installBtn = document.getElementById('pwa-install-mini');
    if (installBtn) {
        installBtn.remove(); // Kill on success
    }
    deferredPrompt = null;
});

// Auto-init for forms and PWA
document.addEventListener('DOMContentLoaded', () => {
    checkStandalone();
    try {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', AttendEase.handleLogin);
        }

        const installBtn = document.getElementById('pwa-install-mini');
        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                installBtn.style.display = 'none';
            });
        }

        // Register SW
        if ('serviceWorker' in navigator) {
            const swPath = (window.AttendEaseConfig ? window.AttendEaseConfig.baseUrl : '/sodex/') + 'firebase-messaging-sw.js';
            navigator.serviceWorker.register(swPath);
        }

        // Initialize FCM features if logged in
        const isLoginPage = window.location.pathname.includes('login');
        const isIndexPage = window.location.pathname.endsWith('/sodex/') || window.location.pathname.endsWith('index.php');
        if (!isLoginPage && !isIndexPage) {
            AttendEase.initFCM();
        }
    } catch (e) {
        console.warn("Main.js init error suppressed:", e);
    }

    // Ultra-Resilient Lucide Initialization
    function renderIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    renderIcons();
    setTimeout(renderIcons, 500);
    setTimeout(renderIcons, 2000);
    setInterval(renderIcons, 5000); // Heartbeat scan
});
