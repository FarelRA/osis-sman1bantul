<div class="mb-6 sm:mb-8">
    <a href="<?= ADMIN_PATH ?>/forms.php"
        class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 mb-3 sm:mb-4 transition-colors text-sm sm:text-base">
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Forms
    </a>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                <?= htmlspecialchars($activeForm['title']) ?>
            </h2>
            <p class="text-sm sm:text-base text-gray-500">Registrations Dashboard</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="addRegBtn"
                class="flex items-center justify-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-colors text-sm sm:text-base">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Registration
            </button>
            <button type="button" id="exportCsvBtn"
                class="flex items-center justify-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold transition-colors text-sm sm:text-base">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
        </div>
    </div>
</div>

<!-- Search & Sort Bar -->
<div class="flex flex-col sm:flex-row gap-3 mb-6">
    <div class="relative flex-1">
        <input type="text" id="searchInput" placeholder="Search by name, ID, or school..."
            class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <button type="button" id="clearSearch" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div class="relative">
        <select id="sortSelect"
            class="w-full appearance-none pl-3 pr-10 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
            <option value="name">Name A-Z</option>
            <option value="name_desc">Name Z-A</option>
            <option value="status">Status</option>
            <option value="class">Assigned Class</option>
        </select>
        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
</div>

<!-- Stats (Clickable Filters) -->
<div id="statusFilters" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-3 md:gap-4 mb-6 sm:mb-8">
    <div data-filter-status="all"
        class="status-filter active bg-white dark:bg-gray-800 rounded-lg sm:rounded-xl p-3 sm:p-4 text-center shadow border-2 border-blue-500 cursor-pointer hover:shadow-md transition-all">
        <div id="stat-total" class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">-</div>
        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">All</div>
    </div>
    <div data-filter-status="duplicate"
        class="status-filter bg-white dark:bg-gray-800 rounded-lg sm:rounded-xl p-3 sm:p-4 text-center shadow border-2 border-transparent cursor-pointer hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all">
        <div id="stat-duplicate" class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">-</div>
        <div class="text-[10px] sm:text-xs bg-orange-500 text-white px-1.5 sm:px-2 py-0.5 rounded-full inline-block mt-1 truncate max-w-full">
            Duplicates
        </div>
    </div>
    <?php foreach (FormConstants::STATUS_CONFIG as $status => $config): ?>
    <div data-filter-status="<?= htmlspecialchars($status) ?>"
        class="status-filter bg-white dark:bg-gray-800 rounded-lg sm:rounded-xl p-3 sm:p-4 text-center shadow border-2 border-transparent cursor-pointer hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all">
        <div id="stat-<?= htmlspecialchars($status) ?>" class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">-</div>
        <div class="text-[10px] sm:text-xs <?= $config['color'] ?> text-white px-1.5 sm:px-2 py-0.5 rounded-full inline-block mt-1 truncate max-w-full">
            <?= $config['label'] ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Virtual Scroll Container -->
<div id="registrationContainer" class="relative">
    <!-- Loading State -->
    <div id="loadingState" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-gray-500">Loading registrations...</span>
    </div>
    
    <!-- Empty State -->
    <div id="emptyState" class="hidden bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-8 sm:p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700">
        <div class="text-4xl sm:text-6xl mb-3 sm:mb-4">📭</div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-2">No Registrations Found</h3>
        <p id="emptyMessage" class="text-sm sm:text-base text-gray-500 dark:text-gray-400">Registrations for this form will appear here.</p>
    </div>
    
    <!-- Virtual Scroll Area (uses window scroll) -->
    <div id="virtualViewport" class="virtual-viewport relative">
        <div id="virtualContent" class="relative" style="height: 0;">
            <div id="registrationList" class="relative"></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/modals.php'; ?>

<link rel="stylesheet" href="<?= ADMIN_PATH ?>/forms/registrations/assets/registrations.css?v=<?= time() ?>">
<script>
    window.REGISTRATIONS_CONFIG = {
        formId: <?= json_encode($activeFormId) ?>,
        csrfToken: <?= json_encode($csrfToken) ?>,
        statusConfig: <?= json_encode(FormConstants::STATUS_CONFIG) ?>,
        formSteps: <?= json_encode($activeForm['steps'] ?? []) ?>,
        apiUrl: '<?= ADMIN_PATH ?>/forms/registrations/api.php'
    };
</script>
<script src="<?= ADMIN_PATH ?>/forms/registrations/assets/registrations.js?v=<?= time() ?>"></script>
