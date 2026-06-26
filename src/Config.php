<?php
// Define Base Path
define('BASE_PATH', dirname(__DIR__));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Local Configuration

define('UPLOAD_PATH', BASE_PATH . '/public/assets/uploads/');

date_default_timezone_set('Asia/Jakarta');

function url($path = '')
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}

function asset($path)
{
    $url = url('public/' . ltrim($path, '/'));
    // Add timestamp for cache busting on CSS/JS files
    if (preg_match('/\.(css|js)$/', $path)) {
        $url .= '?v=' . time();
    }
    return $url;
}

/**
 * Resolves the canonical URL for a form attached to an entity
 */
function getFormUrl($type, $entitySlug)
{
    $formsFile = BASE_PATH . '/data/forms.json';
    if (!file_exists($formsFile))
        return null;

    $data = json_decode(file_get_contents($formsFile), true);

    // Handle new structure with 'forms' key, or legacy flat array
    $forms = isset($data['forms']) ? $data['forms'] : $data;

    foreach ($forms as $form) {
        if (($form['context_type'] ?? '') === $type && ($form['context_id'] ?? '') === $entitySlug) {
            // Return the registration URL with the form slug
            return url("register/{$form['slug']}");
        }
    }

    // No matching form found
    return null;
}