<?php
/**
 * Orchestrator Dashboard View
 * Real-time monitoring for admins - Responsive design for all devices
 */

// Get initial stats
$stats = $orchRepo->getStats($activeFormId);
$recentEvents = $orchRepo->getRecentEvents($activeFormId);

// Get all registrations count
$allRegistrations = $regRepo->getAllForForm($activeFormId);
$verifiedCount = count(array_filter($allRegistrations, fn($r) => $r['status'] === 'verified'));
?>

<!-- Dashboard Container -->
<div class="dashboard-container max-w-7xl mx-auto">

    <!-- Navigation Bar -->
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3 min-w-0">
            <?php if ($isOrchestratorUser): ?>
                <a href="?form_id=<?= urlencode($activeFormId) ?>&mode=scanner"
                    class="flex-shrink-0 p-2 -ml-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            <?php else: ?>
                <a href="/admin/forms.php"
                    class="flex-shrink-0 p-2 -ml-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            <?php endif; ?>
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                    <?= htmlspecialchars($activeForm['title']) ?>
                </h1>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span>Live Dashboard</span>
                    <span class="text-gray-400">•</span>
                    <span id="lastUpdate" class="text-gray-400">Just now</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($canManageAccounts): ?>
                <a href="/admin/forms/orchestrator-accounts.php?form_id=<?= urlencode($activeFormId) ?>"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-3 py-2 sm:px-4 sm:py-2.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800/30 rounded-xl text-sm font-bold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                    <span class="hidden sm:inline">Accounts</span>
                </a>
            <?php endif; ?>
            <a href="?form_id=<?= urlencode($activeFormId) ?>&mode=scanner"
                class="flex-shrink-0 inline-flex items-center gap-2 px-3 py-2 sm:px-4 sm:py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-amber-500/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                <span class="hidden sm:inline">Scanner</span>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- Total Verified -->
        <div
            class="stat-card bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-5 shadow-sm border border-gray-100 dark:border-gray-700 transition-all hover:shadow-md">
            <div class="flex items-center gap-3 sm:gap-4">
                <div
                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25 flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white transition-transform"
                        id="stat-verified">
                        <?= $verifiedCount ?>
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 font-medium">Verified</div>
                </div>
            </div>
        </div>

        <!-- Checked In -->
        <div
            class="stat-card bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-5 shadow-sm border border-gray-100 dark:border-gray-700 transition-all hover:shadow-md">
            <div class="flex items-center gap-3 sm:gap-4">
                <div
                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg shadow-green-500/25 flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white transition-transform"
                        id="stat-checked-in">
                        <?= $stats['checked_in'] ?? 0 ?>
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 font-medium">Checked In</div>
                </div>
            </div>
        </div>

        <!-- Merch Given -->
        <div
            class="stat-card bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-5 shadow-sm border border-gray-100 dark:border-gray-700 transition-all hover:shadow-md">
            <div class="flex items-center gap-3 sm:gap-4">
                <div
                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/25 flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white transition-transform"
                        id="stat-merch">
                        <?= $stats['merch_distributed'] ?? 0 ?>
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 font-medium">Merch Given</div>
                </div>
            </div>
        </div>

        <!-- Total Events -->
        <div
            class="stat-card bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-5 shadow-sm border border-gray-100 dark:border-gray-700 transition-all hover:shadow-md">
            <div class="flex items-center gap-3 sm:gap-4">
                <div
                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25 flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white transition-transform"
                        id="stat-events">
                        <?= $stats['total_events'] ?? 0 ?>
                    </div>
                    <div class="text-xs sm:text-sm text-gray-500 font-medium">Total Events</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Bar - Horizontal scroll on mobile -->
    <div class="quick-actions -mx-4 sm:mx-0 px-4 sm:px-0 mb-6">
        <div class="flex gap-2 overflow-x-auto pb-2 sm:pb-0 sm:flex-wrap scrollbar-hide">
            <button id="exportEventsBtn"
                class="action-btn inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm whitespace-nowrap flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Export Events</span>
            </button>
            <button id="exportAttendeesBtn"
                class="action-btn inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm whitespace-nowrap flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Attendees</span>
            </button>
            <button id="refreshDataBtn"
                class="action-btn inline-flex items-center gap-2 px-4 py-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm whitespace-nowrap flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Refresh</span>
            </button>
            <button id="broadcastBtn"
                class="action-btn inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm whitespace-nowrap flex-shrink-0 sm:ml-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span>Broadcast</span>
            </button>
        </div>
    </div>

    <!-- Participant Search -->
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 overflow-hidden">
        <div class="px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Participant Lookup
            </h2>
        </div>
        <div class="p-4 sm:p-5">
            <div class="flex gap-2">
                <input type="text" id="participantSearch" placeholder="Search by name, ID, or school..."
                    class="flex-1 px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none text-sm transition-all">
                <button id="searchParticipantBtn"
                    class="px-5 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-sm transition-colors flex-shrink-0">
                    <span class="hidden sm:inline">Search</span>
                    <svg class="w-5 h-5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </div>
            <div id="searchResults" class="hidden mt-4">
                <div id="searchResultsList"
                    class="divide-y divide-gray-100 dark:divide-gray-700 max-h-64 overflow-y-auto rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <!-- Results will be inserted here -->
                </div>
            </div>
            <div id="noSearchResults" class="hidden text-center py-8">
                <div
                    class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-gray-500 text-sm">No participants found</p>
            </div>
        </div>
    </div>

    <!-- Two-column layout for larger screens -->
    <div class="grid lg:grid-cols-3 gap-6">

        <!-- Location Distribution -->
        <div
            class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    Current Locations
                </h2>
            </div>
            <div id="locationGrid" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 p-4 sm:p-5 max-h-[70vh] overflow-y-auto pr-1 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <?php
                $locationsData = $stats['locations'] ?? [];
                // Use configured stations from global settings
                $stationLabels = array_map(fn($s) => $s['label'], $configuredStations);

                foreach ($stationLabels as $loc):
                    $count = $locationsData[$loc] ?? 0;
                    ?>
                    <div class="location-card bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 rounded-xl p-3 sm:p-4 text-center transition-all hover:scale-105 cursor-pointer flex-shrink-0"
                        data-location="<?= htmlspecialchars($loc) ?>">
                        <div class="w-full flex flex-col items-center justify-center gap-1">
                            <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white location-count transition-colors flex-shrink-0">
                                <?= $count ?>
                            </div>
                            <div class="text-xs text-gray-500 w-full break-words px-1 leading-tight">
                                <?= htmlspecialchars($loc) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Event Log -->
        <div
            class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div
                class="px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="hidden sm:inline">Live Event Log</span>
                    <span class="sm:hidden">Events</span>
                </h2>
                <div class="flex items-center gap-2">
                    <input type="text" id="adminSearch" placeholder="Search admin..."
                        class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-transparent w-32 sm:w-40">
                    <select id="eventFilter"
                        class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="">All Events</option>
                        <option value="check_in">Check-ins</option>
                        <option value="payment_verified">Payments Verified</option>
                        <option value="payment_denied">Payments Denied</option>
                        <option value="merch_given">Merch Given</option>
                        <option value="room_enter">Room Entries</option>
                        <option value="room_exit">Room Exits</option>
                        <option value="broadcast">Broadcasts</option>
                    </select>
                </div>
            </div>
            <div id="eventLog"
                class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 lg:max-h-[500px] overflow-y-auto">
                <?php if (empty($recentEvents)): ?>
                    <div class="p-8 sm:p-12 text-center">
                        <div
                            class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <p class="text-gray-500 font-medium">No events recorded yet</p>
                        <p class="text-gray-400 text-sm mt-1">Events will appear here as they happen</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentEvents as $event):
                        $reg = $regRepo->find($activeFormId, $event['reg_id']);
                        $name = $reg['data']['full_name'] ?? $event['reg_id'];

                        $typeConfig = match ($event['type']) {
                            'check_in' => ['icon' => '✓', 'bg' => 'bg-green-100 dark:bg-green-900/30 text-green-600'],
                            'payment_verified' => ['icon' => '💰', 'bg' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600'],
                            'payment_denied' => ['icon' => '✗', 'bg' => 'bg-red-100 dark:bg-red-900/30 text-red-600'],
                            'merch_given' => ['icon' => '🎁', 'bg' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600'],
                            'room_enter' => ['icon' => '→', 'bg' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600'],
                            'room_exit' => ['icon' => '←', 'bg' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600'],
                            'broadcast' => ['icon' => '📢', 'bg' => 'bg-red-100 dark:bg-red-900/30 text-red-600'],
                            default => ['icon' => '•', 'bg' => 'bg-gray-100 dark:bg-gray-700 text-gray-600']
                        };
                        ?>
                        <div class="px-4 py-3 sm:px-5 sm:py-4 flex items-center gap-3 event-row hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors <?= $event['type'] === 'broadcast' ? 'bg-red-50 dark:bg-red-900/10' : '' ?>"
                            data-event-type="<?= htmlspecialchars($event['type']) ?>"
                            data-operator="<?= htmlspecialchars(strtolower($event['operator'])) ?>">
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl <?= $typeConfig['bg'] ?> flex items-center justify-center text-lg flex-shrink-0">
                                <?= $typeConfig['icon'] ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div
                                    class="font-semibold text-gray-900 dark:text-white truncate <?= $event['type'] === 'broadcast' ? 'text-red-600 dark:text-red-400' : '' ?>">
                                    <?= $event['type'] === 'broadcast' ? 'Emergency Broadcast' : htmlspecialchars($name) ?>
                                </div>
                                <div class="text-sm text-gray-500 flex items-center gap-2 flex-wrap">
                                    <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $event['type']))) ?></span>
                                    <?php if ($event['location']): ?>
                                        <span class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                            <?= htmlspecialchars($event['location']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-xs text-gray-400 font-medium">
                                    <?= date('H:i', strtotime($event['timestamp'])) ?>
                                </div>
                                <div class="text-xs text-gray-400 truncate max-w-20">
                                    <?= htmlspecialchars($event['operator']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Participant Detail Modal -->
<div id="participantModal"
    class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden animate-modal">
        <div
            class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between sticky top-0 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Participant Details</h3>
            <button id="closeParticipantModal"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="participantModalContent" class="p-5 sm:p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
            <!-- Content will be inserted dynamically -->
        </div>
    </div>
</div>

<!-- Broadcast Modal -->
<div id="broadcastModal"
    class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-modal">
        <div
            class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-700 bg-red-50 dark:bg-red-900/20">
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Emergency Broadcast
            </h3>
        </div>
        <div class="p-5 sm:p-6">
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                This will log an emergency broadcast event visible to all operators.
            </p>
            <textarea id="broadcastMessage" rows="3" placeholder="Enter your emergency message..."
                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-red-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none mb-4 text-sm"></textarea>
            <div class="flex gap-3">
                <button id="cancelBroadcast"
                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold transition-colors hover:bg-gray-200 dark:hover:bg-gray-600">
                    Cancel
                </button>
                <button id="sendBroadcast"
                    class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-colors">
                    Send Broadcast
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Scrollbar hide utility */
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    /* Modal animation */
    @keyframes modalIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .animate-modal {
        animation: modalIn 0.2s ease-out;
    }

    /* Location card hover effect */
    .location-card:hover .location-count {
        color: #d97706;
    }

    /* Stat card number animation */
    .stat-card .text-2xl,
    .stat-card .text-3xl {
        transition: transform 0.2s ease;
    }

    /* Dark mode scrollbar */
    @media (prefers-color-scheme: dark) {
        .overflow-y-auto {
            scrollbar-color: #4b5563 #1f2937;
        }
    }
</style>

<script>
    window.ORCHESTRATOR_CONFIG = {
        formId: <?= json_encode($activeFormId) ?>,
        csrfToken: <?= json_encode($csrfToken) ?>,
        apiUrl: '/admin/forms/orchestrator/api.php'
    };
</script>
<script src="/admin/forms/orchestrator/assets/dashboard.js?v=<?= time() ?>"></script>