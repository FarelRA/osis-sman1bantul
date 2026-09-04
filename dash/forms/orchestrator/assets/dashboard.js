/**
 * Orchestrator Dashboard JavaScript
 * Handles real-time polling, search, export, and broadcast
 */

(function () {
    'use strict';

    const config = window.ORCHESTRATOR_CONFIG;
    const POLL_INTERVAL = 5000; // 5 seconds
    let pollTimer = null;
    let lastEventTimestamp = null;
    let isVisible = true;

    // Initialize
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        setupEventFilter();
        setupVisibilityHandling();
        setupSearch();
        setupExport();
        setupBroadcast();
        setupRefresh();
        setupModals();
        setupLocationCards();
        startPolling();
    }

    // ===== Visibility Handling =====
    function setupVisibilityHandling() {
        document.addEventListener('visibilitychange', () => {
            isVisible = !document.hidden;
            if (isVisible) {
                fetchStats();
                fetchEvents();
            }
        });
    }

    // ===== Event Filter =====
    function setupEventFilter() {
        const filter = document.getElementById('eventFilter');
        const adminSearch = document.getElementById('adminSearch');
        
        if (filter) {
            filter.addEventListener('change', () => {
                filterEvents(filter.value, adminSearch?.value);
            });
        }
        
        if (adminSearch) {
            adminSearch.addEventListener('input', () => {
                filterEvents(filter?.value, adminSearch.value);
            });
        }
    }

    function filterEvents(type, adminQuery) {
        const rows = document.querySelectorAll('.event-row');
        const query = adminQuery?.toLowerCase().trim() || '';
        
        rows.forEach(row => {
            const matchesType = !type || row.dataset.eventType === type;
            const matchesAdmin = !query || row.dataset.operator.includes(query);
            
            row.style.display = (matchesType && matchesAdmin) ? '' : 'none';
        });
    }

    // ===== Polling =====
    function startPolling() {
        fetchStats();
        fetchEvents();

        pollTimer = setInterval(() => {
            if (isVisible) {
                fetchStats();
                fetchEvents();
            }
        }, POLL_INTERVAL);
    }

    async function fetchStats() {
        try {
            const response = await fetch(
                `${config.apiUrl}?action=get_stats&form_id=${encodeURIComponent(config.formId)}`
            );
            const data = await response.json();

            if (data.success) {
                updateStats(data.stats);
                updateLocations(data.stats.locations || {});
                updateLastUpdated();
            }
        } catch (err) {
            console.error('Failed to fetch stats:', err);
        }
    }

    async function fetchEvents() {
        try {
            const url = new URL(config.apiUrl, window.location.origin);
            url.searchParams.set('action', 'get_events');
            url.searchParams.set('form_id', config.formId);

            const response = await fetch(url);
            const data = await response.json();

            if (data.success && data.events) {
                if (data.events.length > 0) {
                    const latestTimestamp = data.events[0].timestamp;
                    if (lastEventTimestamp && latestTimestamp !== lastEventTimestamp) {
                        playNotification();
                    }
                    lastEventTimestamp = latestTimestamp;
                }

                updateEventLog(data.events);
            }
        } catch (err) {
            console.error('Failed to fetch events:', err);
        }
    }

    // ===== Search =====
    function setupSearch() {
        const input = document.getElementById('participantSearch');
        const btn = document.getElementById('searchParticipantBtn');

        if (btn) {
            btn.addEventListener('click', () => searchParticipants(input?.value));
        }

        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchParticipants(input.value);
                }
            });
        }
    }

    async function searchParticipants(query) {
        if (!query || query.trim().length < 2) {
            showToast('Enter at least 2 characters', 'error');
            return;
        }

        const resultsContainer = document.getElementById('searchResults');
        const resultsList = document.getElementById('searchResultsList');
        const noResults = document.getElementById('noSearchResults');

        try {
            const response = await fetch(config.apiUrl + '?action=search_participants', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: config.csrfToken,
                    form_id: config.formId,
                    query: query.trim()
                })
            });

            const data = await response.json();

            if (data.success && data.results.length > 0) {
                resultsList.innerHTML = data.results.map(r => `
                    <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                         onclick="window.showParticipantDetail('${escapeHtml(r.id)}')">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <span class="text-amber-600 font-bold">${escapeHtml((r.name || '?')[0].toUpperCase())}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 dark:text-white truncate">${escapeHtml(r.name)}</div>
                            <div class="text-sm text-gray-500 truncate">${escapeHtml(r.school)}</div>
                        </div>
                        <div class="flex gap-1">
                            ${r.checked_in ? '<span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">✓ In</span>' : ''}
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
            showToast('Search failed', 'error');
        }
    }

    // Global function to show participant detail
    window.showParticipantDetail = async function (regId) {
        const modal = document.getElementById('participantModal');
        const content = document.getElementById('participantModalContent');

        try {
            const response = await fetch(config.apiUrl + '?action=get_participant', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: config.csrfToken,
                    form_id: config.formId,
                    reg_id: regId
                })
            });

            const data = await response.json();

            if (data.success) {
                const reg = data.registration;
                const events = data.events || [];

                content.innerHTML = `
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                <span class="text-2xl text-amber-600 font-bold">${escapeHtml((reg.data?.full_name || '?')[0].toUpperCase())}</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">${escapeHtml(reg.data?.full_name || 'Unknown')}</h3>
                                <p class="text-sm text-gray-500 font-mono">${escapeHtml(reg.id)}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <div class="text-xs text-gray-500">Status</div>
                                <div class="font-bold text-gray-900 dark:text-white">${escapeHtml(reg.status || '-')}</div>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <div class="text-xs text-gray-500">School</div>
                                <div class="font-bold text-gray-900 dark:text-white truncate">${escapeHtml(reg.data?.school_origin || '-')}</div>
                            </div>
                            <div class="p-3 ${data.checked_in ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-900/50'} rounded-lg">
                                <div class="text-xs text-gray-500">Checked In</div>
                                <div class="font-bold ${data.checked_in ? 'text-green-600' : 'text-gray-900 dark:text-white'}">${data.checked_in ? 'Yes ✓' : 'No'}</div>
                            </div>
                            <div class="p-3 ${data.merch_collected ? 'bg-purple-50 dark:bg-purple-900/20' : 'bg-gray-50 dark:bg-gray-900/50'} rounded-lg">
                                <div class="text-xs text-gray-500">Merch</div>
                                <div class="font-bold ${data.merch_collected ? 'text-purple-600' : 'text-gray-900 dark:text-white'}">${data.merch_collected ? 'Collected ✓' : 'Not collected'}</div>
                            </div>
                        </div>
                        
                        ${events.length > 0 ? `
                        <div>
                            <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-2">Activity History (${events.length} events)</h4>
                            <div class="max-h-64 overflow-y-auto border border-gray-100 dark:border-gray-700 rounded-lg">
                                ${events.map(e => {
                    const isLocation = ['room_enter', 'room_exit', 'check_in', 'merch_given'].includes(e.type) && e.location;
                    const bgClass = isLocation ? 'bg-blue-50 dark:bg-blue-900/10' : '';
                    return `
                                    <div class="px-3 py-2 flex items-center gap-2 text-sm border-b border-gray-100 dark:border-gray-700 last:border-0 ${bgClass}">
                                        <span class="text-gray-400 text-xs flex-shrink-0 w-12">${new Date(e.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                        <span class="text-gray-900 dark:text-white flex-shrink-0">${escapeHtml(formatEventType(e.type))}</span>
                                        ${e.location ? `<span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full">📍 ${escapeHtml(e.location)}</span>` : ''}
                                        <span class="text-xs text-gray-400 ml-auto truncate max-w-20">${escapeHtml(e.operator || '')}</span>
                                    </div>
                                `}).join('')}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;

                modal.classList.remove('hidden');
            }
        } catch (err) {
            console.error('Failed to load participant:', err);
            showToast('Failed to load participant details', 'error');
        }
    };

    // ===== Export =====
    function setupExport() {
        const exportEventsBtn = document.getElementById('exportEventsBtn');
        const exportAttendeesBtn = document.getElementById('exportAttendeesBtn');

        if (exportEventsBtn) {
            exportEventsBtn.addEventListener('click', () => exportData('export_events'));
        }

        if (exportAttendeesBtn) {
            exportAttendeesBtn.addEventListener('click', () => exportData('export_attendees'));
        }
    }

    async function exportData(action) {
        try {
            showToast('Generating export...', 'info');

            const response = await fetch(config.apiUrl + '?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: config.csrfToken,
                    form_id: config.formId
                })
            });

            const data = await response.json();

            if (data.success && data.csv) {
                // Create and download file
                const blob = new Blob([data.csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = data.filename || 'export.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                showToast(`Exported ${data.count || 'data'} successfully!`, 'success');
            } else {
                showToast(data.error || 'Export failed', 'error');
            }
        } catch (err) {
            console.error('Export failed:', err);
            showToast('Export failed', 'error');
        }
    }

    // ===== Broadcast =====
    function setupBroadcast() {
        const broadcastBtn = document.getElementById('broadcastBtn');
        const cancelBtn = document.getElementById('cancelBroadcast');
        const sendBtn = document.getElementById('sendBroadcast');
        const modal = document.getElementById('broadcastModal');

        if (broadcastBtn) {
            broadcastBtn.addEventListener('click', () => {
                modal?.classList.remove('hidden');
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                modal?.classList.add('hidden');
                document.getElementById('broadcastMessage').value = '';
            });
        }

        if (sendBtn) {
            sendBtn.addEventListener('click', sendBroadcast);
        }
    }

    async function sendBroadcast() {
        const message = document.getElementById('broadcastMessage')?.value?.trim();
        const modal = document.getElementById('broadcastModal');

        if (!message) {
            showToast('Please enter a message', 'error');
            return;
        }

        try {
            const response = await fetch(config.apiUrl + '?action=broadcast', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: config.csrfToken,
                    form_id: config.formId,
                    message: message
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Broadcast sent!', 'success');
                modal?.classList.add('hidden');
                document.getElementById('broadcastMessage').value = '';
                fetchEvents(); // Refresh to show broadcast in log
            } else {
                showToast(data.error || 'Failed to send broadcast', 'error');
            }
        } catch (err) {
            console.error('Broadcast failed:', err);
            showToast('Failed to send broadcast', 'error');
        }
    }

    // ===== Refresh =====
    function setupRefresh() {
        const refreshBtn = document.getElementById('refreshDataBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                fetchStats();
                fetchEvents();
                showToast('Data refreshed', 'success');
            });
        }
    }

    // ===== Modals =====
    function setupModals() {
        const participantModal = document.getElementById('participantModal');
        const closeParticipantBtn = document.getElementById('closeParticipantModal');

        if (closeParticipantBtn) {
            closeParticipantBtn.addEventListener('click', () => {
                participantModal?.classList.add('hidden');
            });
        }

        // Close modals on outside click
        [participantModal, document.getElementById('broadcastModal')].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                    }
                });
            }
        });
    }

    // ===== UI Updates =====
    function updateStats(stats) {
        const mappings = {
            'stat-verified': stats.verified_registrations ?? 0,
            'stat-checked-in': stats.checked_in ?? 0,
            'stat-merch': stats.merch_distributed ?? 0,
            'stat-events': stats.total_events ?? 0
        };

        Object.entries(mappings).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) {
                const current = parseInt(el.textContent) || 0;
                if (current !== value) {
                    el.textContent = value;
                    el.classList.add('scale-110');
                    setTimeout(() => el.classList.remove('scale-110'), 200);
                }
            }
        });
    }

    function updateLocations(locations) {
        const grid = document.getElementById('locationGrid');
        if (!grid) return;

        grid.querySelectorAll('[data-location]').forEach(el => {
            const loc = el.dataset.location;
            const count = locations[loc] ?? 0;
            const countEl = el.querySelector('.location-count');
            if (countEl) {
                const current = parseInt(countEl.textContent) || 0;
                if (current !== count) {
                    countEl.textContent = count;
                    countEl.classList.add('text-amber-600');
                    setTimeout(() => countEl.classList.remove('text-amber-600'), 500);
                }
            }
        });
    }

    function updateEventLog(events) {
        const container = document.getElementById('eventLog');
        if (!container || events.length === 0) return;

        const currentFilter = document.getElementById('eventFilter')?.value || '';

        const eventIcons = {
            check_in: { icon: '✓', bg: 'bg-green-100 text-green-600' },
            payment_verified: { icon: '💰', bg: 'bg-emerald-100 text-emerald-600' },
            payment_denied: { icon: '✗', bg: 'bg-red-100 text-red-600' },
            merch_given: { icon: '🎁', bg: 'bg-purple-100 text-purple-600' },
            room_enter: { icon: '→', bg: 'bg-blue-100 text-blue-600' },
            room_exit: { icon: '←', bg: 'bg-orange-100 text-orange-600' },
            broadcast: { icon: '📢', bg: 'bg-red-100 text-red-600' }
        };

        container.innerHTML = events.map(event => {
            const { icon, bg } = eventIcons[event.type] || { icon: '•', bg: 'bg-gray-100 text-gray-600' };
            const time = new Date(event.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const display = (!currentFilter || event.type === currentFilter) ? '' : 'display: none;';

            // Special handling for broadcast events
            if (event.type === 'broadcast') {
                return `
                    <div class="px-4 py-3 flex items-center gap-3 event-row bg-red-50 dark:bg-red-900/10" data-event-type="broadcast" style="${display}">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg ${bg}">
                            ${icon}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-red-600 dark:text-red-400">Emergency Broadcast</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300">${escapeHtml(event.data?.message || '')}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-400">${time}</div>
                            <div class="text-xs text-gray-400">${escapeHtml(event.operator)}</div>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="px-4 py-3 flex items-center gap-3 event-row" data-event-type="${escapeHtml(event.type)}" style="${display}">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg ${bg}">
                        ${icon}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 dark:text-white truncate">${escapeHtml(event.participant_name || event.reg_id)}</div>
                        <div class="text-sm text-gray-500 flex items-center gap-2">
                            <span>${escapeHtml(formatEventType(event.type))}</span>
                            ${event.location ? `<span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">${escapeHtml(event.location)}</span>` : ''}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-400">${time}</div>
                        <div class="text-xs text-gray-400">${escapeHtml(event.operator)}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateLastUpdated() {
        const el = document.getElementById('lastUpdate');
        if (el) {
            el.textContent = 'Just now';
        }
    }

    // ===== Location Cards Click Handler =====
    function setupLocationCards() {
        const grid = document.getElementById('locationGrid');
        if (!grid) return;

        grid.addEventListener('click', async (e) => {
            const card = e.target.closest('[data-location]');
            if (!card) return;

            const location = card.dataset.location;
            const count = parseInt(card.querySelector('.location-count')?.textContent) || 0;

            if (count === 0) {
                showToast(`No participants at ${location}`, 'info');
                return;
            }

            await showLocationParticipants(location);
        });
    }

    async function showLocationParticipants(location) {
        const modal = document.getElementById('participantModal');
        const content = document.getElementById('participantModalContent');

        // Update modal title
        const modalTitle = modal.querySelector('h3');
        if (modalTitle) {
            modalTitle.textContent = `Participants at ${location}`;
        }

        content.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin w-8 h-8 border-4 border-amber-500 border-t-transparent rounded-full"></div>
            </div>
        `;
        modal.classList.remove('hidden');

        try {
            const response = await fetch(config.apiUrl + '?action=get_participants_at_location', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: config.csrfToken,
                    form_id: config.formId,
                    location: location
                })
            });

            const data = await response.json();

            if (data.success) {
                if (data.participants.length === 0) {
                    content.innerHTML = `
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-500">No participants currently at this location</p>
                        </div>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="mb-4">
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <span>${escapeHtml(location)}</span>
                                <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-xs font-bold">${data.count} participants</span>
                            </div>
                        </div>
                        <div class="max-h-80 overflow-y-auto border border-gray-100 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                            ${data.participants.map(p => `
                                <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                                     onclick="window.showParticipantDetail('${escapeHtml(p.id)}')">
                                    <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                        <span class="text-amber-600 font-bold">${escapeHtml((p.name || '?')[0].toUpperCase())}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-white truncate">${escapeHtml(p.name)}</div>
                                        <div class="text-sm text-gray-500 truncate">${escapeHtml(p.school)}</div>
                                    </div>
                                    <div class="flex gap-1 flex-shrink-0">
                                        ${p.checked_in ? '<span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full">✓ In</span>' : ''}
                                        ${p.merch_collected ? '<span class="px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full">🎁</span>' : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
            } else {
                content.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        ${escapeHtml(data.error || 'Failed to load participants')}
                    </div>
                `;
            }
        } catch (err) {
            console.error('Failed to load location participants:', err);
            content.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    Failed to load participants
                </div>
            `;
        }
    }

    // ===== Utilities =====
    function formatEventType(type) {
        return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 p-4 rounded-xl shadow-lg z-50 text-white font-bold text-center
            ${type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-gray-800'}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    function playNotification() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.value = 0.1;

            oscillator.start();
            setTimeout(() => oscillator.stop(), 50);
        } catch { }
    }
})();

