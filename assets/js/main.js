/**
 * AttendEase Pro - Main Application Logic
 */

const App = {
    init() {
        this.bindEvents();
        this.updateDate();
    },

    bindEvents() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
    },

    async handleLogin(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = "Signing in...";

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                this.showToast(result.message, "success");
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 800);
            } else {
                this.showToast(result.message, "danger");
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
                alert(result.message); // Fallback alert
            }
        } catch (error) {
            this.showToast("Connection error", "danger");
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    },

    updateDate() {
        const dateText = document.getElementById('date-text');
        if (dateText) {
            const options = { weekday: 'short', day: 'numeric', month: 'short' };
            dateText.innerText = new Date().toLocaleDateString('en-US', options);
        }
    },

    navTo(targetId) {
        document.querySelectorAll('.screen').forEach(screen => {
            if (screen.id === targetId) {
                screen.setAttribute('data-state', 'active');
            } else if (screen.getAttribute('data-state') === 'active') {
                screen.setAttribute('data-state', 'prev');
            }
        });

        // Reset navigation if needed
        if (targetId === 'main-dashboard') {
            this.switchTab('home', document.querySelector('.nav-item'));
        }
    },

    switchTab(tabName, element) {
        // Update Nav UI
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
        element.classList.add('active');

        // Update Content
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        const targetTab = document.getElementById('tab-' + tabName);
        if (targetTab) targetTab.classList.add('active');

        // Scroll reset
        const container = document.querySelector('.tabs-container');
        if (container) container.scrollTop = 0;
    },

    openScanner() {
        const scanner = document.getElementById('scanner-overlay');
        scanner.classList.add('active');

        // Simulate a successful scan after 2 seconds
        setTimeout(() => {
            if (scanner.classList.contains('active')) {
                this.closeScanner();
                this.fireConfetti();
                this.showToast("Attendance marked successfully!", "success");
                
                // Update stats simulation
                const scoreText = document.getElementById('scoreText');
                const progressBar = document.getElementById('progressBar');
                if (scoreText) scoreText.innerText = '90%';
                if (progressBar) progressBar.style.width = '90%';
            }
        }, 2000);
    },

    closeScanner() {
        document.getElementById('scanner-overlay').classList.remove('active');
    },

    showToast(message, type = "info") {
        // Simple toast implementation
        console.log(`${type.toUpperCase()}: ${message}`);
        // In a real app, this would show a premium floating UI element
    },

    fireConfetti() {
        const container = document.getElementById('confetti-container');
        if (!container) return;

        const colors = ['#0066ff', '#00d2ff', '#10b981', '#f59e0b', '#8b5cf6'];
        for (let i = 0; i < 50; i++) {
            let p = document.createElement('div');
            p.className = 'particle';
            p.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            p.style.left = Math.random() * 100 + '%';
            p.style.top = (Math.random() * 20 + 40) + '%';
            p.style.position = 'absolute';
            p.style.width = '8px';
            p.style.height = '8px';
            p.style.borderRadius = '2px';
            
            // Basic animation via JS if CSS is missing
            container.appendChild(p);
            
            p.animate([
                { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                { transform: 'translateY(800px) rotate(720deg)', opacity: 0 }
            ], {
                duration: Math.random() * 1000 + 1500,
                easing: 'ease-in',
                fill: 'forwards'
            });

            setTimeout(() => p.remove(), 3000);
        }
    }
};

// Initialize App
document.addEventListener('DOMContentLoaded', () => App.init());
window.navTo = (id) => App.navTo(id);
window.switchTab = (name, el) => App.switchTab(name, el);
window.openScanner = () => App.openScanner();
window.closeScanner = () => App.closeScanner();
