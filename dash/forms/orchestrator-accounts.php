<?php
/**
 * Orchestrator Accounts Management Module
 * Forms admin can create, edit, and delete orchestrator accounts
 */
require_once __DIR__ . '/../../src/Config.php';

// Auth check - Only forms admin or full admin can access
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isFormsAdmin = isset($_SESSION['forms_logged_in']);
$isStandaloneMode = $isFormsAdmin && !$isFullAdmin;

if (!$isFullAdmin && !$isFormsAdmin) {
    header('Location: ' . ADMIN_PATH . '/forms/login.php');
    exit;
}

require_once __DIR__ . '/../../src/Core/CSRF.php';
require_once __DIR__ . '/../../src/Repository/OrchestratorAccountRepository.php';

// Get form ID
$activeFormId = $_GET['form_id'] ?? null;

// Load forms
$formsFile = BASE_PATH . '/data/forms.json';
$formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
$forms = $formData['forms'] ?? $formData;
$globalSettings = $formData['settings'] ?? [];

// Find active form
$activeForm = null;
foreach ($forms as $f) {
    if ($f['id'] === $activeFormId) {
        $activeForm = $f;
        break;
    }
}

// Get configured stations — prefer form-level, fall back to global
$configuredStations = $activeForm['stations'] ?? $globalSettings['stations'] ?? [
    ['emoji' => '', 'label' => 'Main Entrance'],
    ['emoji' => '', 'label' => 'Registration Desk'],
    ['emoji' => '', 'label' => 'Merch Booth'],
    ['emoji' => '', 'label' => 'Main Hall'],
    ['emoji' => '', 'label' => 'Room A'],
    ['emoji' => '', 'label' => 'Room B'],
    ['emoji' => '', 'label' => 'Room C'],
    ['emoji' => '', 'label' => 'Exit Gate'],
];

// If no form selected, redirect to forms page
if (!$activeForm) {
    header('Location: ' . ADMIN_PATH . '/forms.php');
    exit;
}

// Initialize repository and get accounts
$accountRepo = new OrchestratorAccountRepository();
$accounts = $accountRepo->getAllForForm($activeFormId);

// Get all available actions and permissions
$allActions = OrchestratorAccountRepository::getAllActions();
$allPermissions = OrchestratorAccountRepository::getAllPermissions();

// Generate CSRF token
$csrfToken = CSRF::generate();

// Page title
$title = 'Orchestrator Accounts - ' . htmlspecialchars($activeForm['title']);

// Render view
ob_start();
include __DIR__ . '/orchestrator/views/accounts.php';
$content = ob_get_clean();

// Use appropriate layout
if ($isStandaloneMode) {
    require __DIR__ . '/layout.php';
} else {
    require __DIR__ . '/../layout.php';
}
