<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $title ?? 'Forms - OSIS' ?>
    </title>
    <link rel="icon" type="image/png" href="<?= asset('assets/images/osis.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Simple Header for Standalone Mode -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14 sm:h-16">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <div
                            class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-green-600 to-emerald-600 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h1 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white truncate">
                                Forms Manager</h1>
                            <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 hidden xs:block">OSIS SMAN
                                1 Bantul</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4">
                        <?php 
                        // Determine username to display - could be forms admin or orchestrator
                        $displayName = $_SESSION['forms_username'] ?? ($_SESSION['orchestrator_account']['display_name'] ?? 'User');
                        $isOrchestratorOnly = isset($_SESSION['orchestrator_account']) && !isset($_SESSION['forms_logged_in']);
                        $logoutUrl = $isOrchestratorOnly 
                            ? '/admin/forms/orchestrator/login.php?form_id=' . urlencode($_SESSION['orchestrator_form_id'] ?? '')
                            : '/admin/forms/logout.php';
                        ?>
                        <span
                            class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 hidden sm:block truncate max-w-[120px]">
                            <?= htmlspecialchars($displayName) ?>
                        </span>
                        <?php if ($isOrchestratorOnly): ?>
                            <button onclick="logoutOrchestrator()"
                                class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                            <script>
                            function logoutOrchestrator() {
                                if (confirm('Are you sure you want to log out?')) {
                                    fetch('/admin/forms/orchestrator/api.php?action=orchestrator_logout', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({})
                                    }).then(() => {
                                        window.location.href = '<?= $logoutUrl ?>';
                                    });
                                }
                            }
                            </script>
                        <?php else: ?>
                            <a href="<?= $logoutUrl ?>"
                                class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="hidden sm:inline">Logout</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto">
            <div class="p-4 sm:p-6 lg:p-8">
                <?= $content ?>
            </div>
        </main>
    </div>
</body>

</html>