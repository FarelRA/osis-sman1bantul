<?php
/**
 * Expired Registration View
 */
ob_start();
?>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 md:p-10 text-center max-w-lg mx-auto">
    <!-- Expired Icon -->
    <div class="w-24 h-24 mx-auto mb-6 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
        Registration Expired
    </h2>

    <p class="text-gray-600 dark:text-gray-400 mb-8">
        Your registration session has timed out. Don't worry, you can start a new registration.
    </p>

    <a href="<?= htmlspecialchars($basePath) ?>?restart=1" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-cyan-600 
              hover:from-blue-700 hover:to-cyan-700 text-white font-bold py-4 px-8 
              rounded-xl transition-all shadow-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Start New Registration
    </a>
</div>

<?php
$stepContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>