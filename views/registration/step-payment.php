<?php
/**
 * Dynamic Payment Step View
 * Uses payment configuration from form settings
 */
$old = $_SESSION['flash_old'] ?? [];
unset($_SESSION['flash_old']);

$regData = $registration['data'] ?? [];
$regSettings = $form['registration_settings'] ?? [];

// Get payment configuration from form settings or fall back to global
$paymentMethod = $regData['payment_method'] ?? '';
$registrationFee = $regSettings['registration_fee'] ?? $settings['registration_fee'] ?? 150000;
$bankAccounts = $regSettings['bank_accounts'] ?? $settings['bank_accounts'] ?? [];
$offlineLocation = $regSettings['offline_payment_location'] ?? 'School Administration Office';
$offlineHours = $regSettings['offline_payment_hours'] ?? 'Monday-Friday, 08:00-15:00';
$paymentTitle = $regSettings['payment_title'] ?? 'Complete Payment';

// Payment method toggles
$onlineEnabled = $regSettings['online_payment_enabled'] ?? true;
$offlineEnabled = $regSettings['offline_payment_enabled'] ?? true;

// Auto-disable offline if deadline has passed
$offlineDeadline = $regSettings['offline_payment_deadline'] ?? null;
$offlineDeadlinePassed = false;
if ($offlineDeadline && time() > strtotime($offlineDeadline)) {
    $offlineDeadlinePassed = true;
    $offlineEnabled = false;
}

// Determine default selection if one method is disabled
$defaultMethod = $paymentMethod;
if (!$defaultMethod) {
    if ($onlineEnabled && !$offlineEnabled) {
        $defaultMethod = 'Online';
    } elseif (!$onlineEnabled && $offlineEnabled) {
        $defaultMethod = 'Offline';
    }
}

ob_start();
?>

<div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden card-hover"
    x-data="paymentFlow()">

    <!-- Card Header -->
    <div
        class="relative px-6 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-800">
        <!-- Decorative Elements -->
        <div
            class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-amber-400/30 to-orange-400/30 rounded-full blur-3xl">
        </div>

        <div class="relative flex items-start gap-4">
            <!-- Icon -->
            <div
                class="hidden sm:flex flex-shrink-0 w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 items-center justify-center shadow-lg shadow-amber-500/30">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>

            <div class="flex-1">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    <?= htmlspecialchars($paymentTitle) ?>
                </h2>
                <div class="flex items-center gap-2">
                    <span class="text-slate-600 dark:text-slate-400">Registration fee:</span>
                    <span
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 text-amber-700 dark:text-amber-300 font-bold text-lg">
                        Rp <?= number_format($registrationFee, 0, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php
    $isPendingOnline = ($registration['status'] ?? '') === 'pending_online';
    $isPendingOffline = ($registration['status'] ?? '') === 'pending_offline';
    $offlineDeadline = $regSettings['offline_payment_deadline'] ?? date('Y-m-d', strtotime(($registration['created_at'] ?? 'now') . ' +15 days'));
    ?>

    <?php if ($isPendingOffline): ?>
        <!-- Pending Offline Payment View -->
        <div class="p-6 sm:p-8">
            <div class="max-w-md mx-auto space-y-6">
                <!-- Status -->
                <div class="text-center">
                    <div
                        class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Awaiting Your Payment</h3>
                    <p class="text-slate-600 dark:text-slate-400">Please pay at the designated location</p>
                </div>

                <!-- Deadline -->
                <?php if ($offlineDeadline): ?>
                    <div
                        class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 text-center">
                        <p class="text-sm text-amber-700 dark:text-amber-300">Deadline</p>
                        <p class="text-lg font-bold text-amber-800 dark:text-amber-200">
                            <?= date('d M Y, H:i', strtotime($offlineDeadline)) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Payment Info -->
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-5 space-y-4">
                    <?php if ($offlineLocation): ?>
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Location</p>
                                <p class="font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($offlineLocation) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($offlineHours): ?>
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Hours</p>
                                <p class="font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($offlineHours) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Amount</p>
                            <p class="text-xl font-bold text-amber-600 dark:text-amber-400">Rp
                                <?= number_format($registrationFee, 0, ',', '.') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- QR Code -->
                <div
                    class="bg-white dark:bg-slate-800 rounded-xl p-6 text-center border border-slate-200 dark:border-slate-700">
                    <div class="bg-white p-3 rounded-lg shadow-lg inline-block mb-3">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($registration['id']) ?>"
                            alt="QR Code" class="w-36 h-36">
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Registration ID</p>
                    <p class="text-2xl font-mono font-bold text-slate-900 dark:text-white">
                        <?= htmlspecialchars($registration['id']) ?>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Show this QR code when making payment</p>
                </div>
            </div>
        </div>
    <?php else: ?>

        <div class="p-6 sm:p-8">
            <!-- Payment Method Selection -->
            <div x-show="!showUpload">
                <form action="<?= htmlspecialchars($basePath) ?>/step/<?= $currentStep ?>" method="POST" data-autosave>
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="grid md:grid-cols-2 gap-4 sm:gap-6 mb-8">
                        <!-- Online Payment -->
                        <label class="relative <?= $onlineEnabled ? 'cursor-pointer' : 'cursor-not-allowed' ?> group">
                            <input type="radio" name="payment_method" value="Online" x-model="selectedMethod"
                                <?= $defaultMethod === 'Online' ? 'checked' : '' ?>     <?= !$onlineEnabled ? 'disabled' : '' ?>
                                class="peer sr-only">
                            <div
                                class="h-full p-6 rounded-2xl border-2 transition-all duration-300
                                    border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900
                                    <?= $onlineEnabled ? 'peer-checked:border-blue-500 peer-checked:bg-gradient-to-br peer-checked:from-blue-50 peer-checked:to-cyan-50 dark:peer-checked:from-blue-900/20 dark:peer-checked:to-cyan-900/20 peer-checked:shadow-lg peer-checked:shadow-blue-500/20 group-hover:border-blue-300 dark:group-hover:border-blue-600' : 'opacity-50' ?>">
                                <div class="flex items-center gap-4 mb-4">
                                    <div
                                        class="w-14 h-14 bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/50 dark:to-cyan-900/50 rounded-xl flex items-center justify-center shadow-inner <?= !$onlineEnabled ? 'grayscale' : '' ?>">
                                        <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3
                                                class="text-lg font-bold text-slate-900 dark:text-white <?= !$onlineEnabled ? 'text-slate-400 dark:text-slate-500' : '' ?>">
                                                Online</h3>
                                            <?php if (!$onlineEnabled): ?>
                                                <span
                                                    class="px-2 py-0.5 text-xs font-bold rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400">Not
                                                    Available</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Instant verification</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Checkmark indicator -->
                            <?php if ($onlineEnabled): ?>
                                <div
                                    class="absolute top-10 right-10 w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 peer-checked:border-blue-500 peer-checked:bg-blue-500 transition-all flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </label>

                        <!-- Offline Payment -->
                        <label class="relative <?= $offlineEnabled ? 'cursor-pointer' : 'cursor-not-allowed' ?> group">
                            <input type="radio" name="payment_method" value="Offline" x-model="selectedMethod"
                                <?= $defaultMethod === 'Offline' ? 'checked' : '' ?>     <?= !$offlineEnabled ? 'disabled' : '' ?>
                                class="peer sr-only">
                            <div
                                class="h-full p-6 rounded-2xl border-2 transition-all duration-300
                                    border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900
                                    <?= $offlineEnabled ? 'peer-checked:border-green-500 peer-checked:bg-gradient-to-br peer-checked:from-green-50 peer-checked:to-emerald-50 dark:peer-checked:from-green-900/20 dark:peer-checked:to-emerald-900/20 peer-checked:shadow-lg peer-checked:shadow-green-500/20 group-hover:border-green-300 dark:group-hover:border-green-600' : 'opacity-50' ?>">
                                <div class="flex items-center gap-4 mb-4">
                                    <div
                                        class="w-14 h-14 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/50 dark:to-emerald-900/50 rounded-xl flex items-center justify-center shadow-inner <?= !$offlineEnabled ? 'grayscale' : '' ?>">
                                        <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3
                                                class="text-lg font-bold text-slate-900 dark:text-white <?= !$offlineEnabled ? 'text-slate-400 dark:text-slate-500' : '' ?>">
                                                Offline</h3>
                                            <?php if (!$offlineEnabled): ?>
                                                <?php if ($offlineDeadlinePassed): ?>
                                                    <span
                                                        class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">Deadline
                                                        Passed</span>
                                                <?php else: ?>
                                                    <span
                                                        class="px-2 py-0.5 text-xs font-bold rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400">Not
                                                        Available</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Pay in person</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Checkmark indicator -->
                            <?php if ($offlineEnabled): ?>
                                <div
                                    class="absolute top-10 right-10 w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 peer-checked:border-green-500 peer-checked:bg-green-500 transition-all flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </label>
                    </div>

                    <!-- Online Payment Info -->
                    <div x-show="selectedMethod === 'Online'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform -translate-y-4"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="mb-8">
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 
                                rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
                            <h4 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Transfer Details
                            </h4>
                            <div class="space-y-4">
                                <?php foreach ($bankAccounts as $index => $acc): ?>
                                    <div
                                        class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm">
                                        <div
                                            class="p-3 bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-700 dark:to-slate-700/50 border-b border-slate-200 dark:border-slate-600 flex justify-between items-center">
                                            <span
                                                class="font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($acc['bank_name'] ?? 'Bank') ?></span>
                                            <span
                                                class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300 rounded-full font-bold">
                                                Option <?= $index + 1 ?>
                                            </span>
                                        </div>
                                        <div class="p-4 space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-slate-500 dark:text-slate-400">Account Number</span>
                                                <button type="button"
                                                    @click="copyToClipboard('<?= htmlspecialchars($acc['account_number'] ?? '') ?>')"
                                                    class="font-mono font-bold text-slate-900 dark:text-white flex items-center gap-2 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors group">
                                                    <?= htmlspecialchars($acc['account_number'] ?? '-') ?>
                                                    <svg class="w-4 h-4 opacity-50 group-hover:opacity-100 transition-opacity"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-slate-500 dark:text-slate-400">Account Name</span>
                                                <span
                                                    class="font-medium text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($acc['account_holder'] ?? '-') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div
                                    class="flex justify-between items-center p-4 bg-blue-100 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-800">
                                    <span class="text-slate-700 dark:text-slate-300 font-bold">Total Amount</span>
                                    <span class="font-bold text-blue-600 dark:text-blue-400 text-xl">Rp
                                        <?= number_format($registrationFee, 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <div class="mt-6 text-center">
                                <button type="submit" class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 bg-[length:200%_100%]
                                       hover:bg-right text-white font-bold py-4 px-8 rounded-xl transition-all duration-500 
                                       shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40
                                       transform hover:-translate-y-0.5 btn-shine">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    I've Transferred - Upload Proof
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Offline Payment Info -->
                    <div x-show="selectedMethod === 'Offline'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform -translate-y-4"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="mb-8">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 
                                rounded-2xl p-6 border border-green-200 dark:border-green-800">
                            <h4 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                Payment Location
                            </h4>
                            <div class="space-y-3">
                                <?php if (!empty($offlineLocation)): ?>
                                    <div
                                        class="flex items-start gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                        <div class="flex-shrink-0 p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-slate-500 dark:text-slate-400 text-sm">Location</span>
                                            <p class="font-bold text-slate-900 dark:text-white">
                                                <?= htmlspecialchars($offlineLocation) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($offlineHours)): ?>
                                    <div
                                        class="flex items-start gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                        <div class="flex-shrink-0 p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-slate-500 dark:text-slate-400 text-sm">Hours</span>
                                            <p class="font-bold text-slate-900 dark:text-white">
                                                <?= htmlspecialchars($offlineHours) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div
                                    class="flex items-start gap-4 p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <div class="flex-shrink-0 p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 dark:text-slate-400 text-sm">Amount</span>
                                        <p class="font-bold text-green-600 dark:text-green-400 text-xl">Rp
                                            <?= number_format($registrationFee, 0, ',', '.') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 text-center">
                                <button type="submit" class="inline-flex items-center gap-3 bg-gradient-to-r from-green-600 via-emerald-600 to-green-600 bg-[length:200%_100%]
                                       hover:bg-right text-white font-bold py-4 px-8 rounded-xl transition-all duration-500 
                                       shadow-lg shadow-green-500/30 hover:shadow-xl hover:shadow-green-500/40
                                       transform hover:-translate-y-0.5 btn-shine">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Confirm Offline Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Payment Proof Upload (Online) -->
            <div x-show="showUpload && selectedMethod === 'Online'" x-transition x-data="paymentUpload()" class="space-y-6">
                <!-- Upload Area -->
                <div x-show="!isVerifying && !isVerified && !hasError">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4 text-center">
                        Upload a screenshot of your successful transfer to verify payment.
                    </p>

                    <div class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all duration-300 cursor-pointer
                            border-slate-300 dark:border-slate-600 
                            hover:border-blue-400 dark:hover:border-blue-500
                            hover:bg-blue-50/50 dark:hover:bg-blue-900/10" @click="$refs.paymentFile.click()"
                        @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop($event)"
                        :class="{ 'border-blue-500 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20': isDragging }">

                        <input type="file" x-ref="paymentFile" @change="handleFileSelect($event)" accept="image/*"
                            class="hidden">

                        <div x-show="!previewUrl">
                            <div
                                class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="font-semibold text-slate-700 dark:text-slate-300 mb-2">Click to upload payment proof
                            </p>
                        </div>

                        <!-- Preview -->
                        <div x-show="previewUrl" class="relative inline-block">
                            <div class="relative inline-block rounded-xl overflow-hidden shadow-lg">
                                <img :src="previewUrl" class="max-w-full sm:max-w-xs rounded-xl">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                            </div>
                            <button x-show="!hasError" @click.stop="clearPreview()"
                                class="absolute top-3 right-3 p-2 bg-rose-500 hover:bg-rose-600 text-white rounded-full shadow-lg transition-all transform hover:scale-105">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Verify Button -->
                    <div x-show="previewUrl" class="mt-6 text-center">
                        <button @click="uploadAndVerify()" class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 bg-[length:200%_100%]
                               hover:bg-right text-white font-bold py-4 px-8 rounded-xl transition-all duration-500 
                               shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40
                               transform hover:-translate-y-0.5 btn-shine">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Verify Payment
                        </button>
                    </div>
                </div>

                <!-- AI Verification Loading -->
                <div x-show="isVerifying" class="py-8">
                    <div class="max-w-md mx-auto">
                        <div class="ai-glow-container mb-8">
                            <div class="relative rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                                <img :src="previewUrl" class="w-full rounded-xl">
                                <div class="ai-scanning-line"></div>
                                <div class="absolute inset-0 bg-gradient-to-t from-blue-600/20 to-transparent"></div>
                            </div>
                        </div>

                        <div class="text-center mb-6">
                            <div class="inline-flex items-center gap-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 
                                    px-4 py-2 rounded-full text-sm font-semibold">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="verificationStatus" class="typing-dots">Verifying payment</span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(step, index) in verificationSteps" :key="index">
                                <div class="flex items-center gap-3 p-4 rounded-xl transition-all duration-300" :class="{ 
                                     'bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800': step.done,
                                     'bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border border-blue-200 dark:border-blue-800': step.active && !step.done,
                                     'bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700': !step.active && !step.done
                                 }">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                                        :class="{
                                         'bg-gradient-to-br from-green-400 to-emerald-500 text-white shadow-lg shadow-green-500/30': step.done,
                                         'bg-gradient-to-br from-blue-500 to-cyan-600 text-white shadow-lg shadow-blue-500/30 animate-pulse': step.active && !step.done,
                                         'bg-slate-200 dark:bg-slate-700 text-slate-500': !step.active && !step.done
                                     }">
                                        <template x-if="step.done">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </template>
                                        <template x-if="!step.done">
                                            <span x-text="index + 1"></span>
                                        </template>
                                    </div>
                                    <span class="font-medium" :class="{ 
                                          'text-green-700 dark:text-green-300': step.done,
                                          'text-blue-700 dark:text-blue-300': step.active && !step.done,
                                          'text-slate-500 dark:text-slate-400': !step.active && !step.done
                                      }" x-text="step.text"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Verification Complete -->
                <div x-show="isVerified" class="py-8 text-center">
                    <div
                        class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-full flex items-center justify-center shadow-lg shadow-green-500/20">
                        <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Payment Verified!</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">Your registration is now complete.</p>
                    <a href="<?= htmlspecialchars($basePath) ?>" class="inline-flex items-center gap-3 bg-gradient-to-r from-green-600 via-emerald-600 to-green-600 bg-[length:200%_100%]
                           hover:bg-right text-white font-bold py-4 px-8 rounded-xl transition-all duration-500 
                           shadow-lg shadow-green-500/30 hover:shadow-xl hover:shadow-green-500/40
                           transform hover:-translate-y-0.5 btn-shine">
                        View Registration Details
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>

                <!-- Error State -->
                <div x-show="hasError" class="py-4">
                    <div
                        class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 
                            border border-amber-200 dark:border-amber-800 rounded-2xl p-6 shadow-lg shadow-amber-500/10">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 p-3 bg-amber-100 dark:bg-amber-800 rounded-xl">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-amber-800 dark:text-amber-200">Verification Failed</h3>
                                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1" x-text="errorMessage"></p>
                                <p class="text-sm text-amber-600 dark:text-amber-400 mt-2">
                                    Please try uploading a clearer image of your transfer proof.
                                </p>
                                <button @click="hasError = false; clearPreview()" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-amber-100 hover:bg-amber-200
                                dark:bg-amber-800 dark:hover:bg-amber-700 text-amber-800 dark:text-amber-200 font-semibold
                                rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Try Again
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function paymentFlow() {
        return {
            selectedMethod: '<?= htmlspecialchars($defaultMethod) ?>',
            showUpload: <?= $isPendingOnline ? 'true' : 'false' ?>,
            copied: false,

            copyToClipboard(text) {
                navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);

                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-slate-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-fade-in-up';
                toast.innerHTML = '<span class="flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Copied to clipboard!</span>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            }
        };
    }

    function paymentUpload() {
        return {
            previewUrl: null,
            selectedFile: null,
            isDragging: false,
            isVerifying: false,
            isVerified: false,
            hasError: false,
            errorMessage: '',
            verificationStatus: 'Verifying payment',
            verificationSteps: [
                { text: 'Reading transfer details', active: false, done: false },
                { text: 'Checking amount', active: false, done: false },
                { text: 'Verifying account', active: false, done: false },
                { text: 'Final confirmation', active: false, done: false }
            ],

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.validateAndSetFile(file);
            },

            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.validateAndSetFile(file);
                }
            },

            async validateAndSetFile(file) {
                if (!file.type.startsWith('image/')) {
                    this.hasError = true;
                    this.errorMessage = 'Please upload an image file (JPG, PNG).';
                    return;
                }

                this.hasError = false;

                const maxSize = 1.5 * 1024 * 1024;
                if (file.size > maxSize) {
                    try {
                        const compressed = await this.compressImage(file, maxSize);
                        this.setFile(compressed);
                    } catch (e) {
                        console.error('Compression failed:', e);
                        this.hasError = true;
                        this.errorMessage = 'Failed to compress image. Please try a smaller file.';
                    }
                } else {
                    this.setFile(file);
                }
            },

            async compressImage(file, maxSize) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => {
                        let { width, height } = img;
                        const maxDim = 1920;
                        if (width > maxDim || height > maxDim) {
                            if (width > height) {
                                height = Math.round((height * maxDim) / width);
                                width = maxDim;
                            } else {
                                width = Math.round((width * maxDim) / height);
                                height = maxDim;
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        let quality = 0.8;
                        const tryCompress = () => {
                            canvas.toBlob(
                                (blob) => {
                                    if (blob.size <= maxSize || quality <= 0.3) {
                                        const compressedFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                                            type: 'image/jpeg',
                                            lastModified: Date.now()
                                        });
                                        resolve(compressedFile);
                                    } else {
                                        quality -= 0.1;
                                        tryCompress();
                                    }
                                },
                                'image/jpeg',
                                quality
                            );
                        };
                        tryCompress();
                    };
                    img.onerror = reject;
                    img.src = URL.createObjectURL(file);
                });
            },

            setFile(file) {
                this.selectedFile = file;
                this.previewUrl = URL.createObjectURL(file);
            },

            clearPreview() {
                this.previewUrl = null;
                this.selectedFile = null;
            },

            async uploadAndVerify() {
                if (!this.selectedFile) return;

                this.isVerifying = true;
                this.hasError = false;

                // Start upload immediately
                const formData = new FormData();
                formData.append('payment_proof', this.selectedFile);
                formData.append('field', 'payment_proof');

                const uploadPromise = fetch('<?= htmlspecialchars($basePath) ?>/upload', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());

                // Animation runs in parallel with upload
                const animationPromise = (async () => {
                    const base = 500;
                    for (let i = 0; i < this.verificationSteps.length; i++) {
                        this.verificationSteps[i].active = true;
                        this.verificationStatus = this.verificationSteps[i].text;
                        await this.sleep(base * (i + 1));
                        this.verificationSteps[i].done = true;
                        this.verificationSteps[i].active = false;
                    }
                })();

                try {
                    const [result] = await Promise.all([uploadPromise, animationPromise]);

                    if (result.verified) {
                        this.isVerified = true;
                    } else if (result.failed) {
                        window.location.href = '<?= htmlspecialchars($basePath) ?>';
                    } else if (result.review) {
                        window.location.reload();
                    } else {
                        this.hasError = true;
                        this.errorMessage = result.message || 'Could not automatically verify the payment.';
                    }
                } catch (e) {
                    this.hasError = true;
                    this.errorMessage = 'Upload failed. Please try again.';
                }

                this.isVerifying = false;
            },

            sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            },

            retry() {
                this.hasError = false;
                this.previewUrl = null;
                this.selectedFile = null;
                this.verificationSteps.forEach(s => { s.active = false; s.done = false; });
            }
        };
    }
</script>

<?php
$stepContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>