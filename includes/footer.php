    </div> <!-- End #app-container -->

    <!-- Global Confetti Container -->
    <div id="confetti-container" style="position: absolute; inset: 0; pointer-events: none; z-index: 150;"></div>

    <!-- QR Scanner Overlay -->
    <div id="scanner-overlay">
        <div style="padding: 40px 24px; display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h3 style="color: white; font-weight: 700;">Scan QR Attendance</h3>
            <button onclick="App.closeScanner()" style="background: rgba(255,255,255,0.1); border: none; width: 40px; height: 40px; border-radius: 50%; color: white; display: flex; justify-content: center; align-items: center; cursor: pointer;">
                <i data-lucide="x"></i>
            </button>
        </div>
        
        <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative;">
            <!-- Hidden real scanner element -->
            <div id="reader" style="width: 100%; max-width: 320px; position: absolute; opacity: 0;"></div>
            
            <div id="custom-frame" class="scanner-frame">
                <div class="scanner-laser"></div>
                <!-- Corner Borders -->
                <div style="position: absolute; top: 0; left: 0; width: 40px; height: 40px; border-top: 4px solid var(--secondary); border-left: 4px solid var(--secondary); border-radius: 20px 0 0 0;"></div>
                <div style="position: absolute; top: 0; right: 0; width: 40px; height: 40px; border-top: 4px solid var(--secondary); border-right: 4px solid var(--secondary); border-radius: 0 20px 0 0;"></div>
                <div style="position: absolute; bottom: 0; left: 0; width: 40px; height: 40px; border-bottom: 4px solid var(--secondary); border-left: 4px solid var(--secondary); border-radius: 0 0 0 20px;"></div>
                <div style="position: absolute; bottom: 0; right: 0; width: 40px; height: 40px; border-bottom: 4px solid var(--secondary); border-right: 4px solid var(--secondary); border-radius: 0 0 20px 0;"></div>
            </div>
            
            <p style="color: rgba(255,255,255,0.6); margin-top: 40px; font-size: 14px; text-align: center; padding: 0 40px;">Align the QR code within the frame to automatically verify your attendance.</p>
        </div>

        <div style="padding: 40px; display: flex; justify-content: center;">
             <button id="switchCamBtn" style="background: white; border: none; padding: 16px 32px; border-radius: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--text-dark);">
                <i data-lucide="refresh-cw" style="width: 18px; height: 18px;"></i>
                Switch Camera
             </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
