/**
 * Orchestrator Scanner - Alpine.js Component
 */

// Global helper functions
function confirmLogout() {
    if (confirm('Are you sure you want to log out?')) {
        fetch(window.ORCHESTRATOR_CONFIG.apiUrl + '?action=orchestrator_logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: window.ORCHESTRATOR_CONFIG.csrfToken })
        }).then(() => {
            window.location.href = '/admin/forms/orchestrator/login.php?form_id=' + encodeURIComponent(window.ORCHESTRATOR_CONFIG.formId);
        });
    }
}

function selectLocation(location) {
    fetch(window.ORCHESTRATOR_CONFIG.apiUrl + '?action=set_location', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: window.ORCHESTRATOR_CONFIG.csrfToken,
            location: location
        })
    }).then(() => window.location.reload());
}

// Alpine.js component
function scannerApp() {
    return {
        config: window.ORCHESTRATOR_CONFIG,
        scannedParticipant: null,
        warning: null,
        recentScans: [],
        showInfoModal: false,
        showLocationPicker: false,
        fullInfo: null,
        actionLoading: false,
        
        // Camera state
        video: null,
        canvas: null,
        canvasElement: null,
        cameraStream: null,
        scanning: false,
        scanInterval: null,
        lastScannedCode: null,
        lastScanTime: 0,

        init() {
            this.loadRecentScans();
            this.initCamera();
            this.setupManualLookup();
            this.setupImageScanning();
            this.setupLocationChange();
        },

        canPerformAction(action) {
            return this.config.allowedActions.includes(action);
        },

        async lookupById(regId) {
            regId = regId.toUpperCase().trim();
            if (!regId.startsWith('REG-')) regId = 'REG-' + regId;

            try {
                const response = await fetch(this.config.apiUrl + '?action=lookup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: this.config.csrfToken,
                        form_id: this.config.formId,
                        reg_id: regId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.scannedParticipant = {
                        id: data.registration.id,
                        name: data.registration.data?.full_name || 'Unknown',
                        school: data.registration.data?.school_origin || '-',
                        class: data.registration.data?.assigned_class || '-',
                        gate: data.registration.data?.assigned_gate || '-',
                        participant_number: data.participant_data?.participant_number || '-',
                        desk_number: data.participant_data?.desk_number || '-',
                        status: data.registration.status,
                        checked_in: data.orchestrator_status?.checked_in,
                        merch_collected: data.orchestrator_status?.merch_collected
                    };
                    this.updateWarning();
                    this.playBeep();
                } else {
                    this.showToast(data.error || 'Registration not found', 'error');
                }
            } catch (err) {
                console.error('Lookup error:', err);
                this.showToast('Failed to lookup registration', 'error');
            }
        },

        updateWarning() {
            this.warning = null;
            if (this.scannedParticipant?.checked_in && this.canPerformAction('check_in')) {
                this.warning = 'This participant has already checked in!';
            } else if (this.scannedParticipant?.merch_collected && this.canPerformAction('merch')) {
                this.warning = 'This participant has already collected their merchandise!';
            } else if (this.scannedParticipant?.status === 'verified' && this.canPerformAction('payment')) {
                this.warning = 'This participant is already verified.';
            }
        },

        async performAction(action, extraData = {}) {
            if (!this.scannedParticipant) return;
            this.actionLoading = true;

            try {
                const response = await fetch(this.config.apiUrl + '?action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: this.config.csrfToken,
                        form_id: this.config.formId,
                        reg_id: this.scannedParticipant.id,
                        ...extraData
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.addToRecentScans(action, this.scannedParticipant.id, this.scannedParticipant.name);
                    this.showToast(data.message || 'Action completed successfully', 'success');
                    
                    // Update local state
                    if (action === 'check_in') this.scannedParticipant.checked_in = true;
                    if (action === 'give_merch') this.scannedParticipant.merch_collected = true;
                    if (action === 'verify_payment') this.scannedParticipant.status = 'verified';
                    
                    this.updateWarning();
                } else {
                    this.showToast(data.error || 'Action failed', 'error');
                }
            } catch (err) {
                console.error('Action error:', err);
                this.showToast('Failed to perform action', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        async performCheckInAndMerch() {
            if (!this.scannedParticipant) return;
            this.actionLoading = true;

            try {
                // Check in first
                await this.performAction('check_in');
                // Then give merch
                await this.performAction('give_merch');
            } finally {
                this.actionLoading = false;
            }
        },

        async showFullInfo() {
            if (!this.scannedParticipant) return;

            try {
                const response = await fetch(this.config.apiUrl + '?action=get_full_participant_info', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: this.config.csrfToken,
                        form_id: this.config.formId,
                        reg_id: this.scannedParticipant.id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.fullInfo = data.info;
                    this.showInfoModal = true;
                } else {
                    this.showToast(data.error || 'Failed to load info', 'error');
                }
            } catch (err) {
                console.error('Full info error:', err);
                this.showToast('Failed to load participant info', 'error');
            }
        },

        closeResult() {
            this.scannedParticipant = null;
            this.warning = null;
            if (this.cameraStream && !document.hidden) {
                this.resumeScanning();
            }
        },

        setLocation(location) {
            selectLocation(location);
        },

        // Camera methods
        initCamera() {
            this.video = document.getElementById('qrVideo');
            this.canvasElement = document.createElement('canvas');
            this.canvas = this.canvasElement.getContext('2d', { willReadFrequently: true });

            const retryBtn = document.getElementById('retryCamera');
            if (retryBtn) retryBtn.addEventListener('click', () => this.startCamera());

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseScanning();
                } else if (this.cameraStream) {
                    this.resumeScanning();
                }
            });

            window.addEventListener('beforeunload', () => this.stopCamera());

            this.startCamera();
        },

        async startCamera() {
            const cameraError = document.getElementById('cameraError');
            try {
                this.cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                this.video.srcObject = this.cameraStream;
                await this.video.play().catch(() => {});
                cameraError.classList.add('hidden');
                this.resumeScanning();
            } catch (err) {
                console.error('Camera error:', err);
                cameraError.classList.remove('hidden');
            }
        },

        stopCamera() {
            this.pauseScanning();
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(track => track.stop());
                this.cameraStream = null;
            }
        },

        pauseScanning() {
            this.scanning = false;
            if (this.scanInterval) {
                clearInterval(this.scanInterval);
                this.scanInterval = null;
            }
        },

        resumeScanning() {
            if (this.scanning) return;
            this.scanning = true;
            this.scanInterval = setInterval(() => this.scanFrame(), 200); // 5fps for performance
        },

        scanFrame() {
            if (!this.scanning || !this.video || this.video.readyState !== this.video.HAVE_ENOUGH_DATA) return;

            let width = Math.min(this.video.videoWidth, 640);
            let height = Math.min(this.video.videoHeight, 480);

            this.canvasElement.width = width;
            this.canvasElement.height = height;
            this.canvas.drawImage(this.video, 0, 0, width, height);

            const imageData = this.canvas.getImageData(0, 0, width, height);
            const code = jsQR(imageData.data, width, height, { inversionAttempts: 'dontInvert' });

            if (code && code.data) {
                const now = Date.now();
                if (code.data === this.lastScannedCode && (now - this.lastScanTime) < 3000) return;
                
                this.lastScannedCode = code.data;
                this.lastScanTime = now;

                let regId = code.data;
                try {
                    const parsed = JSON.parse(code.data);
                    regId = parsed.id || parsed.reg_id || code.data;
                } catch {}

                this.pauseScanning();
                this.lookupById(regId);
            }
        },

        setupManualLookup() {
            const input = document.getElementById('manualIdInput');
            const btn = document.getElementById('manualLookupBtn');

            btn.addEventListener('click', () => {
                const query = input.value.trim();
                if (query) this.searchParticipants(query);
            });

            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const query = input.value.trim();
                    if (query) this.searchParticipants(query);
                }
            });
        },

        async searchParticipants(query) {
            if (query.length < 2) {
                this.showToast('Enter at least 2 characters', 'error');
                return;
            }

            const resultsContainer = document.getElementById('manualSearchResults');
            const resultsList = document.getElementById('manualSearchResultsList');
            const noResults = document.getElementById('manualNoResults');

            try {
                const response = await fetch(this.config.apiUrl + '?action=search_participants', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: this.config.csrfToken,
                        form_id: this.config.formId,
                        query: query
                    })
                });

                const data = await response.json();

                if (data.success && data.results.length > 0) {
                    resultsList.innerHTML = data.results.map(r => `
                        <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
                             onclick="window.selectParticipantFromSearch('${this.escapeHtml(r.id)}')">
                            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                <span class="text-amber-600 font-bold">${this.escapeHtml((r.name || '?')[0].toUpperCase())}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-white truncate">${this.escapeHtml(r.name)}</div>
                                <div class="text-sm text-gray-500 truncate">${this.escapeHtml(r.school)}</div>
                            </div>
                            <div class="flex gap-1 flex-shrink-0">
                                ${r.checked_in ? '<span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">✓</span>' : ''}
                                ${r.merch_collected ? '<span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full">🎁</span>' : ''}
                            </div>
                        </div>
                    `).join('');

                    resultsContainer.classList.remove('hidden');
                    noResults.classList.add('hidden');
                } else {
                    resultsContainer.classList.add('hidden');
                    noResults.classList.remove('hidden');
                }
            } catch (err) {
                console.error('Search failed:', err);
                this.showToast('Search failed', 'error');
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        setupImageScanning() {
            const fileInput = document.getElementById('imageFileInput');
            if (!fileInput) return;

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.scanImageFile(file);
                    fileInput.value = '';
                }
            });
        },

        scanImageFile(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = Math.min(img.width, 1500);
                    canvas.height = Math.min(img.height, 1500);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, canvas.width, canvas.height, { inversionAttempts: 'attemptBoth' });
                    if (code && code.data) {
                        this.playBeep();
                        this.lookupById(code.data);
                    } else {
                        this.showToast('No QR code found in image', 'error');
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        setupLocationChange() {
            const btn = document.getElementById('changeLocationBtn');
            if (btn) btn.addEventListener('click', () => this.showLocationPicker = true);
        },

        // Recent scans
        loadRecentScans() {
            const stored = localStorage.getItem('orchestrator_recent_' + this.config.formId);
            if (stored) {
                try { this.recentScans = JSON.parse(stored); } catch {}
            }
        },

        addToRecentScans(action, regId, name) {
            this.recentScans.unshift({
                id: Date.now(),
                action,
                regId,
                name: name || regId,
                time: new Date().toISOString()
            });
            localStorage.setItem('orchestrator_recent_' + this.config.formId, JSON.stringify(this.recentScans));
        },

        // Utilities
        playBeep() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                oscillator.frequency.value = 1000;
                gainNode.gain.value = 0.3;
                oscillator.start();
                setTimeout(() => oscillator.stop(), 100);
            } catch {}
            if (navigator.vibrate) navigator.vibrate(100);
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 left-4 right-4 p-4 rounded-xl shadow-lg z-50 text-white font-bold text-center
                ${type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-gray-800'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 2500);
        },

        getActionClass(action) {
            const classes = {
                check_in: 'bg-green-100 dark:bg-green-900/30 text-green-600',
                verify_payment: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
                payment_verified: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
                deny_payment: 'bg-red-100 dark:bg-red-900/30 text-red-600',
                payment_denied: 'bg-red-100 dark:bg-red-900/30 text-red-600',
                give_merch: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
                merch_given: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
                log_location: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
                room_enter: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
                room_exit: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600'
            };
            return classes[action] || 'bg-gray-100 dark:bg-gray-700';
        },

        getActionIcon(action) {
            const icons = {
                check_in: '✓', verify_payment: '$', payment_verified: '$',
                deny_payment: '✗', payment_denied: '✗',
                give_merch: '🎁', merch_given: '🎁',
                log_location: '📍', room_enter: '→', room_exit: '←'
            };
            return icons[action] || '?';
        },

        getActionLabel(action) {
            const labels = {
                check_in: 'Checked in', verify_payment: 'Payment verified', payment_verified: 'Payment verified',
                deny_payment: 'Payment denied', payment_denied: 'Payment denied',
                give_merch: 'Merch given', merch_given: 'Merch given',
                log_location: 'Location logged', room_enter: 'Entered', room_exit: 'Exited'
            };
            return labels[action] || action;
        },

        formatTime(isoString) {
            const date = new Date(isoString);
            const now = new Date();
            const diff = (now - date) / 1000;
            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (date.toDateString() === now.toDateString()) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            return date.toLocaleDateString();
        },

        formatDateTime(isoString) {
            return new Date(isoString).toLocaleString();
        }
    };
}

// Global function for search result selection
window.selectParticipantFromSearch = function(regId) {
    document.getElementById('manualSearchResults').classList.add('hidden');
    document.getElementById('manualNoResults').classList.add('hidden');
    document.getElementById('manualIdInput').value = '';
    
    // Trigger Alpine method via Alpine.$data
    const container = document.querySelector('.scanner-container');
    if (container && Alpine) {
        Alpine.$data(container).lookupById(regId);
    }
};
