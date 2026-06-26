<?php
/**
 * Orchestrator Module - Entry Point
 * Event-day management for QR scanning, attendance, merch, and location tracking
 * Now supports orchestrator accounts with permission levels
 */
session_start();
require_once __DIR__ . '/../../src/Config.php';

require_once __DIR__ . '/../../src/Core/CSRF.php';
require_once __DIR__ . '/../../src/Core/FormConstants.php';
require_once __DIR__ . '/../../src/Repository/RegistrationRepository.php';
require_once __DIR__ . '/../../src/Repository/OrchestratorRepository.php';
require_once __DIR__ . '/../../src/Repository/OrchestratorAccountRepository.php';

// Get form ID first (needed for orchestrator account check)
$activeFormId = $_GET['form_id'] ?? null;
$mode = $_GET['mode'] ?? 'scanner'; // scanner, dashboard, or accounts

// Auth check - Allow admin, forms admin, OR orchestrator account
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isFormsAdmin = isset($_SESSION['forms_logged_in']);
$isOrchestratorUser = isset($_SESSION['orchestrator_account']) && isset($_SESSION['orchestrator_form_id']);

// Check if orchestrator is logged in for the correct form
if ($isOrchestratorUser && $_SESSION['orchestrator_form_id'] !== $activeFormId) {
    $isOrchestratorUser = false;
}

$isStandaloneMode = ($isFormsAdmin || $isOrchestratorUser) && !$isFullAdmin;

// Redirect to appropriate login if not authenticated
if (!$isFullAdmin && !$isFormsAdmin && !$isOrchestratorUser) {
    // Redirect orchestrators to their login, admins to forms login
    if ($activeFormId) {
        header('Location: /admin/forms/orchestrator/login.php?form_id=' . urlencode($activeFormId));
    } else {
        header('Location: /admin/forms/login.php');
    }
    exit;
}

// Load forms
$formsFile = BASE_PATH . '/data/forms.json';
$formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
$forms = $formData['forms'] ?? $formData;
$globalSettings = $formData['settings'] ?? [];

// Get configured stations from global settings
$configuredStations = $globalSettings['stations'] ?? [
    ['emoji' => '', 'label' => 'Main Entrance'],
    ['emoji' => '', 'label' => 'Registration Desk'],
    ['emoji' => '', 'label' => 'Merch Booth'],
    ['emoji' => '', 'label' => 'Main Hall'],
    ['emoji' => '', 'label' => 'Room A'],
    ['emoji' => '', 'label' => 'Room B'],
    ['emoji' => '', 'label' => 'Room C'],
    ['emoji' => '', 'label' => 'Exit Gate'],
];

// Find active form
$activeForm = null;
foreach ($forms as $f) {
    if ($f['id'] === $activeFormId) {
        $activeForm = $f;
        break;
    }
}

// If no form selected, redirect to forms page or login
if (!$activeForm) {
    if ($isOrchestratorUser) {
        header('Location: /admin/forms/orchestrator/login.php');
    } else {
        header('Location: /admin/forms.php');
    }
    exit;
}

// Initialize repositories
$regRepo = new RegistrationRepository();
$orchRepo = new OrchestratorRepository();
$accountRepo = new OrchestratorAccountRepository();

// Get configured locations
$locations = $orchRepo->getLocations($activeFormId);

// Get operator info - support both old session-based and new account-based
$orchestratorAccount = $_SESSION['orchestrator_account'] ?? null;
$operatorLocation = $_SESSION['orchestrator_location'] ?? null;

if ($orchestratorAccount) {
    $operatorName = $orchestratorAccount['display_name'];
    $permissionLevel = $orchestratorAccount['permission'];
    $allowedActions = $orchestratorAccount['allowed_actions'] ?? [];
    $allowedLocations = $orchestratorAccount['allowed_locations'] ?? [];
} else {
    // Admin/forms accounts can ONLY manage orchestrator accounts
    $operatorName = null;
    $permissionLevel = 'admin';
    $allowedActions = [];
    $allowedLocations = [];
}

// Determine what this user can do
$canSelectLocation = $orchestratorAccount && ($permissionLevel === 'super' || $permissionLevel === 'high');
$canManageAccounts = ($isFullAdmin || $isFormsAdmin || ($orchestratorAccount && $permissionLevel === 'super'));
$canScanAndDashboard = $orchestratorAccount !== null; // Only orchestrator accounts can scan/dashboard

// Get filtered stations based on permissions
$availableStations = $configuredStations;
if (!$canSelectLocation && !empty($allowedLocations)) {
    $availableStations = array_filter($configuredStations, function($station) use ($allowedLocations) {
        return in_array($station['label'], $allowedLocations);
    });
    $availableStations = array_values($availableStations);
}

// Redirect admin/forms accounts to account management only
if (!$canScanAndDashboard) {
    header('Location: /admin/forms/orchestrator-accounts.php?form_id=' . urlencode($activeFormId));
    exit;
}

// Generate CSRF token
$csrfToken = CSRF::generate();

// Page title based on mode
$title = ($mode === 'dashboard' ? 'Dashboard' : ($mode === 'accounts' ? 'Accounts' : 'Scanner')) . ' - ' . htmlspecialchars($activeForm['title']);

// Render appropriate view
ob_start();
if ($mode === 'dashboard') {
    include __DIR__ . '/orchestrator/views/dashboard.php';
} elseif ($mode === 'accounts' && $canManageAccounts) {
    // Redirect to accounts page
    header('Location: /admin/forms/orchestrator-accounts.php?form_id=' . urlencode($activeFormId));
    exit;
} else {
    include __DIR__ . '/orchestrator/views/scanner.php';
}
$content = ob_get_clean();

// Use appropriate layout
if ($isStandaloneMode) {
    require __DIR__ . '/layout.php';
} else {
    require __DIR__ . '/../layout.php';
}
