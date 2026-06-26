<?php
/**
 * Restore Registration Cookie
 * Validates a share token and restores the registration cookie
 */
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/Cookie.php';
require_once __DIR__ . '/../src/Repository/RegistrationRepository.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    $error = 'Missing restore token';
    require __DIR__ . '/restore-error.php';
    exit;
}

// Validate token
$tokenData = Cookie::validateRestoreToken($token);
if (!$tokenData) {
    http_response_code(400);
    $error = 'Invalid or expired restore link';
    require __DIR__ . '/restore-error.php';
    exit;
}

$formId = $tokenData['formId'];
$regId = $tokenData['regId'];

// Verify registration exists
$repo = new RegistrationRepository();
$registration = $repo->find($formId, $regId);

if (!$registration) {
    http_response_code(404);
    $error = 'Registration not found';
    require __DIR__ . '/restore-error.php';
    exit;
}

// Load form to get the slug for redirect
$formsFile = BASE_PATH . '/data/forms.json';
$formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
$forms = $formData['forms'] ?? $formData;
$formSlug = null;

foreach ($forms as $form) {
    if ($form['id'] === $formId) {
        $formSlug = $form['slug'] ?? null;
        break;
    }
}

if (!$formSlug) {
    http_response_code(404);
    $error = 'Form not found';
    require __DIR__ . '/restore-error.php';
    exit;
}

// Set the cookie
Cookie::set($formId, $regId);

// Redirect to registration page
header('Location: /register/' . urlencode($formSlug));
exit;
