<?php
session_start();
require_once __DIR__ . '/../src/Config.php';

// Auth Check - Allow either admin login OR forms login
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isFormsAdmin = isset($_SESSION['forms_logged_in']);

if (!$isFullAdmin && !$isFormsAdmin) {
    header('Location: /admin/forms/login.php');
    exit;
}

require_once __DIR__ . '/../src/Core/CSRF.php';
require_once __DIR__ . '/../src/Core/FormConstants.php';
require_once __DIR__ . '/../src/Core/AssignmentService.php';
require_once __DIR__ . '/../src/Repository/RegistrationRepository.php';

$repo = new RegistrationRepository();
$view = $_GET['view'] ?? 'forms';
$activeFormId = $_GET['form_id'] ?? null;
$statusConfig = FormConstants::STATUS_CONFIG;

// Load Data
$formsFile = BASE_PATH . '/data/forms.json';
$eventsFile = BASE_PATH . '/data/events.json';
$clubsFile = BASE_PATH . '/data/clubs.json';
$communitiesFile = BASE_PATH . '/data/communities.json';

// Unified Loading
$formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
$forms = $formData['forms'] ?? $formData; // Handle legacy array or new object
$settings = $formData['settings'] ?? []; // Load settings from forms.json

$events = file_exists($eventsFile) ? json_decode(file_get_contents($eventsFile), true) : [];
$clubs = file_exists($clubsFile) ? json_decode(file_get_contents($clubsFile), true) : [];
$communities = file_exists($communitiesFile) ? json_decode(file_get_contents($communitiesFile), true) : [];

$settingsSuccess = '';
$settingsError = '';
$activeForm = null;

// Fetch registrations if in view mode
if ($view === 'registrations' && $activeFormId) {
    foreach ($forms as $f) {
        if ($f['id'] === $activeFormId) {
            $activeForm = $f;
            break;
        }
    }

    if ($activeForm) {
        $registrations = $repo->getAllForForm($activeFormId);
        $statusCounts = $repo->countByStatus($activeFormId);
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        $idToDelete = $_POST['id'];
        $forms = array_values(array_filter($forms, fn($f) => $f['id'] !== $idToDelete));

        // Save back with settings
        $newData = ['forms' => $forms, 'settings' => $settings];
        file_put_contents($formsFile, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        header('Location: /admin/forms.php');
        exit;
    }

    if ($action === 'save') {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        $data = json_decode($_POST['form_data'], true);
        if ($data === null || !is_array($data)) {
            die('Invalid form data');
        }
        $originalId = $data['original_id'] ?? null;
        unset($data['original_id']); // Don't save this to JSON

        // Auto-generate ID if empty
        if (empty($data['id'])) {
            $data['id'] = 'form_' . time();
        }
        $data['created_at'] = date('Y-m-d H:i:s'); // Always update timestamp or keep original? keeping for now

        // Update Linked Entity
        if (!empty($data['context_type']) && !empty($data['context_id'])) {
            $targetFile = '';
            $targetData = [];

            if ($data['context_type'] === 'event') {
                $targetFile = $eventsFile;
                $targetData = $events;
            } elseif ($data['context_type'] === 'club') {
                $targetFile = $clubsFile;
                $targetData = $clubs;
            } elseif ($data['context_type'] === 'community') {
                $targetFile = $communitiesFile;
                $targetData = $communities;
            }

            if ($targetFile) {
                foreach ($targetData as &$item) {
                    if ($item['slug'] === $data['context_id']) {
                        $item['registration'] = [
                            'enabled' => true,
                            'title' => $data['title'],
                            'form_id' => $data['id']
                        ];
                    }
                }
                file_put_contents($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        $found = false;
        // Try to find by original_id first (Renaming)
        if ($originalId) {
            foreach ($forms as $key => $form) {
                if ($form['id'] === $originalId) {
                    // Use array_replace_recursive for deep merge of nested settings
                    $forms[$key] = array_replace_recursive($form, $data);
                    $found = true;
                    break;
                }
            }
        }

        // If not found by original_id (or no original_id), try by current ID (Update existing without rename)
        if (!$found) {
            foreach ($forms as $key => $form) {
                if ($form['id'] === $data['id']) {
                    // Use array_replace_recursive for deep merge of nested settings
                    $forms[$key] = array_replace_recursive($form, $data);
                    $found = true;
                    break;
                }
            }
        }

        // If still not found, append
        if (!$found)
            $forms[] = $data;

        // Save back with settings
        $newData = ['forms' => $forms, 'settings' => $settings];
        file_put_contents($formsFile, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: /admin/forms.php');
        exit;
    }

    // Handle Registration Actions
    if ($action === 'approve' || $action === 'reject') {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }

        $regId = $_POST['reg_id'] ?? '';
        $formId = $_POST['form_id'] ?? '';

        if ($regId && $formId) {
            $newStatus = ($action === 'approve') ? 'verified' : 'rejected';

            // Assign class and gate when approving
            if ($action === 'approve') {
                // Get form settings for assignment
                foreach ($forms as $f) {
                    if ($f['id'] === $formId) {
                        $assignmentSettings = $f['registration_settings']['assignment_settings'] ?? [];
                        if (!empty($assignmentSettings['enabled'])) {
                            $registration = $repo->find($formId, $regId);
                            if ($registration) {
                                $assignmentService = new AssignmentService($repo);
                                $assignmentService->assignClassAndGate(
                                    $formId,
                                    $regId,
                                    $assignmentSettings,
                                    $registration['data'] ?? []
                                );
                            }
                        }
                        break;
                    }
                }
            }

            $repo->updateStatus($formId, $regId, $newStatus);
            header('Location: /admin/forms.php?view=registrations&form_id=' . urlencode($formId));
            exit;
        }
    }

    // Handle Registration Delete
    if ($action === 'delete_registration') {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }

        $regId = $_POST['reg_id'] ?? '';
        $formId = $_POST['form_id'] ?? '';

        if ($regId && $formId) {
            $repo->delete($formId, $regId);
            header('Location: /admin/forms.php?view=registrations&form_id=' . urlencode($formId));
            exit;
        }
    }

    // Handle Settings Save
    if ($action === 'save_settings') {
        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            $settingsError = 'Invalid CSRF token';
        } else {
            // Global settings only contain AI configuration
            $newSettings = [
                'ai_api_key' => trim($_POST['ai_api_key'] ?? ''),
                'ai_api_url' => trim($_POST['ai_api_url'] ?? 'https://api.openai.com/v1'),
                'ai_model' => trim($_POST['ai_model'] ?? 'gpt-4o')
            ];

            // Update settings in memory and save everything
            $settings = $newSettings;
            $newData = ['forms' => $forms, 'settings' => $settings];

            if (file_put_contents($formsFile, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $settingsSuccess = 'Settings saved successfully!';
            } else {
                $settingsError = 'Failed to save settings';
            }
        }
    }
}

$title = 'Form Builder - Admin';
ob_start();
?>

<div x-data="formManager()">
    <?php if ($view === 'registrations' && $activeForm): ?>
        <!-- Registration Dashboard View -->
        <div class="mb-8">
            <a href="/admin/forms.php"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 mb-4 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Forms
            </a>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        <?= htmlspecialchars($activeForm['title']) ?>
                    </h2>
                    <p class="text-gray-500">Registrations Dashboard</p>
                </div>
                <div class="flex gap-4">
                    <!-- Export Button Placeholder -->
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow border border-gray-100 dark:border-gray-700">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    <?= count($registrations) ?>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
            </div>
            <?php foreach ($statusCounts as $status => $count): ?>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow border border-gray-100 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?= $count ?>
                    </div>
                    <div
                        class="text-xs <?= $statusConfig[$status]['color'] ?? 'bg-gray-500' ?> text-white px-2 py-0.5 rounded-full inline-block mt-1">
                        <?= $statusConfig[$status]['label'] ?? ucfirst($status) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- List (Using Alpine for local search/filter if needed, or simple loop) -->
        <div class="space-y-4">
            <?php if (empty($registrations)): ?>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No Registrations Yet</h3>
                    <p class="text-gray-500 dark:text-gray-400">Registrations for this form will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($registrations as $reg): ?>
                    <?php
                    $status = $reg['status'] ?? 'pending_form';
                    $data = $reg['data'] ?? [];
                    $statusInfo = $statusConfig[$status] ?? ['label' => $status, 'color' => 'bg-gray-500'];
                    $studentIdPhoto = $data['student_id_photo'] ?? null;
                    $paymentProof = $data['payment_proof'] ?? null;
                    $csrfToken = CSRF::generate(); // Loop performance negligible here
                    ?>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                        x-data="{ expanded: false }">
                        <div class="p-4 md:p-6 flex flex-col md:flex-row md:items-center gap-4">
                            <!-- Info & Thumbnails -->
                            <div class="flex gap-4 items-center flex-1">
                                <div class="flex -space-x-3 shrink-0">
                                    <?php if ($studentIdPhoto && file_exists($studentIdPhoto)): ?>
                                        <img src="/data/registrations/uploads/<?= urlencode($activeFormId) ?>/<?= urlencode($reg['id']) ?>/<?= basename($studentIdPhoto) ?>"
                                            class="w-12 h-12 rounded-full border-2 border-white dark:border-gray-800 object-cover">
                                    <?php endif; ?>
                                    <?php if ($paymentProof && file_exists($paymentProof)): ?>
                                        <img src="/data/registrations/uploads/<?= urlencode($activeFormId) ?>/<?= urlencode($reg['id']) ?>/<?= basename($paymentProof) ?>"
                                            class="w-12 h-12 rounded-full border-2 border-white dark:border-gray-800 object-cover">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($data['full_name'] ?? 'Unknown') ?>
                                    </h3>
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <span><?= htmlspecialchars($data['school_origin'] ?? '-') ?></span>
                                        <span>•</span>
                                        <span
                                            class="<?= $statusInfo['color'] ?> text-white text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider"><?= $statusInfo['label'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <?php
                                $canApprove = ($status !== 'verified');
                                $canReject = !in_array($status, ['rejected', 'expired']);
                                ?>
                                <form method="POST" class="inline"
                                    onsubmit="return <?= $canApprove ? "confirm('Approve this registration?')" : 'false' ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="reg_id" value="<?= htmlspecialchars($reg['id']) ?>">
                                    <input type="hidden" name="form_id" value="<?= htmlspecialchars($activeFormId) ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit"
                                        class="p-2 rounded-lg transition-colors <?= $canApprove ? 'bg-green-100 text-green-600 hover:bg-green-200' : 'bg-gray-100 text-gray-400 cursor-not-allowed' ?>"
                                        title="Approve" <?= $canApprove ? '' : 'disabled' ?>>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" class="inline"
                                    onsubmit="return <?= $canReject ? "confirm('Reject this registration?')" : 'false' ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="reg_id" value="<?= htmlspecialchars($reg['id']) ?>">
                                    <input type="hidden" name="form_id" value="<?= htmlspecialchars($activeFormId) ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit"
                                        class="p-2 rounded-lg transition-colors <?= $canReject ? 'bg-orange-100 text-orange-600 hover:bg-orange-200' : 'bg-gray-100 text-gray-400 cursor-not-allowed' ?>"
                                        title="Reject" <?= $canReject ? '' : 'disabled' ?>>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" class="inline"
                                    onsubmit="return confirm('Delete this registration? This cannot be undone.')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="reg_id" value="<?= htmlspecialchars($reg['id']) ?>">
                                    <input type="hidden" name="form_id" value="<?= htmlspecialchars($activeFormId) ?>">
                                    <input type="hidden" name="action" value="delete_registration">
                                    <button type="submit"
                                        class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition-colors"
                                        title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                                <button @click="expanded = !expanded"
                                    class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-lg transition-colors">
                                    <svg class="w-5 h-5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Details (Expanded) -->
                        <div x-show="expanded"
                            class="border-t border-gray-100 dark:border-gray-700 p-6 bg-gray-50 dark:bg-gray-900/50">
                            <!-- Detailed Grid similar to registrations.php -->
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php
                                $fields = [
                                    'Full Name' => $data['full_name'] ?? '-',
                                    'Email' => $data['email'] ?? '-',
                                    'WhatsApp' => $data['whatsapp'] ?? '-',
                                    'School' => $data['school_origin'] ?? '-',
                                    'Address' => $data['address'] ?? '-',
                                    'Parent' => $data['parent_name'] ?? '-',
                                    'Payment' => ucfirst($data['payment_method'] ?? '-'),
                                    'Created' => $reg['created_at'] ?? '-',
                                    'Reg ID' => $reg['id']
                                ];
                                foreach ($fields as $label => $value): ?>
                                    <div>
                                        <label class="text-xs text-gray-500 uppercase tracking-wide block mb-1"><?= $label ?></label>
                                        <div class="font-medium text-gray-900 dark:text-white break-words">
                                            <?= htmlspecialchars($value) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($studentIdPhoto || $paymentProof): ?>
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 flex gap-4">
                                    <?php if ($studentIdPhoto && file_exists($studentIdPhoto)): ?>
                                        <a href="/data/registrations/uploads/<?= urlencode($activeFormId) ?>/<?= urlencode($reg['id']) ?>/<?= basename($studentIdPhoto) ?>"
                                            target="_blank" class="block group">
                                            <span class="text-xs text-gray-500 mb-1 block">Student ID</span>
                                            <img src="/data/registrations/uploads/<?= urlencode($activeFormId) ?>/<?= urlencode($reg['id']) ?>/<?= basename($studentIdPhoto) ?>"
                                                class="h-32 rounded border border-gray-200 group-hover:border-blue-500 transition-colors">
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($paymentProof && file_exists($paymentProof)): ?>
                                        <a href="/data/registrations/uploads/<?= urlencode($activeFormId) ?>/<?= urlencode($reg['id']) ?>/<?= basename($paymentProof) ?>"
                                            target="_blank" class="block group">
                                            <span class="text-xs text-gray-500 mb-1 block">Payment Proof</span>
                                            <img src="/data/registrations/uploads/<?= urlencode($activeFormId) ?>/<?= urlencode($reg['id']) ?>/<?= basename($paymentProof) ?>"
                                                class="h-32 rounded border border-gray-200 group-hover:border-blue-500 transition-colors">
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($view === 'settings'): ?>
        <!-- Global Settings View -->
        <div class="mb-8">
            <a href="/admin/forms.php"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 mb-4 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Forms
            </a>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">⚙️ Global Settings</h2>
            <p class="text-gray-500">Configure global parameters for all forms</p>
        </div>

        <?php if ($settingsSuccess): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <?= htmlspecialchars($settingsSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($settingsError): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <?= htmlspecialchars($settingsError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8 max-w-4xl">
            <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
            <input type="hidden" name="action" value="save_settings">

            <!-- AI Configuration -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><span
                        class="text-2xl">🤖</span> AI Configuration (OpenAI Compatible)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                        <input type="password" name="ai_api_key"
                            value="<?= htmlspecialchars($settings['ai_api_key'] ?? '') ?>" placeholder="sk-..."
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:outline-none">
                        <p class="mt-1 text-xs text-gray-500">Required for AI-powered document verification.</p>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">API URL</label>
                            <input type="text" name="ai_api_url"
                                value="<?= htmlspecialchars($settings['ai_api_url'] ?? 'https://api.openai.com/v1') ?>"
                                placeholder="https://api.openai.com/v1"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:outline-none">
                            <p class="mt-1 text-xs text-gray-500">Use custom endpoint for Azure, local models, etc.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Model</label>
                            <input type="text" name="ai_model"
                                value="<?= htmlspecialchars($settings['ai_model'] ?? 'gpt-4o') ?>" placeholder="gpt-4o"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:outline-none">
                            <p class="mt-1 text-xs text-gray-500">e.g., gpt-4o, gpt-4o-mini, gpt-4-vision-preview</p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300">
                <strong>Note:</strong> Payment settings (bank accounts, fees, etc.) are now configured per-form in each
                form's Registration Settings.
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg">Save
                    Settings</button>
            </div>
        </form>

    <?php else: ?>
        <!-- Normal Form Builder Views -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Form Builder</h2>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                <a href="/admin/forms/settings.php"
                    class="flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 transition-colors font-semibold text-sm sm:text-base">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
                <button @click="openEditor()"
                    class="btn-primary flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 text-sm sm:text-base">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Form
                </button>
            </div>
        </div>

        <!-- Forms List -->
        <div class="grid gap-4 sm:gap-6" x-show="!isEditing">
            <?php foreach ($forms as $form): ?>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between border border-gray-100 dark:border-gray-700 group hover:shadow-md transition-all gap-4">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 truncate"
                            title="<?= htmlspecialchars($form['title']) ?>"><?= htmlspecialchars($form['title']) ?>
                        </h3>
                        <div class="flex items-center flex-wrap gap-2 sm:gap-4 text-sm text-gray-500">
                            <span
                                class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded text-xs font-mono shrink-0">ID:
                                <?= $form['id'] ?></span>
                            <?php
                            $prefix = '/form/';
                            if (!empty($form['context_type']) && $form['context_type'] !== 'standalone') {
                                $prefix = "/{$form['context_type']}/{$form['context_id']}/";
                            }
                            $fullPath = $prefix . ($form['slug'] ?? '');
                            ?>
                            <?php if (!empty($form['context_type']) && $form['context_type'] !== 'standalone'): ?>
                                <span
                                    class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1 rounded text-xs font-bold uppercase shrink-0">Linked:
                                    <?= ucfirst($form['context_type']) ?></span>
                            <?php endif; ?>
                            <a href="<?= $fullPath ?>" target="_blank"
                                class="hover:text-blue-500 flex items-center gap-1 transition-colors min-w-0 truncate max-w-full">
                                <span class="truncate"><?= $fullPath ?></span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    <!-- Action Buttons Container -->
                    <div
                        class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto pt-4 sm:pt-0 border-t border-gray-100 dark:border-gray-700 sm:border-0">
                        <!-- Registrations & Orchestrator Row -->
                        <?php if (!empty($form['registration_settings']['enabled'])): ?>
                            <div class="flex gap-2">
                                <a href="/admin/forms/registrations.php?form_id=<?= urlencode($form['id']) ?>"
                                    class="flex-1 sm:flex-none justify-center px-4 py-2.5 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/40 rounded-lg flex items-center gap-2 transition-colors font-bold text-sm">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    <span>Registrations</span>
                                </a>
                                <a href="/admin/forms/orchestrator.php?form_id=<?= urlencode($form['id']) ?>"
                                    class="flex-1 sm:flex-none justify-center px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40 rounded-lg flex items-center gap-2 transition-colors font-bold text-sm">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    <span>Orchestrator</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Edit/Delete Row -->
                        <div class="flex gap-1 justify-end">
                            <button @click='editForm(<?= json_encode($form) ?>)'
                                class="p-2.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-lg transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <form method="POST" onsubmit="return confirm('Are you sure? This cannot be undone.')">
                                <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $form['id'] ?>">
                                <button type="submit"
                                    class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-700 rounded-lg transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Form Editor Modal -->
    <div x-show="isEditing" x-transition class="fixed inset-0 bg-gray-900/90 backdrop-blur-sm z-50 overflow-y-auto">
        <div class="min-h-screen flex items-start justify-center p-4">
            <div class="bg-white dark:bg-gray-900 w-full max-w-6xl rounded-2xl shadow-2xl my-8 overflow-hidden">

                <!-- Modal Header -->
                <div
                    class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-8 py-5 flex items-center justify-between sticky top-0 z-10">
                    <div class="flex items-center gap-4">
                        <button @click="isEditing = false"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white"
                            x-text="form.original_id ? 'Edit Form' : 'Create Form'"></h2>
                    </div>
                    <button @click="saveForm()"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Save Changes
                    </button>
                </div>

                <div class="p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Left: Settings -->
                    <div class="lg:col-span-4 space-y-6">
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3
                                class="font-bold text-lg mb-6 text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2">
                                Basic Settings</h3>
                            <div class="space-y-5">
                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Form
                                        ID</label>
                                    <input type="text" x-model="form.id"
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 placeholder-gray-400 font-mono text-sm"
                                        placeholder="e.g. registration_form_2024">
                                    <p class="text-xs text-gray-500 mt-1">Unique identifier. Changing this will rename
                                        the form ID.</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Form
                                        Title</label>
                                    <input type="text" x-model="form.title"
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 placeholder-gray-400"
                                        placeholder="e.g. Registration 2024">
                                </div>

                                <div
                                    class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 space-y-3">
                                    <label
                                        class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Attachment
                                        & URL</label>
                                    <div class="flex items-center gap-2">
                                        <select x-model="form.context_type" @change="form.context_id = ''"
                                            class="w-2/5 px-2 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-xs font-bold">
                                            <option value="standalone">Standalone</option>
                                            <option value="event">Event</option>
                                            <option value="club">Club</option>
                                            <option value="community">Community</option>
                                        </select>
                                        <span class="text-gray-300 dark:text-gray-600">/</span>
                                        <div class="w-3/5 relative">
                                            <select x-show="form.context_type === 'event'" x-model="form.context_id"
                                                class="w-full px-2 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-xs">
                                                <option value="">Select Event...</option>
                                                <?php foreach ($events as $e): ?>
                                                    <option value="<?= $e['slug'] ?>"><?= htmlspecialchars($e['title']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select x-show="form.context_type === 'club'" x-model="form.context_id"
                                                class="w-full px-2 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-xs">
                                                <option value="">Select Club...</option>
                                                <?php foreach ($clubs as $c): ?>
                                                    <option value="<?= $c['slug'] ?>"><?= htmlspecialchars($c['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select x-show="form.context_type === 'community'" x-model="form.context_id"
                                                class="w-full px-2 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-xs">
                                                <option value="">Select Community...</option>
                                                <?php foreach ($communities as $c): ?>
                                                    <option value="<?= $c['slug'] ?>"><?= htmlspecialchars($c['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select x-show="form.context_type === 'standalone'" disabled
                                                class="w-full px-2 py-2 border-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-400 border-gray-100 dark:border-gray-700 text-xs cursor-not-allowed">
                                                <option>No Parent Entity</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-300 dark:text-gray-600 pl-1">/</span>
                                        <input type="text" x-model="form.slug"
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-xs outline-none transition-all placeholder-gray-400"
                                            placeholder="slug">
                                    </div>
                                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                                        <p class="text-[9px] text-gray-400 uppercase tracking-tighter truncate">
                                            URL: <span class="text-blue-500 font-mono"
                                                x-text="getUrlPrefix() + (form.context_id ? form.context_id + '/' : '') + form.slug"></span>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Description</label>
                                    <textarea x-model="form.description"
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 placeholder-gray-400"
                                        rows="4"></textarea>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Quota
                                        / Limit</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" x-model="form.quota"
                                            class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 placeholder-gray-400"
                                            placeholder="0">
                                        <span class="text-xs text-gray-500 whitespace-nowrap">0 = Unlimited</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Registration Settings -->
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-lg text-gray-900 dark:text-white">🚀 Advanced Registration
                                </h3>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="form.registration_settings.enabled"
                                        class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable</span>
                                </label>
                            </div>

                            <div x-show="form.registration_settings?.enabled" x-transition class="space-y-5">
                                <!-- Document Verification -->
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl space-y-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                            x-model="form.registration_settings.document_verification"
                                            class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">📄 Document
                                            Verification Step</span>
                                    </label>
                                    <p class="text-xs text-gray-500 pl-7">Adds a step for uploading and verifying
                                        student ID</p>

                                    <div x-show="form.registration_settings.document_verification"
                                        class="pl-7 space-y-3">
                                        <input type="text"
                                            x-model="form.registration_settings.document_verification_title"
                                            placeholder="Step title (e.g., Verify Your Identity)"
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="form.registration_settings.ai_verification"
                                                class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                            <span class="text-xs text-gray-600 dark:text-gray-400">🤖 Enable AI
                                                verification (requires Gemini API key in Settings)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Payment Settings -->
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl space-y-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="form.registration_settings.payment_enabled"
                                            class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">💳 Payment
                                            Step</span>
                                    </label>
                                    <p class="text-xs text-gray-500 pl-7">Adds a payment selection & verification step
                                    </p>

                                    <div x-show="form.registration_settings.payment_enabled" class="pl-7 space-y-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-600 mb-1">Fee
                                                    (Rp)</label>
                                                <input type="number"
                                                    x-model="form.registration_settings.registration_fee"
                                                    placeholder="150000"
                                                    class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                            </div>
                                            <div>
                                                <!-- Payment Method Toggles -->
                                                <label class="block text-xs font-bold text-gray-600 mb-2">Payment
                                                    Methods</label>
                                                <div class="space-y-1.5">
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox"
                                                            x-model="form.registration_settings.online_payment_enabled"
                                                            x-init="if(form.registration_settings.online_payment_enabled === undefined) form.registration_settings.online_payment_enabled = true"
                                                            class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                                        <span class="text-xs text-gray-700 dark:text-gray-300">💳
                                                            Online</span>
                                                    </label>
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox"
                                                            x-model="form.registration_settings.offline_payment_enabled"
                                                            x-init="if(form.registration_settings.offline_payment_enabled === undefined) form.registration_settings.offline_payment_enabled = true"
                                                            class="w-4 h-4 rounded text-green-600 focus:ring-green-500 border-gray-300">
                                                        <span class="text-xs text-gray-700 dark:text-gray-300">🏢
                                                            Offline</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Multiple Bank Accounts -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-2">Bank
                                                Accounts</label>
                                            <div class="space-y-3">
                                                <template
                                                    x-for="(account, idx) in form.registration_settings.bank_accounts"
                                                    :key="idx">
                                                    <div
                                                        class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 relative group">
                                                        <button
                                                            @click="form.registration_settings.bank_accounts.splice(idx, 1)"
                                                            class="absolute top-2 right-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                        <div class="grid grid-cols-2 gap-2 pr-6">
                                                            <input type="text" x-model="account.bank_name"
                                                                placeholder="Bank Name (e.g. BCA)"
                                                                class="w-full px-2 py-1.5 border rounded bg-transparent text-sm">
                                                            <input type="text" x-model="account.account_number"
                                                                placeholder="Account Number"
                                                                class="w-full px-2 py-1.5 border rounded bg-transparent text-sm">
                                                            <input type="text" x-model="account.account_holder"
                                                                placeholder="Account Holder Name"
                                                                class="col-span-2 w-full px-2 py-1.5 border rounded bg-transparent text-sm">
                                                        </div>
                                                    </div>
                                                </template>
                                                <button
                                                    @click="if(!form.registration_settings.bank_accounts) form.registration_settings.bank_accounts = []; form.registration_settings.bank_accounts.push({bank_name:'', account_number:'', account_holder:''})"
                                                    class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-xs font-bold text-gray-500 hover:text-blue-500 hover:border-blue-500 transition-all">
                                                    + Add Bank Account
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Offline Payment
                                                Location</label>
                                            <input type="text"
                                                x-model="form.registration_settings.offline_payment_location"
                                                placeholder="School Admin Office, Room 101"
                                                class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Offline Payment
                                                Hours</label>
                                            <input type="text"
                                                x-model="form.registration_settings.offline_payment_hours"
                                                placeholder="Mon-Fri, 08:00-15:00"
                                                class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 mb-1">Offline Payment
                                                Deadline</label>
                                            <input type="datetime-local"
                                                x-model="form.registration_settings.offline_payment_deadline"
                                                class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                </div>

                                <!-- Completion Settings -->
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl space-y-3">
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">✅ Completion Settings
                                    </h4>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Completion
                                            Message</label>
                                        <input type="text" x-model="form.registration_settings.completion_message"
                                            placeholder="Welcome! Your registration is complete."
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">WhatsApp Group
                                            Link</label>
                                        <input type="url" x-model="form.registration_settings.whatsapp_group_link"
                                            placeholder="https://chat.whatsapp.com/..."
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Event PDF/Guide
                                            URL</label>
                                        <input type="url" x-model="form.registration_settings.event_pdf_url"
                                            placeholder="https://example.com/event-guide.pdf"
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                    </div>
                                </div>

                                <!-- Timeout Settings -->
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl space-y-3">
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">⏱️ Timeout Settings
                                    </h4>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Default Step Timeout
                                            (seconds)</label>
                                        <input type="number" x-model="form.registration_settings.default_timeout"
                                            placeholder="900 (15 minutes)"
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                        <p class="text-xs text-gray-500 mt-1">0 = No timeout. Default: 900 (15 min)</p>
                                    </div>
                                    <div x-show="form.registration_settings.document_verification">
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Document Upload
                                            Timeout
                                            (seconds)</label>
                                        <input type="number" x-model="form.registration_settings.document_timeout"
                                            placeholder="3600 (1 hour)"
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                        <p class="text-xs text-gray-500 mt-1">0 = No timeout. Default: 3600 (1 hour)</p>
                                    </div>
                                    <div x-show="form.registration_settings.payment_enabled">
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Payment Step Timeout
                                            (seconds)</label>
                                        <input type="number" x-model="form.registration_settings.payment_timeout"
                                            placeholder="7200 (2 hours)"
                                            class="w-full px-3 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 text-sm">
                                        <p class="text-xs text-gray-500 mt-1">0 = No timeout. Default: 7200 (2 hours)
                                        </p>
                                    </div>

                                    <!-- Assignment Settings -->
                                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <label class="flex items-center gap-3 cursor-pointer mb-4">
                                            <input type="checkbox"
                                                x-model="form.registration_settings.assignment_settings.enabled"
                                                @click="if(!form.registration_settings.assignment_settings) form.registration_settings.assignment_settings = {enabled: true, classes: [], gates: []}"
                                                class="w-5 h-5 rounded text-purple-600 focus:ring-purple-500">
                                            <span class="font-bold text-gray-900 dark:text-white">🎫 Class & Gate
                                                Assignment</span>
                                        </label>

                                        <div x-show="form.registration_settings?.assignment_settings?.enabled"
                                            class="space-y-4 pl-2">
                                            <!-- Classes -->
                                            <div>
                                                <label
                                                    class="block text-xs font-bold text-gray-600 mb-2">Classes</label>
                                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                                    <template
                                                        x-for="(cls, idx) in (form.registration_settings?.assignment_settings?.classes || [])"
                                                        :key="idx">
                                                        <div class="flex gap-2 items-center">
                                                            <input type="text" x-model="cls.name"
                                                                placeholder="Class name"
                                                                class="flex-1 px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-purple-500 text-sm">
                                                            <input type="number" x-model="cls.max" placeholder="Max"
                                                                class="w-16 px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-purple-500 text-sm">
                                                            <button type="button"
                                                                @click="form.registration_settings.assignment_settings.classes.splice(idx, 1)"
                                                                class="p-1.5 text-red-500 hover:bg-red-100 rounded-lg">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <button type="button"
                                                    @click="if(!form.registration_settings.assignment_settings.classes) form.registration_settings.assignment_settings.classes = []; form.registration_settings.assignment_settings.classes.push({name:'', max:36})"
                                                    class="mt-2 text-xs text-purple-600 hover:text-purple-800 font-medium">
                                                    + Add Class
                                                </button>
                                            </div>

                                            <!-- Gates -->
                                            <div>
                                                <label class="block text-xs font-bold text-gray-600 mb-2">Gates</label>
                                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                                    <template
                                                        x-for="(gate, idx) in (form.registration_settings?.assignment_settings?.gates || [])"
                                                        :key="idx">
                                                        <div class="flex gap-2 items-center">
                                                            <input type="text" x-model="gate.name"
                                                                placeholder="Gate name"
                                                                class="flex-1 px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-purple-500 text-sm">
                                                            <input type="number" x-model="gate.max" placeholder="Max"
                                                                class="w-16 px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-purple-500 text-sm">
                                                            <button type="button"
                                                                @click="form.registration_settings.assignment_settings.gates.splice(idx, 1)"
                                                                class="p-1.5 text-red-500 hover:bg-red-100 rounded-lg">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <button type="button"
                                                    @click="if(!form.registration_settings.assignment_settings.gates) form.registration_settings.assignment_settings.gates = []; form.registration_settings.assignment_settings.gates.push({name:'', max:63})"
                                                    class="mt-2 text-xs text-purple-600 hover:text-purple-800 font-medium">
                                                    + Add Gate
                                                </button>
                                            </div>

                                            <!-- Special Needs Settings -->
                                            <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg">
                                                <label
                                                    class="block text-xs font-bold text-amber-800 dark:text-amber-200 mb-2">♿
                                                    Special Needs Routing</label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <input type="text"
                                                        x-model="form.registration_settings.assignment_settings.special_needs_class"
                                                        placeholder="Class (e.g. Cue)"
                                                        class="px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-amber-500 text-sm">
                                                    <input type="text"
                                                        x-model="form.registration_settings.assignment_settings.special_needs_gate"
                                                        placeholder="Gate (e.g. Gate 1)"
                                                        class="px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-amber-500 text-sm">
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 mt-2">
                                                    <input type="text"
                                                        x-model="form.registration_settings.assignment_settings.special_needs_field"
                                                        placeholder="Field name (e.g. special_needs)"
                                                        class="px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-amber-500 text-sm">
                                                    <input type="text"
                                                        x-model="form.registration_settings.assignment_settings.special_needs_value"
                                                        placeholder="Value (e.g. Ya)"
                                                        class="px-2 py-1.5 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-amber-500 text-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-400 text-center">
                                    Access via: <span class="font-mono text-blue-500">/register/<span
                                            x-text="form.slug || 'slug'"></span></span>
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Builder -->
                    <div class="lg:col-span-8 space-y-6">
                        <template x-for="(step, sIndex) in form.steps" :key="sIndex">
                            <div
                                class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all">
                                <div
                                    class="bg-gray-50 dark:bg-gray-700/30 px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                    <div class="flex items-center gap-4 flex-1">
                                        <span
                                            class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/30"
                                            x-text="sIndex + 1"></span>
                                        <input type="text" x-model="step.title"
                                            class="w-full px-4 py-2.5 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all text-lg font-bold"
                                            placeholder="Step Title">
                                    </div>
                                    <button @click="removeStep(sIndex)"
                                        class="ml-4 text-gray-400 hover:text-red-500 p-2 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="Delete Step">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-6 space-y-4">
                                    <template x-for="(field, fIndex) in step.fields" :key="fIndex">
                                        <div
                                            class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl p-5 relative group transition-all hover:border-blue-300 dark:hover:border-blue-700">
                                            <button @click="removeField(sIndex, fIndex)"
                                                class="absolute top-4 right-4 text-gray-400 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                                                <div class="md:col-span-4">
                                                    <label
                                                        class="block text-xs font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Field
                                                        Label</label>
                                                    <input type="text" x-model="field.label"
                                                        @input="generateName(field)"
                                                        class="w-full px-4 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none transition-colors text-sm">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-xs font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Placeholder</label>
                                                    <input type="text" x-model="field.placeholder"
                                                        class="w-full px-4 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none transition-colors text-sm">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label
                                                        class="block text-xs font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Type</label>
                                                    <select x-model="field.type"
                                                        class="w-full px-4 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none transition-colors text-sm">
                                                        <option value="text">Text Input</option>
                                                        <option value="email">Email Address</option>
                                                        <option value="tel">Phone Number</option>
                                                        <option value="textarea">Long Text (Textarea)</option>
                                                        <option value="select">Dropdown Selection</option>
                                                        <option value="number">Number</option>
                                                        <option value="file">File Upload</option>
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2 pt-6">
                                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                                        <input type="checkbox" x-model="field.required"
                                                            class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                                        <span
                                                            class="text-sm font-medium text-gray-700 dark:text-gray-300">Required</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div x-show="field.type === 'select'"
                                                class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                                <label
                                                    class="block text-xs font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Dropdown
                                                    Options</label>
                                                <div class="flex gap-2">
                                                    <input type="text"
                                                        :value="field.options ? field.options.join(', ') : ''"
                                                        @input="field.options = $event.target.value.split(',').map(s => s.trim())"
                                                        class="w-full px-4 py-2 border-2 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none transition-colors text-sm"
                                                        placeholder="Option 1, Option 2, Option 3">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <button @click="addField(sIndex)"
                                        class="w-full py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-blue-500 hover:text-blue-600 dark:hover:border-blue-400 dark:hover:text-blue-400 transition-all font-bold flex items-center justify-center gap-2 group">
                                        <div
                                            class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 flex items-center justify-center transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </div>
                                        Add New Field
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div class="flex justify-center pt-4">
                            <button @click="addStep()"
                                class="bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Add Another Step
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="form.context_type !== 'standalone' && form.context_id"
                    class="bg-blue-50 dark:bg-blue-900/20 p-4 border-t border-blue-100 dark:border-blue-800 flex items-center justify-between transition-all">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-lg text-blue-600 dark:text-blue-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">Attached to <span
                                    x-text="form.context_type.toUpperCase()"></span></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: <span
                                    x-text="form.context_id"></span></p>
                        </div>
                    </div>
                    <a :href="'/' + form.context_type + '/' + form.context_id" target="_blank"
                        class="text-sm font-bold text-blue-600 hover:underline">View Page &rarr;</a>
                </div>
            </div>
        </div>
    </div>
    <form id="saveForm" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="form_data" id="formDataInput">
    </form>
</div>

<script src="/public/js/admin/form-manager.js?v=<?= time() ?>"></script>

<?php
$content = ob_get_clean();
if ($isFullAdmin) {
    require __DIR__ . '/layout.php';
} else {
    require __DIR__ . '/forms/layout.php';
}
?>