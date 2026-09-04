<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_PATH . '/login.php');
    exit;
}

// Handle clear analytics
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid form token, please try again';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    $jsonlFile = BASE_PATH . '/data/analytics.jsonl';
    if (file_exists($jsonlFile)) unlink($jsonlFile);
    header('Location: ' . ADMIN_PATH . '/analytics.php');
    exit;
}

$range = $_GET['range'] ?? '7d';
$stats = getAnalyticsStats($range);
$csrfToken = CSRF::generate();
$title = 'Analytics - Admin';
ob_start();
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 sm:mb-8">
    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Analytics</h2>
    <form method="POST" onsubmit="return confirm('Clear all analytics data?')">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <button type="submit" name="clear"
            class="px-4 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600 w-full sm:w-auto">Clear
            Data</button>
    </form>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-1">Total Visits</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_visits']) ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-1">Unique Visitors</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    <?= number_format($stats['unique_visitors']) ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-1">Pages Tracked</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= count($stats['pages']) ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Visits by Date Chart -->
<div class="card p-4 sm:p-6 mb-6 sm:mb-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Visits Over Time</h3>
        <div class="flex gap-2 flex-wrap w-full sm:w-auto">
            <?php
            $ranges = ['12h' => '12 Hours', '24h' => '24 Hours', '3d' => '3 Days', '7d' => '7 Days', '30d' => '30 Days', '1y' => '1 Year', '2y' => '2 Years'];
            foreach ($ranges as $key => $label):
                ?>
                <a href="?range=<?= $key ?>"
                    class="px-3 py-1 rounded text-xs <?= $range === $key ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (empty($stats['visits_by_period'])): ?>
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            <p>No data available for this time range</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <div class="flex items-end gap-2 px-4" style="min-width: 600px; height: 256px;">
                <?php
                $max_visits = max($stats['visits_by_period']);
                foreach ($stats['visits_by_period'] as $period => $count):
                    $height_px = $max_visits > 0 ? round(($count / $max_visits) * 240) : 0;
                    $min_height_px = $count > 0 ? 10 : 0;
                    $final_height_px = max($height_px, $min_height_px);
                    ?>
                    <div class="flex-1 flex flex-col items-center group min-w-[40px]">
                        <div class="relative w-full bg-blue-500 rounded-t hover:bg-blue-600 transition-colors"
                            style="height: <?= $final_height_px ?>px;">
                            <div
                                class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                <?= $count ?> visits
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 whitespace-nowrap">
                            <?= htmlspecialchars($period) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
    <!-- Most Visited Pages -->
    <div class="card p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Most Visited Pages</h3>
            <button onclick="document.getElementById('pagesModal').classList.remove('hidden')"
                class="px-3 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">View All</button>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php foreach ($stats['pages'] as $page => $count): ?>
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?= htmlspecialchars($page) ?>
                        </p>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 h-2 rounded-full"
                                style="width: <?= ($count / $stats['total_visits']) * 100 ?>%"></div>
                        </div>
                    </div>
                    <span class="ml-4 text-sm font-semibold text-gray-600 dark:text-gray-400"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Visits -->
    <div class="card p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Recent Visits</h3>
            <button onclick="document.getElementById('visitsModal').classList.remove('hidden')"
                class="px-3 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">View All</button>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php foreach ($stats['recent_visits'] as $visit): ?>
                <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                <?= htmlspecialchars($visit['page']) ?>
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                IP: <?= htmlspecialchars($visit['ip']) ?>
                            </p>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap ml-2">
                            <?= date('M d, H:i', strtotime($visit['timestamp'])) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- All Pages Modal -->
<div id="pagesModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">All Visited Pages</h3>
            <button onclick="document.getElementById('pagesModal').classList.add('hidden')"
                class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">Close</button>
        </div>
        <div class="space-y-3">
            <?php foreach ($stats['pages'] as $page => $count): ?>
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($page) ?></p>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 h-2 rounded-full"
                                style="width: <?= ($count / $stats['total_visits']) * 100 ?>%"></div>
                        </div>
                    </div>
                    <span class="ml-4 text-sm font-semibold text-gray-600 dark:text-gray-400"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- All Visits Modal -->
<div id="visitsModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">All Recent Visits</h3>
            <button onclick="document.getElementById('visitsModal').classList.add('hidden')"
                class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">Close</button>
        </div>
        <div class="space-y-3">
            <?php foreach ($stats['recent_visits'] as $visit): ?>
                <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($visit['page']) ?>
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                IP: <?= htmlspecialchars($visit['ip']) ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                <?= htmlspecialchars(substr($visit['user_agent'], 0, 80)) ?>...
                            </p>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap ml-2">
                            <?= date('M d, H:i', strtotime($visit['timestamp'])) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>