<?php
/**
 * Submissions Management Module - Entry Point
 * Minimal controller that routes to API or renders view
 */
require_once __DIR__ . '/../../src/Config.php';

// Auth check - Allow either admin login OR forms-only login
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isFormsOnly = isset($_SESSION['forms_logged_in']);
$isStandaloneMode = $isFormsOnly && !$isFullAdmin;

if (!$isFullAdmin && !$isFormsOnly) {
    header('Location: ' . ADMIN_PATH . '/forms/login.php');
    exit;
}

require_once __DIR__ . '/../../src/Core/CSRF.php';
require_once __DIR__ . '/../../src/Core/FormConstants.php';

// Get form ID
$activeFormId = $_GET['form_id'] ?? null;

// Load forms
$formsFile = BASE_PATH . '/data/forms.json';
$formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
$forms = $formData['forms'] ?? $formData;

// Find active form
$activeForm = null;
foreach ($forms as $f) {
    if ($f['id'] === $activeFormId) {
        $activeForm = $f;
        break;
    }
}

// If no form selected, redirect to forms page
if (!$activeForm) {
    header('Location: ' . ADMIN_PATH . '/forms.php');
    exit;
}

// Generate single CSRF token for all operations
$csrfToken = CSRF::generate();

// Get display fields from form config (dynamic - no hardcoding)
$displayFields = [];
$fieldLabels = [];
foreach ($activeForm['steps'] ?? [] as $step) {
    foreach ($step['fields'] ?? [] as $field) {
        $type = $field['type'] ?? 'text';
        $fieldLabels[$field['name']] = $field['label'] ?? ucwords(str_replace('_', ' ', $field['name']));
        if (in_array($type, ['text', 'email', 'tel', 'select', 'textarea'])) {
            $displayFields[] = $field['name'];
        }
    }
}

// Page title
$title = 'Submissions - ' . htmlspecialchars($activeForm['title']);

// Render view
ob_start();
include __DIR__ . '/submissions/views/main.php';
$content = ob_get_clean();

// Use appropriate layout based on access mode
if ($isStandaloneMode) {
    require __DIR__ . '/layout.php';
} else {
    require __DIR__ . '/../layout.php';
}
