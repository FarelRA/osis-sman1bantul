<?php
/**
 * Orchestrator Scanner View - Redesigned
 * Scan-first approach with permission-based action buttons
 * Shows logs and scan interface only - actions appear after scan
 */
?>

<!-- Location Setup Modal (for Normal permission users who need to select from allowed locations) -->
<?php if (!$operatorLocation && count($availableStations) > 0): ?>
    <div id="locationSetupModal"
        class="fixed inset-0 bg-gradient-to-br from-gray-900/95 via-gray-800/95 to-gray-900/95 backdrop-blur-md z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-8">
            <!-- Logo/Icon -->
            <div class="text-center mb-8">
                <div class="relative inline-block">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center mx-auto shadow-xl shadow-amber-500/30 transform rotate-3">
                        <svg class="w-10 h-10 sm:w-12 sm:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-6">Select Your Station</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-2">
                    <?php if ($orchestratorAccount): ?>
                        Welcome, <?= htmlspecialchars($operatorName) ?>
                    <?php else: ?>
                        Choose where you're operating from
                    <?php endif; ?>
                </p>
            </div>

            <form id="locationSetupForm" class="space-y-5">
                <div class="grid grid-cols-2 gap-3 max-h-[50vh] overflow-y-auto pr-1 scrollbar-thin">
                    <?php foreach ($availableStations as $station): ?>
                        <button type="button" onclick="selectLocation('<?= htmlspecialchars($station['label']) ?>')"
                            class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all text-left flex-shrink-0">
                            <div class="text-2xl mb-1"><?= htmlspecialchars($station['emoji'] ?? '') ?></div>
                            <div class="font-bold text-gray-900 dark:text-white text-sm truncate"><?= htmlspecialchars($station['label']) ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Main Scanner Layout -->
<div class="scanner-container" x-data="scannerApp()" x-init="init()">
    <!-- Navigation Bar -->
    <div class="flex items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3 min-w-0">
            <?php if ($isOrchestratorUser): ?>
                <button onclick="confirmLogout()"
                    class="flex-shrink-0 p-2 -ml-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-xl transition-colors text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            <?php else: ?>
                <a href="<?= ADMIN_PATH ?>/forms.php"
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
                <p class="text-sm text-gray-500 dark:text-gray-400">Scanner</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($canManageAccounts): ?>
                <a href="<?= ADMIN_PATH ?>/forms/orchestrator-accounts.php?form_id=<?= urlencode($activeFormId) ?>"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-3 py-2 sm:px-4 sm:py-2.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800/30 rounded-xl text-sm font-bold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                    <span class="hidden sm:inline">Accounts</span>
                </a>
            <?php endif; ?>
            <a href="?form_id=<?= urlencode($activeFormId) ?>&mode=dashboard"
                class="flex-shrink-0 inline-flex items-center gap-2 px-3 py-2 sm:px-4 sm:py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-sm font-bold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="hidden sm:inline">Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Two-Column Layout -->
    <div class="scanner-layout lg:grid lg:grid-cols-5 lg:gap-6 xl:gap-8">

        <!-- Left Column: Camera & Controls -->
        <div class="lg:col-span-3 space-y-4">

            <!-- Operator Info Badge -->
            <?php if ($operatorName): ?>
                <div class="operator-badge bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 border border-amber-200 dark:border-amber-700 rounded-2xl p-3 sm:p-4 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shadow-md flex-shrink-0
                            <?php if ($permissionLevel === 'super'): ?>bg-gradient-to-br from-purple-400 to-purple-600
                            <?php elseif ($permissionLevel === 'high'): ?>bg-gradient-to-br from-amber-400 to-amber-600
                            <?php elseif ($permissionLevel === 'admin'): ?>bg-gradient-to-br from-green-400 to-green-600
                            <?php else: ?>bg-gradient-to-br from-blue-400 to-blue-600<?php endif; ?>">
                            <span class="text-white font-bold text-lg sm:text-xl"><?= strtoupper(substr($operatorName, 0, 1)) ?></span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 dark:text-white text-sm sm:text-base truncate">
                                    <?= htmlspecialchars($operatorName) ?>
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                    <?php if ($permissionLevel === 'super'): ?>bg-purple-100 dark:bg-purple-900/30 text-purple-600
                                    <?php elseif ($permissionLevel === 'high'): ?>bg-amber-100 dark:bg-amber-900/30 text-amber-600
                                    <?php elseif ($permissionLevel === 'admin'): ?>bg-green-100 dark:bg-green-900/30 text-green-600
                                    <?php else: ?>bg-blue-100 dark:bg-blue-900/30 text-blue-600<?php endif; ?>">
                                    <?= ucfirst($permissionLevel) ?>
                                </span>
                            </div>
                            <?php if ($operatorLocation): ?>
                                <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400 text-xs sm:text-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    <span class="truncate"><?= htmlspecialchars($operatorLocation) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($canSelectLocation): ?>
                        <button id="changeLocationBtn"
                            class="flex-shrink-0 p-2 text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-800/50 rounded-lg transition-colors"
                            title="Change location">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Camera Scanner -->
            <div class="camera-container relative rounded-3xl overflow-hidden shadow-2xl bg-gray-900">
                <div class="camera-aspect">
                    <video id="qrVideo" class="absolute inset-0 w-full h-full object-cover" playsinline autoplay muted></video>

                    <!-- Viewfinder Overlay -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="viewfinder w-3/5 sm:w-1/2 max-w-xs aspect-square relative">
                            <div class="absolute inset-0 border-2 border-white/30 rounded-3xl"></div>
                            <div class="corner corner-tl"></div>
                            <div class="corner corner-tr"></div>
                            <div class="corner corner-bl"></div>
                            <div class="corner corner-br"></div>
                        </div>
                    </div>

                    <!-- Scan Line Animation -->
                    <div id="scanLine" class="scan-line"></div>

                    <!-- Camera Error State -->
                    <div id="cameraError"
                        class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 text-white hidden p-6">
                        <div class="w-20 h-20 rounded-full bg-red-500/20 flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold mb-2">Camera Access Required</h3>
                        <p class="text-center text-gray-400 text-sm mb-6 max-w-xs">Please allow camera access to scan QR codes.</p>
                        <button id="retryCamera"
                            class="px-6 py-3 bg-amber-600 hover:bg-amber-700 rounded-xl font-bold transition-colors">
                            Try Again
                        </button>
                    </div>
                </div>
            </div>

            <!-- Manual ID Input + Image Scan + OCR -->
            <div class="manual-input bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col">
                <div class="flex-1 p-4">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Manual Entry / Image Scan</label>
                <div class="flex flex-wrap gap-2">
                    <input type="text" id="manualIdInput" placeholder="Search by name, ID, or school..."
                        class="flex-1 min-w-0 px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none text-sm transition-all"
                        autocomplete="off">
                    <div class="flex gap-2 flex-shrink-0">
                        <button id="manualLookupBtn"
                            class="px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold transition-colors"
                            title="Search">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                        <button id="ocrButton"
                            class="px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-400 text-white rounded-xl font-bold transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                            :disabled="ocrRunning" title="OCR ID Card">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </button>
                        <label class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition-colors cursor-pointer flex items-center"
                            title="Scan QR from image">
                            <input type="file" id="imageFileInput" accept="image/*" class="hidden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </label>
                    </div>
                </div>

                <!-- Search Results -->
                <div id="manualSearchResults" class="hidden mt-3">
                    <div id="manualSearchResultsList"
                        class="divide-y divide-gray-100 dark:divide-gray-700 max-h-64 overflow-y-auto rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <!-- Results will be inserted here -->
                    </div>
                </div>
                <div id="manualNoResults" class="hidden text-center py-6 mt-3">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">No participants found</p>
                </div>

                </div>

                <div id="ocrStatus" class="text-center mt-auto hidden">
                    <div id="ocrStatusText" class="w-full px-3 py-2 text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded-b-2xl"></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Results & Recent Scans -->
        <div class="lg:col-span-2 space-y-4 mt-4 lg:mt-0">

            <!-- Result Panel (shown after scan) -->
            <div id="resultPanel" x-show="scannedParticipant" x-cloak
                class="result-panel bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                
                <!-- Header with participant info -->
                <div class="p-4 sm:p-5 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/80 dark:to-gray-800/80 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg flex-shrink-0">
                            <span class="text-white font-bold text-xl sm:text-2xl" x-text="scannedParticipant?.name?.charAt(0)?.toUpperCase() || '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg sm:text-xl truncate" x-text="scannedParticipant?.name || 'Unknown'"></h3>
                            <p class="text-gray-500 text-sm font-mono mt-0.5" x-text="scannedParticipant?.id || ''"></p>
                        </div>
                        <button @click="closeResult()"
                            class="p-2.5 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors flex-shrink-0">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="result-stat bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Status</span>
                            <div class="font-bold mt-1" 
                                :class="{
                                    'text-green-600': scannedParticipant?.status === 'verified',
                                    'text-yellow-600': scannedParticipant?.status?.includes('pending'),
                                    'text-red-600': scannedParticipant?.status === 'rejected'
                                }"
                                x-text="scannedParticipant?.status || '-'"></div>
                        </div>
                        <template x-for="(value, key) in scannedParticipant?.dataFields || {}" :key="key">
                            <div class="result-stat bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                                <span class="text-xs text-gray-500 uppercase tracking-wide" x-text="key.replace(/_/g, ' ')"></span>
                                <div class="font-bold text-gray-900 dark:text-white mt-1 overflow-hidden text-ellipsis" x-text="value || '-'"></div>
                            </div>
                        </template>
                        <div class="result-stat bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Checked In</span>
                            <div class="font-bold mt-1" :class="scannedParticipant?.checked_in ? 'text-green-600' : 'text-gray-500'"
                                x-text="scannedParticipant?.checked_in ? 'Yes' : 'No'"></div>
                        </div>
                        <div class="result-stat bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3" x-show="merchEnabled">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Merch</span>
                            <div class="font-bold mt-1" :class="scannedParticipant?.merch_collected ? 'text-purple-600' : 'text-gray-500'"
                                x-text="scannedParticipant?.merch_collected ? 'Collected' : 'Not collected'"></div>
                        </div>
                    </div>

                    <!-- Warnings -->
                    <div x-show="warning" class="mb-4">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-yellow-400 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-yellow-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <p class="text-yellow-800 dark:text-yellow-200 text-sm font-medium" x-text="warning"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons (based on permissions) -->
                    <div class="action-buttons flex flex-col gap-2">
                        <!-- View Full Info Button (always shown) -->
                        <button @click="showFullInfo()"
                            class="w-full py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-bold transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            View Full Info
                        </button>

                        <!-- Combined Check-in & Merch -->
                        <template x-if="merchEnabled && canPerformAction('check_in') && canPerformAction('merch') && !scannedParticipant?.checked_in && !scannedParticipant?.merch_collected">
                            <button @click="performCheckInAndMerch()" :disabled="actionLoading"
                                class="w-full py-3 bg-gradient-to-r from-green-600 to-purple-600 hover:from-green-700 hover:to-purple-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Check In + Give Merch
                            </button>
                        </template>

                        <!-- Check-in (or Check-in only when merch is enabled but already collected) -->
                        <template x-if="canPerformAction('check_in') && (!merchEnabled || !canPerformAction('merch') || scannedParticipant?.merch_collected)">
                            <button @click="performAction('check_in')" :disabled="scannedParticipant?.checked_in || actionLoading"
                                class="w-full py-3 rounded-xl font-bold transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="scannedParticipant?.checked_in ? 'bg-gray-300 text-gray-600' : 'bg-green-600 hover:bg-green-700 text-white'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="scannedParticipant?.checked_in ? 'Already Checked In' : 'Check In'"></span>
                            </button>
                        </template>

                        <!-- Merch Only -->
                        <template x-if="merchEnabled && canPerformAction('merch') && (!canPerformAction('check_in') || scannedParticipant?.checked_in)">
                            <button @click="performAction('give_merch')" :disabled="scannedParticipant?.merch_collected || actionLoading"
                                class="w-full py-3 rounded-xl font-bold transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="scannedParticipant?.merch_collected ? 'bg-gray-300 text-gray-600' : 'bg-purple-600 hover:bg-purple-700 text-white'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span x-text="scannedParticipant?.merch_collected ? 'Already Collected' : 'Give Merch'"></span>
                            </button>
                        </template>

                        <!-- Payment Actions -->
                        <template x-if="canPerformAction('payment') && scannedParticipant?.status !== 'verified'">
                            <div class="flex gap-2">
                                <button @click="performAction('verify_payment')" :disabled="actionLoading"
                                    class="flex-1 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50">
                                    Accept Payment
                                </button>
                                <button @click="performAction('deny_payment')" :disabled="actionLoading"
                                    class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50">
                                    Deny
                                </button>
                            </div>
                        </template>

                        <!-- Location Actions -->
                        <template x-if="canPerformAction('location')">
                            <div class="flex gap-2">
                                <button @click="performAction('log_location', {direction: 'enter'})" :disabled="actionLoading"
                                    class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50">
                                    Enter
                                </button>
                                <button @click="performAction('log_location', {direction: 'exit'})" :disabled="actionLoading"
                                    class="flex-1 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50">
                                    Exit
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Recent Scans / Logs -->
            <div class="recent-scans bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Recent Scans
                    </h3>
                    <span class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold rounded-full"
                        x-text="recentScans.length"></span>
                </div>
                <div id="recentScans" class="divide-y divide-gray-100 dark:divide-gray-700 max-h-72 lg:max-h-96 overflow-y-auto">
                    <template x-if="recentScans.length === 0">
                        <div class="p-6 sm:p-8 text-center">
                            <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <p class="text-gray-500 text-sm">No scans yet</p>
                            <p class="text-gray-400 text-xs mt-1">Scan a QR code or use OCR to get started</p>
                        </div>
                    </template>
                    <template x-for="scan in recentScans" :key="scan.id">
                        <div class="px-4 py-3 sm:px-5 sm:py-4 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                            @click="lookupById(scan.regId)">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-base flex-shrink-0"
                                :class="getActionClass(scan.action)">
                                <span x-text="getActionIcon(scan.action)"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 dark:text-white text-sm truncate" x-text="scan.name"></div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs mt-0.5" x-text="getActionLabel(scan.action)"></div>
                            </div>
                            <div class="text-gray-400 text-xs font-medium" x-text="formatTime(scan.time)"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Info Modal -->
    <div x-show="showInfoModal" x-cloak
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="showInfoModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col"
            @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">
            
            <!-- Header with Participant Avatar -->
            <div class="relative bg-gradient-to-br from-amber-500 via-amber-600 to-orange-600 px-6 py-8 flex-shrink-0">
                <button @click="showInfoModal = false"
                    class="absolute top-4 right-4 p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-colors z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                
                <div class="flex items-center gap-5 pr-12">
                    <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-xl flex-shrink-0 border-2 border-white/30">
                        <span class="text-white font-bold text-3xl" x-text="fullInfo?.registration?.data?.full_name?.charAt(0)?.toUpperCase() || '?'"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl sm:text-3xl font-bold text-white mb-1 break-words line-clamp-2" x-text="fullInfo?.registration?.data?.full_name || 'Unknown Participant'"></h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-sm font-mono rounded-lg border border-white/30 break-all"
                                x-text="fullInfo?.registration?.id || ''"></span>
                            <span class="px-3 py-1 rounded-lg text-sm font-bold whitespace-nowrap"
                                :class="{
                                    'bg-green-500 text-white': fullInfo?.registration?.status === 'verified',
                                    'bg-yellow-500 text-white': fullInfo?.registration?.status?.includes('pending'),
                                    'bg-red-500 text-white': fullInfo?.registration?.status === 'rejected'
                                }"
                                x-text="fullInfo?.registration?.status?.toUpperCase() || 'UNKNOWN'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto scrollbar-thin">
                <!-- Quick Stats Cards -->
                <div class="p-6 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="w-10 h-10 rounded-xl mx-auto mb-2 flex items-center justify-center"
                                :class="fullInfo?.orchestrator_status?.checked_in ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700'">
                                <svg class="w-5 h-5" :class="fullInfo?.orchestrator_status?.checked_in ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Check-in</div>
                            <div class="text-sm font-bold mt-1" :class="fullInfo?.orchestrator_status?.checked_in ? 'text-green-600' : 'text-gray-400'"
                                x-text="fullInfo?.orchestrator_status?.checked_in ? 'Yes' : 'No'"></div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700" x-show="merchEnabled">
                            <div class="w-10 h-10 rounded-xl mx-auto mb-2 flex items-center justify-center"
                                :class="fullInfo?.orchestrator_status?.merch_collected ? 'bg-purple-100 dark:bg-purple-900/30' : 'bg-gray-100 dark:bg-gray-700'">
                                <svg class="w-5 h-5" :class="fullInfo?.orchestrator_status?.merch_collected ? 'text-purple-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Merchandise</div>
                            <div class="text-sm font-bold mt-1" :class="fullInfo?.orchestrator_status?.merch_collected ? 'text-purple-600' : 'text-gray-400'"
                                x-text="fullInfo?.orchestrator_status?.merch_collected ? 'Collected' : 'Not Yet'"></div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 mx-auto mb-2 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Events</div>
                            <div class="text-sm font-bold text-blue-600 mt-1" x-text="fullInfo?.events?.length || 0"></div>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 mx-auto mb-2 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Location</div>
                            <div class="text-sm font-bold text-amber-600 mt-1 truncate px-1" x-text="fullInfo?.orchestrator_status?.current_location || 'None'"></div>
                        </div>
                    </div>
                </div>

                <!-- Registration Details -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Registration Details
                    </h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <template x-for="(value, key) in fullInfo?.registration?.data || {}" :key="key">
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 min-w-0">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-1 truncate"
                                    x-text="key.replace(/_/g, ' ')"></div>
                                <div class="text-gray-900 dark:text-white font-semibold break-words overflow-wrap-anywhere" x-text="value || '-'"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Activity Timeline
                    </h3>
                    
                    <div class="space-y-3 max-h-80 overflow-y-auto scrollbar-thin">
                        <template x-for="(event, index) in fullInfo?.events || []" :key="event.id">
                            <div class="relative flex items-start gap-4 group">
                                <!-- Timeline line -->
                                <div class="absolute left-5 top-12 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"
                                    x-show="index < (fullInfo?.events?.length - 1)"></div>
                                
                                <!-- Event icon -->
                                <div class="relative z-10 w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold shadow-sm flex-shrink-0"
                                    :class="getActionClass(event.type)">
                                    <span x-text="getActionIcon(event.type)"></span>
                                </div>
                                
                                <!-- Event details -->
                                <div class="flex-1 min-w-0 bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 group-hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="font-bold text-gray-900 dark:text-white break-words flex-1 min-w-0" x-text="getActionLabel(event.type)"></div>
                                        <div class="text-xs text-gray-400 font-medium whitespace-nowrap flex-shrink-0" x-text="formatDateTime(event.timestamp)"></div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="flex items-center gap-1 min-w-0">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="truncate" x-text="event.operator"></span>
                                        </span>
                                        <template x-if="event.location">
                                            <span class="flex items-center gap-1 min-w-0">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                </svg>
                                                <span class="truncate" x-text="event.location"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <template x-if="!fullInfo?.events?.length">
                            <div class="text-center py-12">
                                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">No activity recorded yet</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Events will appear here once actions are performed</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Picker Modal -->
    <div x-show="showLocationPicker" x-cloak
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="showLocationPicker = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[85vh] flex flex-col" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Select Location</h2>
                <button @click="showLocationPicker = false"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($availableStations as $station): ?>
                        <button @click="setLocation('<?= htmlspecialchars($station['label']) ?>')"
                            class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all text-left flex-shrink-0">
                            <div class="text-2xl mb-1"><?= htmlspecialchars($station['emoji'] ?? '') ?></div>
                            <div class="font-bold text-gray-900 dark:text-white text-sm truncate"><?= htmlspecialchars($station['label']) ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Styles -->
<style>
    [x-cloak] { display: none !important; }
    .touch-target { min-height: 44px; min-width: 44px; }
    .camera-aspect { position: relative; width: 100%; padding-bottom: 75%; }
    @media (min-width: 640px) { .camera-aspect { padding-bottom: 66.67%; } }
    @media (min-width: 1024px) { .camera-aspect { padding-bottom: 75%; } }
    .corner { position: absolute; width: 24px; height: 24px; border-color: #f59e0b; border-style: solid; border-width: 0; }
    
    /* Text overflow utilities */
    .overflow-wrap-anywhere { overflow-wrap: anywhere; word-break: break-word; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .corner-tl { top: -2px; left: -2px; border-top-width: 4px; border-left-width: 4px; border-top-left-radius: 12px; }
    .corner-tr { top: -2px; right: -2px; border-top-width: 4px; border-right-width: 4px; border-top-right-radius: 12px; }
    .corner-bl { bottom: -2px; left: -2px; border-bottom-width: 4px; border-left-width: 4px; border-bottom-left-radius: 12px; }
    .corner-br { bottom: -2px; right: -2px; border-bottom-width: 4px; border-right-width: 4px; border-bottom-right-radius: 12px; }
    .scan-line { position: absolute; left: 20%; right: 20%; height: 2px; background: linear-gradient(90deg, transparent 0%, #f59e0b 50%, transparent 100%); opacity: 0.8; animation: scanLine 2s ease-in-out infinite; pointer-events: none; }
    @keyframes scanLine { 0%, 100% { top: 15%; } 50% { top: 85%; } }
    
    /* Custom scrollbar for modals */
    .scrollbar-thin::-webkit-scrollbar { width: 6px; }
    .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
    .scrollbar-thin::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    .dark .scrollbar-thin::-webkit-scrollbar-thumb { background: #4b5563; }
    .dark .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    .result-panel { animation: slideUp 0.3s ease-out; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<!-- jsQR Library -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<?php if ($ocrMode === 'tesseract'): ?>
<!-- Tesseract.js OCR Library -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@7/dist/tesseract.min.js"></script>
<?php endif; ?>

<script>
// Configuration
window.ORCHESTRATOR_CONFIG = {
    formId: <?= json_encode($activeFormId) ?>,
    csrfToken: <?= json_encode($csrfToken) ?>,
    adminPath: '<?= ADMIN_PATH ?>',
    apiUrl: '<?= ADMIN_PATH ?>/forms/orchestrator/api.php',
    operatorName: <?= json_encode($operatorName) ?>,
    operatorLocation: <?= json_encode($operatorLocation) ?>,
    permissionLevel: <?= json_encode($permissionLevel) ?>,
    allowedActions: <?= json_encode($permissionLevel === 'normal' ? $allowedActions : OrchestratorAccountRepository::getAllActions()) ?>,
    isOrchestratorUser: <?= json_encode($isOrchestratorUser) ?>,
    merchEnabled: <?= json_encode($merchEnabled ?? true) ?>,
    ocrMode: <?= json_encode($ocrMode ?? 'tesseract') ?>
};
</script>

<!-- Load scanner JavaScript -->
<script src="<?= ADMIN_PATH ?>/forms/orchestrator/assets/scanner.js?v=<?= time() ?>"></script>
