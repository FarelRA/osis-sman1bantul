<?php
/**
 * Rejected Registration View
 * Shows rejection message and reason
 */
ob_start();
?>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 md:p-10 text-center">
    <div class="w-24 h-24 mx-auto mb-6 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
        Registration Rejected
    </h2>

    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
        We're sorry, but your registration could not be approved at this time.
    </p>

    <?php if (!empty($registration['rejection_reason'])): ?>
        <div
            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 max-w-md mx-auto mb-8 text-left">
            <h3 class="font-bold text-red-800 dark:text-red-200 mb-2">Reason for Rejection:</h3>
            <p class="text-red-700 dark:text-red-300">
                <?= htmlspecialchars($registration['rejection_reason']) ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="space-y-4">
        <a href="<?= htmlspecialchars($basePath) ?>?restart=true"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Start New Registration
        </a>

        <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4 max-w-md mx-auto mt-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Registration ID</p>
            <p class="text-xl font-mono font-bold text-gray-900 dark:text-white">
                <?= htmlspecialchars($registration['id']) ?>
            </p>
        </div>
    </div>
</div>

<?php
$stepContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>