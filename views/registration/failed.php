<?php
/**
 * Failed Registration View
 * Shows when registration is marked as failed after max verification attempts
 */
ob_start();
?>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 md:p-10 text-center">
    <div class="w-24 h-24 mx-auto mb-6 bg-rose-100 dark:bg-rose-900/30 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    </div>

    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
        Verification Failed
    </h2>

    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
        Your registration could not be verified after multiple attempts. This may be due to unclear documents or
        mismatched information.
    </p>

    <div
        class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-6 max-w-md mx-auto mb-8">
        <h3 class="font-bold text-rose-800 dark:text-rose-200 mb-2">What happened?</h3>
        <p class="text-rose-700 dark:text-rose-300 text-sm">
            Maximum verification attempts (2) were reached without successful verification. Please ensure your documents
            are clear and information matches your registration.
        </p>
    </div>

    <div class="space-y-4">
        <a href="<?= htmlspecialchars($basePath) ?>?restart=true"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Try Again with New Registration
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