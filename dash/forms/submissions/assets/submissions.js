/**
 * Submissions Manager - Client-side module
 * Virtual scrolling, infinite scroll, CRUD operations
 */
const SubmissionsManager = {
    // Config
    config: null,
    CARD_HEIGHT_DESKTOP: 88,
    CARD_HEIGHT_MOBILE: 120,
    BUFFER_CARDS: 5,
    PAGE_SIZE: 50,
    SCROLL_THRESHOLD: 500,
    POLL_INTERVAL: 5000,
    DEBOUNCE_DELAY: 300,

    // State
    allData: [],
    displayFields: [],
    renderedRange: { start: 0, end: 0 },
    currentPage: 1,
    hasMore: true,
    totalCount: 0,
    isLoading: false,
    currentFilter: 'all',
    currentSort: 'newest',
    currentSearch: '',
    pollTimer: null,
    searchTimer: null,
    scrollTimer: null,
    deleteTargetId: null,

    // DOM refs
    els: {},

    init() {
        this.config = window.SUBMISSIONS_CONFIG;
        if (!this.config) return console.error('Missing SUBMISSIONS_CONFIG');

        this.cacheElements();
        this.loadPreferences();
        this.setupEventListeners();
        this.fetchStats();
        this.fetchPage(1, true);
        this.startPolling();
    },

    isMobile() {
        return window.innerWidth < 640;
    },

    getCardHeight() {
        return this.isMobile() ? this.CARD_HEIGHT_MOBILE : this.CARD_HEIGHT_DESKTOP;
    },

    cacheElements() {
        this.els = {
            container: document.getElementById('submissionContainer'),
            viewport: document.getElementById('virtualViewport'),
            content: document.getElementById('virtualContent'),
            list: document.getElementById('submissionList'),
            loading: document.getElementById('loadingState'),
            empty: document.getElementById('emptyState'),
            emptyMsg: document.getElementById('emptyMessage'),
            search: document.getElementById('searchInput'),
            clearSearch: document.getElementById('clearSearch'),
            sort: document.getElementById('sortSelect'),
            filters: document.getElementById('statusFilters'),
            statTotal: document.getElementById('stat-total'),
        };
    },

    loadPreferences() {
        const stored = localStorage.getItem(`sub_prefs_${this.config.formId}`);
        if (stored) {
            try {
                const prefs = JSON.parse(stored);
                this.currentSort = prefs.sort || 'newest';
                this.currentFilter = prefs.filter || 'all';
            } catch (e) { }
        }
        if (this.els.sort) this.els.sort.value = this.currentSort;
        this.updateFilterUI();
    },

    savePreferences() {
        localStorage.setItem(`sub_prefs_${this.config.formId}`, JSON.stringify({
            sort: this.currentSort,
            filter: this.currentFilter
        }));
    },

    setupEventListeners() {
        this.els.search?.addEventListener('input', () => {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => this.handleSearch(), this.DEBOUNCE_DELAY);
            this.els.clearSearch?.classList.toggle('hidden', !this.els.search.value);
        });
        this.els.clearSearch?.addEventListener('click', () => {
            this.els.search.value = '';
            this.els.clearSearch.classList.add('hidden');
            this.handleSearch();
        });

        this.els.sort?.addEventListener('change', () => {
            this.currentSort = this.els.sort.value;
            this.savePreferences();
            this.resetAndFetch();
        });

        this.els.filters?.querySelectorAll('.status-filter').forEach(tile => {
            tile.addEventListener('click', () => {
                this.currentFilter = tile.dataset.filterStatus;
                this.savePreferences();
                this.updateFilterUI();
                this.resetAndFetch();
            });
        });

        window.addEventListener('scroll', () => {
            cancelAnimationFrame(this.scrollTimer);
            this.scrollTimer = requestAnimationFrame(() => this.handleScroll());
        }, { passive: true });

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.renderedRange = { start: -1, end: -1 };
                this.updateVirtualHeight();
                this.renderVisible();
            }, 150);
        });

        document.getElementById('exportCsvBtn')?.addEventListener('click', () => this.exportCsv());
        document.getElementById('downloadAllBtn')?.addEventListener('click', () => this.downloadAll());
        document.getElementById('editSubForm')?.addEventListener('submit', (e) => { e.preventDefault(); this.submitEdit(); });
        document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => this.confirmDelete());
    },

    // API
    async api(action, params = {}, method = 'GET') {
        const url = new URL(this.config.apiUrl, window.location.origin);
        url.searchParams.set('form_id', this.config.formId);
        url.searchParams.set('action', action);

        const options = { method, priority: 'high' };
        if (method === 'GET') {
            Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        } else {
            const formData = new FormData();
            formData.append('form_id', this.config.formId);
            formData.append('action', action);
            formData.append('csrf_token', this.config.csrfToken);
            Object.entries(params).forEach(([k, v]) => formData.append(k, v));
            options.body = formData;
        }

        const res = await fetch(url, options);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    },

    async fetchStats() {
        try {
            const data = await this.api('stats');
            if (data.success) this.updateStats(data.total, data.statusCounts);
        } catch (e) {
            console.error('Failed to fetch stats:', e);
        }
    },

    async fetchPage(page, reset = false) {
        if (this.isLoading) return;
        if (!reset && !this.hasMore) return;

        this.isLoading = true;
        if (reset) {
            this.showLoading();
            this.allData = [];
            this.currentPage = 1;
            this.hasMore = true;
            this.renderedRange = { start: 0, end: 0 };
        }

        try {
            const data = await this.api('list', {
                page,
                per_page: this.PAGE_SIZE,
                search: this.currentSearch,
                status: this.currentFilter,
                sort: this.currentSort
            });

            if (data.success) {
                if (reset) this.allData = [];
                this.allData.push(...data.submissions);
                this.displayFields = data.displayFields || this.displayFields;
                this.hasMore = data.pagination.has_more;
                this.currentPage = data.pagination.page;
                this.totalCount = data.pagination.total;

                this.updateVirtualHeight();
                this.renderVisible();

                if (this.allData.length === 0) {
                    this.showEmpty();
                } else {
                    this.hideEmpty();
                }
            }
        } catch (e) {
            console.error('Failed to fetch:', e);
            this.showError('Failed to load submissions');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    },

    resetAndFetch() {
        window.scrollTo({ top: 0, behavior: 'instant' });
        this.fetchPage(1, true);
    },

    // Virtual Scroll Core
    updateVirtualHeight() {
        if (!this.els.content) return;
        const totalHeight = this.totalCount * this.getCardHeight();
        this.els.content.style.height = `${totalHeight}px`;
    },

    renderVisible() {
        if (!this.els.viewport || !this.els.list) return;

        const cardHeight = this.getCardHeight();
        const viewportRect = this.els.viewport.getBoundingClientRect();
        const scrollTop = Math.max(0, -viewportRect.top);
        const viewportHeight = window.innerHeight;

        const startIndex = Math.max(0, Math.floor(scrollTop / cardHeight) - this.BUFFER_CARDS);
        const endIndex = Math.min(
            this.allData.length,
            Math.ceil((scrollTop + viewportHeight) / cardHeight) + this.BUFFER_CARDS
        );

        if (startIndex === this.renderedRange.start && endIndex === this.renderedRange.end) {
            return;
        }

        this.renderedRange = { start: startIndex, end: endIndex };

        const fragment = document.createDocumentFragment();
        for (let i = startIndex; i < endIndex; i++) {
            const sub = this.allData[i];
            if (!sub) continue;
            const card = this.createCard(sub, i);
            fragment.appendChild(card);
        }

        this.els.list.innerHTML = '';
        this.els.list.appendChild(fragment);
    },

    handleScroll() {
        this.renderVisible();

        if (!this.hasMore || this.isLoading) return;

        const loadedHeight = this.allData.length * this.getCardHeight();
        const viewportRect = this.els.viewport.getBoundingClientRect();
        const scrollTop = Math.max(0, -viewportRect.top);

        if (scrollTop + window.innerHeight >= loadedHeight - this.SCROLL_THRESHOLD) {
            this.fetchPage(this.currentPage + 1);
        }
    },

    createCard(sub, index) {
        const cardHeight = this.getCardHeight();
        const card = document.createElement('div');
        card.className = 'submission-card absolute left-0 right-0 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden mx-0';
        card.style.transform = `translateY(${index * cardHeight}px)`;
        card.style.height = `${cardHeight - 8}px`;
        card.dataset.subId = sub.id;
        card.dataset.index = index;
        card.innerHTML = this.isMobile() ? this.cardHTMLMobile(sub) : this.cardHTMLDesktop(sub);
        this.attachCardListeners(card, sub);
        return card;
    },

    getStatusColor(status) {
        const colors = {
            'PENDING': 'bg-yellow-500',
            'VERIFIED': 'bg-green-500',
            'FAILED': 'bg-red-500',
        };
        return colors[status] || 'bg-gray-500';
    },

    getStatusLabel(status) {
        const labels = {
            'PENDING': 'Pending',
            'VERIFIED': 'Verified',
            'FAILED': 'Failed',
        };
        return labels[status] || status;
    },

    cardHTMLDesktop(sub) {
        const statusColor = this.getStatusColor(sub.status);
        const statusLabel = this.getStatusLabel(sub.status);
        const canApprove = sub.status !== 'VERIFIED';
        const canReject = sub.status !== 'FAILED';
        const displayName = sub.display_name || 'Unknown';
        const displayInfo = sub.display_info || '-';

        return `<div class="p-3 h-full flex items-center">
            <div class="flex gap-3 items-center flex-1 min-w-0">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">${this.esc(displayName)}</h3>
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                        <span class="truncate">${this.esc(displayInfo)}</span>
                        <span class="status-badge ${statusColor} text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold uppercase shrink-0">${this.esc(statusLabel)}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1 shrink-0 ml-2">
                <button data-action="approve" ${!canApprove ? 'disabled' : ''} class="p-1.5 rounded-lg ${canApprove ? 'bg-green-100 text-green-600 hover:bg-green-200 dark:bg-green-900/30' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
                <button data-action="reject" ${!canReject ? 'disabled' : ''} class="p-1.5 rounded-lg ${canReject ? 'bg-orange-100 text-orange-600 hover:bg-orange-200 dark:bg-orange-900/30' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <button data-action="delete" class="p-1.5 bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/30 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <button data-action="edit" class="p-1.5 bg-blue-100 text-blue-600 hover:bg-blue-200 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button data-action="view" class="p-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-400 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
        </div>`;
    },

    cardHTMLMobile(sub) {
        const statusColor = this.getStatusColor(sub.status);
        const statusLabel = this.getStatusLabel(sub.status);
        const canApprove = sub.status !== 'VERIFIED';
        const canReject = sub.status !== 'FAILED';
        const displayName = sub.display_name || 'Unknown';
        const displayInfo = sub.display_info || '-';

        return `<div class="p-3 h-full flex gap-3">
            <div class="flex-1 min-w-0 flex flex-col items-center justify-center text-center">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate w-full">${this.esc(displayName)}</h3>
                <div class="flex items-center justify-center gap-1 text-xs text-gray-500">
                    <span class="truncate">${this.esc(displayInfo)}</span>
                    <span class="status-badge ${statusColor} text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold uppercase shrink-0">${this.esc(statusLabel)}</span>
                </div>
                <div class="flex items-center justify-center gap-1 mt-2">
                    <button data-action="approve" ${!canApprove ? 'disabled' : ''} class="p-1.5 rounded-lg ${canApprove ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    <button data-action="reject" ${!canReject ? 'disabled' : ''} class="p-1.5 rounded-lg ${canReject ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <button data-action="delete" class="p-1.5 bg-red-100 text-red-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button data-action="edit" class="p-1.5 bg-blue-100 text-blue-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button data-action="view" class="p-1.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>
        </div>`;
    },

    attachCardListeners(card, sub) {
        card.querySelector('[data-action="approve"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.approve(sub.id); });
        card.querySelector('[data-action="reject"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.reject(sub.id); });
        card.querySelector('[data-action="delete"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.promptDelete(sub.id); });
        card.querySelector('[data-action="edit"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.openEditModal(sub.id); });
        card.querySelector('[data-action="view"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.openViewModal(sub.id); });
    },

    updateDataItem(id, updates) {
        const idx = this.allData.findIndex(s => s.id === id);
        if (idx !== -1) {
            this.allData[idx] = { ...this.allData[idx], ...updates };
            this.renderVisible();
        }
    },

    removeDataItem(id) {
        const idx = this.allData.findIndex(s => s.id === id);
        if (idx !== -1) {
            this.allData.splice(idx, 1);
            this.totalCount--;
            this.updateVirtualHeight();
            this.renderVisible();
        }
    },

    // CRUD Operations
    async approve(id) {
        if (!confirm('Approve this submission?')) return;
        try {
            const data = await this.api('approve', { id }, 'POST');
            if (data.success) {
                this.updateDataItem(id, data.submission);
                this.showToast('Submission approved');
            } else {
                alert(data.error || 'Failed to approve');
            }
        } catch (e) {
            alert('Failed to approve submission');
        }
    },

    async reject(id) {
        if (!confirm('Reject this submission?')) return;
        try {
            const data = await this.api('reject', { id }, 'POST');
            if (data.success) {
                this.updateDataItem(id, data.submission);
                this.showToast('Submission rejected');
            } else {
                alert(data.error || 'Failed to reject');
            }
        } catch (e) {
            alert('Failed to reject submission');
        }
    },

    promptDelete(id) {
        this.deleteTargetId = id;
        document.getElementById('deleteConfirmModal')?.classList.remove('hidden');
    },

    async confirmDelete() {
        if (!this.deleteTargetId) return;
        const id = this.deleteTargetId;
        this.closeModal('deleteConfirmModal');

        try {
            const data = await this.api('delete', { id }, 'POST');
            if (data.success) {
                this.removeDataItem(id);
                this.showToast('Submission deleted');
                if (this.allData.length === 0) this.showEmpty();
            } else {
                alert(data.error || 'Failed to delete');
            }
        } catch (e) {
            alert('Failed to delete submission');
        }
        this.deleteTargetId = null;
    },

    async submitEdit() {
        const form = document.getElementById('editSubForm');
        const id = document.getElementById('editSubId')?.value;
        if (!form || !id) return;

        const params = { id };
        new FormData(form).forEach((v, k) => { if (k !== 'id') params[k] = v; });

        try {
            const data = await this.api('edit', params, 'POST');
            if (data.success) {
                this.closeModal('editSubModal');
                this.updateDataItem(id, data.submission);
                this.showToast('Changes saved');
            } else {
                alert(data.error || 'Failed to save changes');
            }
        } catch (e) {
            alert('Failed to save changes');
        }
    },

    // Modals
    getLabel(key) {
        if (this.config.fieldLabels && this.config.fieldLabels[key]) {
            return this.config.fieldLabels[key];
        }
        return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    },

    async openEditModal(id) {
        try {
            const data = await this.api('get', { id });
            if (!data.success) return alert(data.error || 'Failed to load');

            const sub = data.submission;
            const container = document.getElementById('editSubFields');
            document.getElementById('editSubId').value = id;

            const excludedFields = ['registration_id', 'timestamp', 'form_id', 'context_type', 'context_id', 'slug', 'updated_at', 'status'];
            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">';

            Object.entries(sub).forEach(([key, value]) => {
                if (excludedFields.includes(key)) return;
                if (typeof value === 'object' && value !== null) return;
                const label = this.getLabel(key);
                html += `<div>
                    <label class="block text-xs text-gray-500 uppercase mb-1">${this.esc(label)}</label>
                    <input type="text" name="data_${this.esc(key)}" value="${this.esc(String(value || ''))}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm">
                </div>`;
            });

            html += `<div>
                <label class="block text-xs text-gray-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm">
                    <option value="PENDING" ${sub.status === 'PENDING' ? 'selected' : ''}>Pending</option>
                    <option value="VERIFIED" ${sub.status === 'VERIFIED' ? 'selected' : ''}>Verified</option>
                    <option value="FAILED" ${sub.status === 'FAILED' ? 'selected' : ''}>Failed</option>
                </select>
            </div>`;
            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Created</label><div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm">${this.esc(sub.created_at || sub.timestamp || '-')}</div></div>`;
            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Submission ID</label><div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-mono truncate">${this.esc(id)}</div></div>`;
            html += '</div>';

            container.innerHTML = html;
            document.getElementById('editSubModal')?.classList.remove('hidden');
        } catch (e) {
            alert('Failed to load submission');
        }
    },

    getFileUrl(relPath) {
        if (!relPath) return '';
        return `${this.config.apiUrl}?action=file&form_id=${this.config.formId}&path=${encodeURIComponent(relPath)}`;
    },

    getFileName(relPath) {
        if (!relPath) return '';
        return relPath.split('/').pop();
    },

    isImageFile(path) {
        return /\.(jpg|jpeg|png|webp)$/i.test(path);
    },

    isFilePath(value) {
        return typeof value === 'string' && value && (
            value.startsWith('uploads/') ||
            value.includes('/data/submissions/uploads/')
        );
    },

    async downloadSubmissionFiles(subId) {
        try {
            const data = await this.api('get', { id: subId });
            if (!data.success) return alert('Failed to load submission');

            const sub = data.submission;
            const fileFields = [];
            Object.entries(sub).forEach(([key, value]) => {
                if (this.isFilePath(value)) {
                    fileFields.push({ key, value });
                }
            });

            if (fileFields.length === 0) return alert('No files to download');

            for (const { key, value } of fileFields) {
                const fileUrl = this.getFileUrl(value);
                const fileName = this.getFileName(value);

                const a = document.createElement('a');
                a.href = fileUrl;
                a.download = `${subId}_${fileName}`;
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                await new Promise(r => setTimeout(r, 500));
            }

            this.showToast(`Downloaded ${fileFields.length} files!`);
        } catch (e) {
            alert('Failed to download files');
        }
    },

    async openViewModal(id) {
        try {
            const data = await this.api('get', { id });
            if (!data.success) return alert(data.error || 'Failed to load');

            const sub = data.submission;
            const container = document.getElementById('viewSubContent');

            const excludedFields = ['registration_id', 'timestamp', 'form_id', 'context_type', 'context_id', 'slug', 'updated_at'];

            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">';
            Object.entries(sub).forEach(([key, value]) => {
                if (excludedFields.includes(key)) return;
                if (typeof value === 'object' && value !== null) return;
                // Skip file paths in the grid - we'll show them below
                if (this.isFilePath(value)) return;
                const label = this.getLabel(key);
                const val = Array.isArray(value) ? JSON.stringify(value) : (value || '-');
                html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">${this.esc(label)}</label><div class="font-medium text-sm">${this.esc(String(val))}</div></div>`;
            });

            html += '</div>';

            // Find and show all file fields
            const fileFields = [];
            Object.entries(sub).forEach(([key, value]) => {
                if (this.isFilePath(value)) {
                    fileFields.push({ key, value });
                }
            });

            if (fileFields.length > 0) {
                html += '<div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"><div class="flex items-center justify-between mb-3"><h4 class="text-sm font-bold">Files</h4>';
                html += `<button onclick="SubmissionsManager.downloadSubmissionFiles('${this.esc(id)}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download All (${fileFields.length})
                </button>`;
                html += '</div><div class="flex flex-wrap gap-4">';
                fileFields.forEach(({ key, value }) => {
                    const label = this.getLabel(key);
                    const fileUrl = this.getFileUrl(value);
                    const fileName = this.getFileName(value);
                    const isImage = this.isImageFile(value);
                    if (isImage) {
                        html += `<div class="text-center">
                            <span class="text-xs text-gray-500 block mb-1">${this.esc(label)}</span>
                            <a href="${this.esc(fileUrl)}" target="_blank">
                                <img src="${this.esc(fileUrl)}" class="h-32 rounded border border-gray-200 hover:border-blue-500 transition-colors">
                            </a>
                            <a href="${this.esc(fileUrl)}" download="${this.esc(fileName)}" class="mt-1 inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                ${this.esc(fileName)}
                            </a>
                        </div>`;
                    } else {
                        html += `<div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-center">
                            <span class="text-xs text-gray-500 block mb-1">${this.esc(label)}</span>
                            <svg class="w-8 h-8 mx-auto text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <a href="${this.esc(fileUrl)}" download="${this.esc(fileName)}" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                ${this.esc(fileName)}
                            </a>
                        </div>`;
                    }
                });
                html += '</div></div>';
            }

            container.innerHTML = html;
            document.getElementById('viewSubModal')?.classList.remove('hidden');
        } catch (e) {
            alert('Failed to load submission');
        }
    },

    closeModal(id) {
        document.getElementById(id)?.classList.add('hidden');
    },

    showToast(message) {
        const existing = document.getElementById('toast-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 z-[100] animate-fade-in';
        toast.innerHTML = `
            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>${this.esc(message)}</span>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    },

    exportCsv() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = this.config.apiUrl;
        form.innerHTML = `<input type="hidden" name="form_id" value="${this.config.formId}"><input type="hidden" name="action" value="export_csv"><input type="hidden" name="csrf_token" value="${this.config.csrfToken}">`;
        document.body.appendChild(form);
        form.submit();
        form.remove();
    },

    async downloadAll() {
        if (!confirm('Download all files from all submissions? This may take a while.')) return;

        try {
            // Fetch all submissions (first page with large per_page)
            const data = await this.api('list', { page: 1, per_page: 1000, status: 'all', sort: 'newest' });
            if (!data.success) return alert('Failed to load submissions');

            const submissions = data.submissions;
            let totalFiles = 0;
            let downloaded = 0;

            // Count total files first
            for (const sub of submissions) {
                const detail = await this.api('get', { id: sub.id });
                if (detail.success) {
                    const subData = detail.submission;
                    Object.values(subData).forEach(val => {
                        if (this.isFilePath(val)) totalFiles++;
                    });
                }
            }

            if (totalFiles === 0) return alert('No files to download');

            if (!confirm(`Found ${totalFiles} files. Start downloading?`)) return;

            // Download each file
            for (const sub of submissions) {
                const detail = await this.api('get', { id: sub.id });
                if (!detail.success) continue;

                const subData = detail.submission;
                for (const [key, val] of Object.entries(subData)) {
                    if (this.isFilePath(val)) {
                        const fileUrl = this.getFileUrl(val);
                        const fileName = this.getFileName(val);
                        const subId = sub.id || 'unknown';

                        // Create download link and trigger it
                        const a = document.createElement('a');
                        a.href = fileUrl;
                        a.download = `${subId}_${fileName}`;
                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);

                        downloaded++;
                        this.showToast(`Downloading ${downloaded}/${totalFiles}...`);

                        // Small delay between downloads to avoid browser blocking
                        await new Promise(r => setTimeout(r, 500));
                    }
                }
            }

            this.showToast(`Downloaded ${downloaded} files!`);
        } catch (e) {
            alert('Failed to download files: ' + e.message);
        }
    },

    // UI Helpers
    handleSearch() {
        this.currentSearch = this.els.search?.value.trim() || '';
        this.resetAndFetch();
    },

    updateFilterUI() {
        this.els.filters?.querySelectorAll('.status-filter').forEach(tile => {
            const active = tile.dataset.filterStatus === this.currentFilter;
            tile.classList.toggle('active', active);
            tile.classList.toggle('border-blue-500', active);
            tile.classList.toggle('border-transparent', !active);
        });
    },

    updateStats(total, counts) {
        if (total !== null && this.els.statTotal) this.els.statTotal.textContent = total;
        if (counts) {
            Object.entries(counts).forEach(([s, c]) => {
                const el = document.getElementById(`stat-${s.toLowerCase()}`);
                if (el) el.textContent = c;
            });
            ['pending', 'verified', 'failed'].forEach(s => {
                if (!(s.toUpperCase() in counts) && !(s in counts)) {
                    const el = document.getElementById(`stat-${s}`);
                    if (el) el.textContent = '0';
                }
            });
        }
    },

    showLoading() { this.els.loading?.classList.remove('hidden'); },
    hideLoading() { this.els.loading?.classList.add('hidden'); },
    showEmpty() { this.els.empty?.classList.remove('hidden'); if (this.els.list) this.els.list.innerHTML = ''; if (this.els.emptyMsg) this.els.emptyMsg.textContent = this.currentSearch ? 'No results found.' : this.currentFilter !== 'all' ? 'No submissions with this status.' : 'No submissions yet.'; },
    hideEmpty() { this.els.empty?.classList.add('hidden'); },
    showError(msg) { if (this.els.list) this.els.list.innerHTML = `<div class="text-center py-8 text-red-500">${this.esc(msg)}</div>`; },

    // Polling
    startPolling() {
        this.pollTimer = setInterval(() => this.poll(), this.POLL_INTERVAL);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) clearInterval(this.pollTimer);
            else { this.poll(); this.pollTimer = setInterval(() => this.poll(), this.POLL_INTERVAL); }
        });
    },

    async poll() {
        if (document.hidden || this.isModalOpen()) return;
        try {
            const data = await this.api('stats');
            if (data.success) this.updateStats(data.total, data.statusCounts);
        } catch (e) { }
    },

    isModalOpen() {
        return ['editSubModal', 'viewSubModal', 'deleteConfirmModal'].some(id => !document.getElementById(id)?.classList.contains('hidden'));
    },

    esc(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }
};

document.addEventListener('DOMContentLoaded', () => SubmissionsManager.init());
