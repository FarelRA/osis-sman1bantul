/**
 * Registrations Manager - Client-side module
 * TRUE Virtual scrolling, infinite scroll, lazy images, CRUD operations
 */
const RegistrationsManager = {
    // Config
    config: null,
    CARD_HEIGHT_DESKTOP: 88,
    CARD_HEIGHT_MOBILE: 130,
    CARD_HEIGHT_MOBILE_DUP: 150,
    BUFFER_CARDS: 5,
    PAGE_SIZE: 50,
    SCROLL_THRESHOLD: 500,
    POLL_INTERVAL: 3000,
    DEBOUNCE_DELAY: 300,

    // State
    allData: [],
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
    viewRegId: null,
    imageObserver: null,

    // DOM refs
    els: {},

    init() {
        this.config = window.REGISTRATIONS_CONFIG;
        if (!this.config) return console.error('Missing REGISTRATIONS_CONFIG');

        this.cacheElements();
        this.loadPreferences();
        this.setupEventListeners();
        this.setupImageObserver();
        this.fetchStats();
        this.fetchPage(1, true);
        this.startPolling();
    },

    isMobile() {
        return window.innerWidth < 640;
    },

    getCardHeight() {
        if (this.isMobile()) {
            return this.currentFilter === 'duplicate' ? this.CARD_HEIGHT_MOBILE_DUP : this.CARD_HEIGHT_MOBILE;
        }
        return this.CARD_HEIGHT_DESKTOP;
    },

    cacheElements() {
        this.els = {
            container: document.getElementById('registrationContainer'),
            viewport: document.getElementById('virtualViewport'),
            content: document.getElementById('virtualContent'),
            list: document.getElementById('registrationList'),
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
        const stored = localStorage.getItem(`reg_prefs_${this.config.formId}`);
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
        localStorage.setItem(`reg_prefs_${this.config.formId}`, JSON.stringify({
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

        document.getElementById('addRegBtn')?.addEventListener('click', () => this.openAddModal());
        document.getElementById('exportCsvBtn')?.addEventListener('click', () => this.exportCsv());
        document.getElementById('addRegForm')?.addEventListener('submit', (e) => { e.preventDefault(); this.submitAdd(); });
        document.getElementById('editRegForm')?.addEventListener('submit', (e) => { e.preventDefault(); this.submitEdit(); });
        document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => this.confirmDelete());
    },

    setupImageObserver() {
        if (!('IntersectionObserver' in window)) return;
        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.remove('lazy-image');
                        this.imageObserver.unobserve(img);
                    }
                }
            });
        }, { rootMargin: '200px' });
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
            if (data.success) this.updateStats(data.total, data.statusCounts, data.duplicateCount);
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
                this.allData.push(...data.registrations);
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
            this.showError('Failed to load registrations');
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
            const reg = this.allData[i];
            if (!reg) continue;
            const card = this.createCard(reg, i);
            fragment.appendChild(card);
        }

        this.els.list.innerHTML = '';
        this.els.list.appendChild(fragment);
        this.observeLazyImages();
    },

    handleScroll() {
        this.renderVisible();

        if (!this.hasMore || this.isLoading) return;

        // Trigger fetch when approaching end of loaded data
        const loadedHeight = this.allData.length * this.getCardHeight();
        const viewportRect = this.els.viewport.getBoundingClientRect();
        const scrollTop = Math.max(0, -viewportRect.top);

        if (scrollTop + window.innerHeight >= loadedHeight - this.SCROLL_THRESHOLD) {
            this.fetchPage(this.currentPage + 1);
        }
    },

    createCard(reg, index) {
        const cardHeight = this.getCardHeight();
        const card = document.createElement('div');
        card.className = 'registration-card absolute left-0 right-0 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden mx-0';
        card.style.transform = `translateY(${index * cardHeight}px)`;
        card.style.height = `${cardHeight - 8}px`;
        card.dataset.regId = reg.id;
        card.dataset.index = index;
        card.innerHTML = this.isMobile() ? this.cardHTMLMobile(reg) : this.cardHTMLDesktop(reg);
        this.attachCardListeners(card, reg);
        return card;
    },

    cardHTMLDesktop(reg) {
        const canApprove = reg.status !== 'verified';
        const canReject = !['rejected', 'expired'].includes(reg.status);
        const classPill = reg.assigned_class ? `<span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-[10px] shrink-0">${this.esc(reg.assigned_class)}</span>` : '';
        const gatePill = reg.assigned_gate ? `<span class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-[10px] shrink-0">${this.esc(reg.assigned_gate)}</span>` : '';
        const dupPills = (reg.duplicate_fields || []).map(f => `<span class="px-1.5 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-full text-[10px] shrink-0">${this.esc(f.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()))}</span>`).join('');
        const imgPlaceholder = '<div class="w-10 h-10 rounded-full border-2 border-white dark:border-gray-800 bg-gray-200 dark:bg-gray-700"></div>';

        return `<div class="p-3 h-full flex items-center">
            <div class="flex gap-3 items-center flex-1 min-w-0">
                <div class="flex -space-x-2 shrink-0">
                    ${reg.student_id_url ? `<img data-src="${this.esc(reg.student_id_url)}" loading="lazy" fetchpriority="low" class="lazy-image w-10 h-10 rounded-full border-2 border-white dark:border-gray-800 object-cover bg-gray-200" alt="">` : imgPlaceholder}
                    ${reg.payment_url ? `<img data-src="${this.esc(reg.payment_url)}" loading="lazy" fetchpriority="low" class="lazy-image w-10 h-10 rounded-full border-2 border-white dark:border-gray-800 object-cover bg-gray-200" alt="">` : imgPlaceholder}
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">${this.esc(reg.full_name)}</h3>
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                        <span class="truncate">${this.esc(reg.school_origin)}</span>
                        <span class="status-badge ${reg.status_color} text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold uppercase shrink-0">${this.esc(reg.status_label)}</span>
                        ${classPill}${gatePill}${dupPills}
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1 shrink-0 ml-2">
                <button data-action="approve" ${!canApprove ? 'disabled' : ''} class="p-1.5 rounded-lg ${canApprove ? 'bg-green-100 text-green-600 hover:bg-green-200 dark:bg-green-900/30' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
                <button data-action="share" class="p-1.5 bg-teal-100 text-teal-600 hover:bg-teal-200 dark:bg-teal-900/30 rounded-lg" title="Share restore link">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
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

    cardHTMLMobile(reg) {
        const canApprove = reg.status !== 'verified';
        const canReject = !['rejected', 'expired'].includes(reg.status);
        const classPill = reg.assigned_class ? `<span class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-[10px] shrink-0">${this.esc(reg.assigned_class)}</span>` : '';
        const gatePill = reg.assigned_gate ? `<span class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-[10px] shrink-0">${this.esc(reg.assigned_gate)}</span>` : '';
        const dupPills = (reg.duplicate_fields || []).map(f => `<span class="px-1.5 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-full text-[10px] shrink-0">${this.esc(f.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()))}</span>`).join('');
        const dupRow = dupPills ? `<div class="flex items-center justify-center gap-1 mt-1">${dupPills}</div>` : '';
        const imgPlaceholder = '<div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 border-2 border-white dark:border-gray-800"></div>';

        return `<div class="p-3 h-full flex gap-3">
            <div class="flex flex-col gap-1 shrink-0 justify-center">
                ${reg.student_id_url ? `<img data-src="${this.esc(reg.student_id_url)}" loading="lazy" fetchpriority="low" class="lazy-image w-12 h-12 rounded-full border-2 border-white dark:border-gray-800 object-cover bg-gray-200" alt="">` : imgPlaceholder}
                ${reg.payment_url ? `<img data-src="${this.esc(reg.payment_url)}" loading="lazy" fetchpriority="low" class="lazy-image w-12 h-12 rounded-full border-2 border-white dark:border-gray-800 object-cover bg-gray-200" alt="">` : imgPlaceholder}
            </div>
            <div class="flex-1 min-w-0 flex flex-col items-center justify-center text-center">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate w-full">${this.esc(reg.full_name)}</h3>
                <div class="flex items-center justify-center gap-1 text-xs text-gray-500">
                    <span class="truncate">${this.esc(reg.school_origin)}</span>
                    <span class="status-badge ${reg.status_color} text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold uppercase shrink-0">${this.esc(reg.status_label)}</span>
                    ${classPill}${gatePill}
                </div>${dupRow}
                <div class="flex items-center justify-center gap-1 mt-2">
                    <button data-action="approve" ${!canApprove ? 'disabled' : ''} class="p-1.5 rounded-lg ${canApprove ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    <button data-action="share" class="p-1.5 bg-teal-100 text-teal-600 rounded-lg" title="Share restore link">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
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

    attachCardListeners(card, reg) {
        card.querySelector('[data-action="approve"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.approve(reg.id); });
        card.querySelector('[data-action="share"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.shareRegistration(reg.id); });
        card.querySelector('[data-action="reject"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.reject(reg.id); });
        card.querySelector('[data-action="delete"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.promptDelete(reg.id); });
        card.querySelector('[data-action="edit"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.openEditModal(reg.id); });
        card.querySelector('[data-action="view"]')?.addEventListener('click', (e) => { e.stopPropagation(); this.openViewModal(reg.id); });
    },

    observeLazyImages() {
        if (!this.imageObserver) return;
        this.els.list?.querySelectorAll('.lazy-image[data-src]').forEach(img => {
            this.imageObserver.observe(img);
        });
    },

    updateDataItem(id, updates) {
        const idx = this.allData.findIndex(r => r.id === id);
        if (idx !== -1) {
            this.allData[idx] = { ...this.allData[idx], ...updates };
            this.renderVisible();
        }
    },

    removeDataItem(id) {
        const idx = this.allData.findIndex(r => r.id === id);
        if (idx !== -1) {
            this.allData.splice(idx, 1);
            this.totalCount--;
            this.updateVirtualHeight();
            this.renderVisible();
        }
    },

    // CRUD Operations
    async approve(id) {
        if (!confirm('Approve this registration?')) return;
        try {
            const data = await this.api('approve', { id }, 'POST');
            if (data.success) {
                this.updateDataItem(id, data.registration);
                if (data.statusCounts) this.updateStats(null, data.statusCounts);
            } else {
                alert(data.error || 'Failed to approve');
            }
        } catch (e) {
            alert('Failed to approve registration');
        }
    },

    async reject(id) {
        if (!confirm('Reject this registration?')) return;
        try {
            const data = await this.api('reject', { id }, 'POST');
            if (data.success) {
                this.updateDataItem(id, data.registration);
                if (data.statusCounts) this.updateStats(null, data.statusCounts);
            } else {
                alert(data.error || 'Failed to reject');
            }
        } catch (e) {
            alert('Failed to reject registration');
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
                if (data.statusCounts) this.updateStats(data.total, data.statusCounts);
                if (this.allData.length === 0) this.showEmpty();
            } else {
                alert(data.error || 'Failed to delete');
            }
        } catch (e) {
            alert('Failed to delete registration');
        }
        this.deleteTargetId = null;
    },

    async submitAdd() {
        const form = document.getElementById('addRegForm');
        if (!form) return;

        const params = {};
        new FormData(form).forEach((v, k) => params[k] = v);

        try {
            const data = await this.api('add', params, 'POST');
            if (data.success) {
                this.closeModal('addRegModal');
                form.reset();
                this.allData.unshift(data.registration);
                this.totalCount++;
                this.updateVirtualHeight();
                this.renderVisible();
                if (data.statusCounts) this.updateStats(data.total, data.statusCounts);
                this.hideEmpty();
            } else {
                alert(data.error || 'Failed to add registration');
            }
        } catch (e) {
            alert('Failed to add registration');
        }
    },

    async submitEdit() {
        const form = document.getElementById('editRegForm');
        const id = document.getElementById('editRegId')?.value;
        if (!form || !id) return;

        const params = { id };
        new FormData(form).forEach((v, k) => { if (k !== 'id') params[k] = v; });

        try {
            const data = await this.api('edit', params, 'POST');
            if (data.success) {
                this.closeModal('editRegModal');
                this.updateDataItem(id, data.registration);
                if (data.statusCounts) this.updateStats(null, data.statusCounts);
            } else {
                alert(data.error || 'Failed to save changes');
            }
        } catch (e) {
            alert('Failed to save changes');
        }
    },

    // Modals
    openAddModal() {
        const container = document.getElementById('addRegFields');
        if (!container) return;

        let html = '';
        (this.config.formSteps || []).forEach((step, i) => {
            html += `<div class="border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">${this.esc(step.title || `Step ${i + 1}`)}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">`;
            (step.fields || []).forEach(field => {
                if (field.type === 'file') return;
                html += this.renderField(field, '', 'data_');
            });
            html += '</div></div>';
        });

        html += `<div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-sm">
                ${Object.entries(this.config.statusConfig).map(([k, v]) => `<option value="${k}" ${k === 'verified' ? 'selected' : ''}>${v.label}</option>`).join('')}
            </select>
        </div>`;

        container.innerHTML = html;
        document.getElementById('addRegModal')?.classList.remove('hidden');
    },

    async openEditModal(id) {
        try {
            const data = await this.api('get', { id });
            if (!data.success) return alert(data.error || 'Failed to load');

            const reg = data.registration;
            const container = document.getElementById('editRegFields');
            document.getElementById('editRegId').value = id;

            const excluded = ['student_id_photo', 'payment_proof', 'document_attempts', 'payment_attempts'];
            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">';

            Object.entries(reg.data || {}).forEach(([key, value]) => {
                if (excluded.includes(key)) return;
                const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                html += `<div>
                    <label class="block text-xs text-gray-500 uppercase mb-1">${this.esc(label)}</label>
                    <input type="text" name="data_${this.esc(key)}" value="${this.esc(String(value || ''))}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm">
                </div>`;
            });

            html += `<div>
                <label class="block text-xs text-gray-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm">
                    ${Object.entries(this.config.statusConfig).map(([k, v]) => `<option value="${k}" ${k === reg.status ? 'selected' : ''}>${v.label}</option>`).join('')}
                </select>
            </div>`;
            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Created</label><div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm">${this.esc(reg.created_at || '-')}</div></div>`;
            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Reg ID</label><div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-mono truncate">${this.esc(id)}</div></div>`;
            html += '</div>';

            container.innerHTML = html;
            document.getElementById('editRegModal')?.classList.remove('hidden');
        } catch (e) {
            alert('Failed to load registration');
        }
    },

    async openViewModal(id) {
        this.viewRegId = id;
        try {
            const data = await this.api('get', { id });
            if (!data.success) return alert(data.error || 'Failed to load');

            const reg = data.registration;
            const container = document.getElementById('viewRegContent');
            const excludeFields = ['student_id_photo', 'payment_proof', 'document_attempts', 'payment_attempts'];

            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">';
            Object.entries(reg.data || {}).forEach(([key, value]) => {
                if (excludeFields.includes(key)) return;
                const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                const val = Array.isArray(value) ? JSON.stringify(value) : (value || '-');
                html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">${this.esc(label)}</label><div class="font-medium text-sm">${this.esc(String(val))}</div></div>`;
            });

            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Status</label><span class="${reg.status_color} text-white text-xs px-2 py-0.5 rounded-full">${this.esc(reg.status_label)}</span></div>`;
            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Created</label><div class="text-sm">${this.esc(reg.created_at || '-')}</div></div>`;
            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Last Active</label><div class="text-sm">${this.esc(reg.last_activity || '-')}</div></div>`;
            html += `<div><label class="block text-xs text-gray-500 uppercase mb-1">Reg ID</label><div class="font-mono text-sm">${this.esc(id)}</div></div>`;
            html += '</div>';

            // Failed Attempts Section
            const docAttempts = parseInt(reg.data?.document_attempts) || 0;
            const payAttempts = parseInt(reg.data?.payment_attempts) || 0;
            if (docAttempts > 0 || payAttempts > 0) {
                html += '<div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"><h4 class="text-sm font-bold mb-3 text-red-600">⚠️ Failed Attempts</h4><div class="flex gap-4">';
                if (docAttempts > 0) html += `<div class="px-3 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg"><span class="text-xs text-gray-500 block">Document</span><span class="text-lg font-bold text-red-600">${docAttempts}</span></div>`;
                if (payAttempts > 0) html += `<div class="px-3 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg"><span class="text-xs text-gray-500 block">Payment</span><span class="text-lg font-bold text-red-600">${payAttempts}</span></div>`;
                html += '</div></div>';
            }

            if (reg.student_id_url || reg.payment_url) {
                html += '<div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-4">';
                if (reg.student_id_url) html += `<a href="${this.esc(reg.student_id_url)}" target="_blank"><span class="text-xs text-gray-500 block mb-1">Student ID</span><img src="${this.esc(reg.student_id_url)}" class="h-32 rounded border"></a>`;
                if (reg.payment_url) html += `<a href="${this.esc(reg.payment_url)}" target="_blank"><span class="text-xs text-gray-500 block mb-1">Payment</span><img src="${this.esc(reg.payment_url)}" class="h-32 rounded border"></a>`;
                html += '</div>';
            }

            const ai = reg.ai_verification || {};
            if (Object.keys(ai).length > 0) {
                html += '<div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"><h4 class="text-sm font-bold mb-3">🤖 AI Verification</h4><div class="grid md:grid-cols-2 gap-4">';
                ['student_id', 'payment'].forEach(type => {
                    const r = ai[type]?.result;
                    if (!r) return;
                    html += `<div class="p-3 rounded-lg ${r.valid ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'}">
                        <span class="text-xs font-bold uppercase ${r.valid ? 'text-green-700' : 'text-red-700'}">${type.replace('_', ' ')}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full ml-2 ${r.valid ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}">${r.valid ? '✓ Valid' : '✗ Invalid'}</span>
                        ${r.issues?.length ? `<ul class="text-xs text-red-600 list-disc pl-4 mt-2">${r.issues.map(i => `<li>${this.esc(i)}</li>`).join('')}</ul>` : ''}
                    </div>`;
                });
                html += '</div></div>';
            }

            container.innerHTML = html;
            document.getElementById('viewRegModal')?.classList.remove('hidden');
        } catch (e) {
            alert('Failed to load registration');
        }
    },

    closeModal(id) {
        document.getElementById(id)?.classList.add('hidden');
        if (id === 'viewRegModal') this.viewRegId = null;
    },

    async shareRegistration(id) {
        if (!id) return;

        try {
            const data = await this.api('generate_share_link', { id });
            if (data.success && data.url) {
                await navigator.clipboard.writeText(data.url);
                this.showToast('Link copied to clipboard!');
            } else {
                alert(data.error || 'Failed to generate share link');
            }
        } catch (e) {
            // Fallback for older browsers
            try {
                const data = await this.api('generate_share_link', { id });
                if (data.success && data.url) {
                    prompt('Copy this link:', data.url);
                }
            } catch (e2) {
                alert('Failed to generate share link');
            }
        }
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

    renderField(field, value = '', prefix = '') {
        const name = field.name || '';
        const label = field.label || name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        const type = field.type || 'text';
        const req = field.required ? 'required' : '';

        let input;
        if (type === 'textarea') {
            input = `<textarea name="${prefix}${name}" ${req} rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-sm">${this.esc(value)}</textarea>`;
        } else if (type === 'select' && field.options) {
            input = `<select name="${prefix}${name}" ${req} class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-sm">
                <option value="">- Select -</option>${field.options.map(o => `<option value="${this.esc(o)}" ${o === value ? 'selected' : ''}>${this.esc(o)}</option>`).join('')}
            </select>`;
        } else {
            input = `<input type="${type === 'email' ? 'email' : type === 'tel' ? 'tel' : 'text'}" name="${prefix}${name}" value="${this.esc(value)}" ${req} class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-sm">`;
        }

        return `<div class="${type === 'textarea' ? 'sm:col-span-2' : ''}">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">${this.esc(label)}${field.required ? '<span class="text-red-500">*</span>' : ''}</label>
            ${input}
        </div>`;
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
        // Disable sort when duplicate filter active
        if (this.els.sort) {
            this.els.sort.disabled = this.currentFilter === 'duplicate';
            this.els.sort.classList.toggle('opacity-50', this.currentFilter === 'duplicate');
        }
    },

    updateStats(total, counts, duplicateCount) {
        if (total !== null && this.els.statTotal) this.els.statTotal.textContent = total;
        if (duplicateCount !== undefined) {
            const dupEl = document.getElementById('stat-duplicate');
            if (dupEl) dupEl.textContent = duplicateCount;
        }
        if (counts) {
            Object.entries(counts).forEach(([s, c]) => {
                const el = document.getElementById(`stat-${s}`);
                if (el) el.textContent = c;
            });
            Object.keys(this.config.statusConfig).forEach(s => {
                if (!(s in counts)) {
                    const el = document.getElementById(`stat-${s}`);
                    if (el) el.textContent = '0';
                }
            });
        }
    },

    showLoading() { this.els.loading?.classList.remove('hidden'); },
    hideLoading() { this.els.loading?.classList.add('hidden'); },
    showEmpty() { this.els.empty?.classList.remove('hidden'); if (this.els.list) this.els.list.innerHTML = ''; if (this.els.emptyMsg) this.els.emptyMsg.textContent = this.currentSearch ? 'No results found.' : this.currentFilter !== 'all' ? 'No registrations with this status.' : 'No registrations yet.'; },
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
        return ['addRegModal', 'editRegModal', 'viewRegModal', 'deleteConfirmModal'].some(id => !document.getElementById(id)?.classList.contains('hidden'));
    },

    esc(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }
};

document.addEventListener('DOMContentLoaded', () => RegistrationsManager.init());
