<?php
define('BASE_PATH', dirname(__DIR__));
define('ADMIN_PATH', '/dash');

$sessionPath = BASE_PATH . '/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0700, true);
}
session_save_path($sessionPath);

// Harden session config
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', '7200');
ini_set('session.cookie_secure', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('UPLOAD_PATH', BASE_PATH . '/public/assets/uploads/');
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/Security.php';
Security::init();

// Regenerate session ID periodically to prevent fixation
if (!empty($_SESSION['admin_logged_in']) || !empty($_SESSION['forms_logged_in'])) {
    $regenerated = $_SESSION['_session_regenerated'] ?? 0;
    if (time() - $regenerated > 3600) {
        session_regenerate_id(true);
        $_SESSION['_session_regenerated'] = time();
    }
}

require_once __DIR__ . '/Analytics.php';

trackVisit();

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function js(?string $value): string
{
    return htmlspecialchars(str_replace(["\\", "'"], ["\\\\", "\\'"], $value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url($path = '')
{
    return '/' . ltrim($path, '/');
}

function admin_url($path = '')
{
    return ADMIN_PATH . '/' . ltrim($path, '/');
}

function asset($path)
{
    $url = url('public/' . ltrim($path, '/'));
    if (preg_match('/\.(css|js)$/', $path)) {
        $url .= '?v=' . time();
    }
    $url = str_replace(['<', '>', '"', "'"], '', $url);
    return $url;
}

function getFormUrl($type, $entitySlug)
{
    $formsFile = BASE_PATH . '/data/forms.json';
    if (!file_exists($formsFile))
        return null;

    $data = json_decode(file_get_contents($formsFile), true);
    $forms = isset($data['forms']) ? $data['forms'] : $data;

    foreach ($forms as $form) {
        if (($form['context_type'] ?? '') === $type && ($form['context_id'] ?? '') === $entitySlug) {
            return url("register/{$form['slug']}");
        }
    }

    return null;
}
