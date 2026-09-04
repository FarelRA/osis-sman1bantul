<?php
/**
 * Global Settings Module
 * Handles AI configuration only. Payment settings are per-form.
 */
require_once __DIR__ . '/../../src/Config.php';

// Auth Check - Allow either admin login OR forms login
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isFormsAdmin = isset($_SESSION['forms_logged_in']);

if (!$isFullAdmin && !$isFormsAdmin) {
    header('Location: ' . ADMIN_PATH . '/forms/login.php');
    exit;
}

require_once __DIR__ . '/../../src/Core/CSRF.php';
require_once __DIR__ . '/../../src/Core/FormConstants.php';

// Load forms.json for settings
$formsFile = BASE_PATH . '/data/forms.json';
$formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
$forms = $formData['forms'] ?? $formData;
$settings = $formData['settings'] ?? [];

$settingsSuccess = '';
$settingsError = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            $settingsError = 'Invalid CSRF token';
        } else {
            // Only save AI configuration (payment settings are now per-form)
            $settings['ai_api_key'] = $_POST['ai_api_key'] ?? '';
            $settings['ai_api_url'] = $_POST['ai_api_url'] ?? FormConstants::DEFAULT_AI_API_URL;
            $settings['ai_model'] = $_POST['ai_model'] ?? FormConstants::DEFAULT_AI_MODEL;
            $settings['ai_extra_params'] = $_POST['ai_extra_params'] ?? '';

            // Save stations configuration
            $stationsRaw = $_POST['stations'] ?? '';
            $stationsLines = array_filter(array_map('trim', explode("\n", $stationsRaw)));
            $parsedStations = [];
            foreach ($stationsLines as $line) {
                preg_match('/^(\X)(.*)$/u', $line, $m);
                $firstChar = $m[1] ?? '';
                $restOfLine = $m[2] ?? '';

                if (
                    $firstChar !== '' && $restOfLine !== '' &&
                    strlen($firstChar) > 1 &&
                    !preg_match('/^[\p{L}\p{N}]/u', $firstChar)
                ) {
                    $parsedStations[] = ['emoji' => $firstChar, 'label' => trim($restOfLine)];
                } else {
                    $parsedStations[] = ['emoji' => '📍', 'label' => $line];
                }
            }
            $settings['stations'] = $parsedStations;

            // Save back to forms.json
            $newData = ['forms' => $forms, 'settings' => $settings];
            if (file_put_contents($formsFile, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $settingsSuccess = 'Settings saved successfully!';
            } else {
                $settingsError = 'Failed to save settings. Check file permissions.';
            }
        }
    }
}

$title = 'Global Settings';
ob_start();
?>

<div class="w-full max-w-2xl mx-auto px-4 sm:px-0">
    <div class="mb-6 sm:mb-8">
        <a href="<?= ADMIN_PATH ?>/forms.php"
            class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 mb-3 sm:mb-4 transition-colors text-sm sm:text-base">
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Forms
        </a>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">⚙️ Global Settings</h2>
        <p class="text-gray-500 text-sm sm:text-base">Configure AI verification settings</p>
    </div>

    <?php if ($settingsSuccess): ?>
        <div class="mb-6 p-4 bg-green-100 text-green-800 rounded-xl flex items-center gap-3 text-sm sm:text-base">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <?= htmlspecialchars($settingsSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($settingsError): ?>
        <div class="mb-6 p-4 bg-red-100 text-red-800 rounded-xl flex items-center gap-3 text-sm sm:text-base">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <?= htmlspecialchars($settingsError) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
        <input type="hidden" name="action" value="save_settings">

        <!-- AI Configuration -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-xl sm:text-2xl">🤖</span>
                AI Verification Settings
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                    <input type="password" name="ai_api_key"
                        value="<?= htmlspecialchars($settings['ai_api_key'] ?? '') ?>" placeholder="sk-..."
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-0 transition-colors text-sm sm:text-base">
                    <p class="text-xs text-gray-500 mt-1">OpenAI or compatible API key for document verification</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">API URL</label>
                    <input type="url" name="ai_api_url"
                        value="<?= htmlspecialchars($settings['ai_api_url'] ?? FormConstants::DEFAULT_AI_API_URL) ?>"
                        placeholder="<?= FormConstants::DEFAULT_AI_API_URL ?>"
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-0 transition-colors text-sm sm:text-base">
                    <p class="text-xs text-gray-500 mt-1">OpenAI-compatible endpoint (default:
                        <?= FormConstants::DEFAULT_AI_API_URL ?>)
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Model</label>
                    <input type="text" name="ai_model"
                        value="<?= htmlspecialchars($settings['ai_model'] ?? FormConstants::DEFAULT_AI_MODEL) ?>"
                        placeholder="<?= FormConstants::DEFAULT_AI_MODEL ?>"
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-0 transition-colors text-sm sm:text-base">
                    <p class="text-xs text-gray-500 mt-1">Model name (default:
                        <?= FormConstants::DEFAULT_AI_MODEL ?>)
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Extra Parameters (JSON)</label>
                    <textarea name="ai_extra_params" rows="4"
                        placeholder='{"thinking_config": {"thinking_level": "MINIMAL"}, "media_resolution": "MEDIA_RESOLUTION_LOW"}'
                        class="w-full px-3 py-2.5 sm:px-4 sm:py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-0 transition-colors text-sm sm:text-base font-mono"><?= htmlspecialchars($settings['ai_extra_params'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Optional JSON object merged into the API request body. Supports provider-specific params like <code>thinking_config</code>, <code>media_resolution</code>, <code>top_k</code>, etc.</p>
                </div>
            </div>
        </div>

        <!-- Stations/Locations Configuration -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-xl sm:text-2xl">📍</span>
                Scanner Stations / Locations
            </h3>
            <p class="text-sm text-gray-500 mb-4">Configure the available stations/locations for the scanner operator
                setup. Enter one station per line. Optionally prefix with an emoji.</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Stations List</label>
                    <textarea name="stations" rows="8" placeholder="🚪 Main Entrance
📋 Registration Desk
🎁 Merch Booth
🎪 Main Hall
🅰️ Room A
🚶 Exit Gate" class="w-full px-3 py-2.5 sm:px-4 sm:py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-0 transition-colors text-sm sm:text-base font-mono"><?php
// Format existing stations back to text
$existingStations = $settings['stations'] ?? [];
if (!empty($existingStations)) {
    foreach ($existingStations as $station) {
        echo htmlspecialchars($station['emoji'] . ' ' . $station['label']) . "\n";
    }
} else {
    // Default stations
    echo "🚪 Main Entrance\n📋 Registration Desk\n🎁 Merch Booth\n🎪 Main Hall\n🅰️ Room A\n🅱️ Room B\n©️ Room C\n🚶 Exit Gate";
}
?></textarea>
                    <p class="text-xs text-gray-500 mt-1">One station per line. Format: <code
                            class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">🚪 Main Entrance</code> or just
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">Main Entrance</code>
                    </p>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit"
                class="w-full mt-6 py-3 sm:py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold rounded-xl shadow-lg transition-all text-sm sm:text-base active:scale-[0.98]">
                Save Settings
            </button>
    </form>
</div>

<?php
$content = ob_get_clean();
if ($isFullAdmin) {
    require __DIR__ . '/../layout.php';
} else {
    require __DIR__ . '/layout.php';
}
?>