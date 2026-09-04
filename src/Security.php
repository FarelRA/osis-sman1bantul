<?php

class Security
{
    private const SESSION_TIMEOUT = 7200;
    private const LOGIN_ATTEMPTS_KEY = 'login_attempts';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_MINUTES = 15;

    public static function init(): void
    {
        self::sendSecurityHeaders();
        self::checkSessionTimeout();
    }

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) return;
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    public static function checkSessionTimeout(): void
    {
        if (empty($_SESSION)) return;

        $lastActivity = $_SESSION['_last_activity'] ?? 0;
        $hasAuth = !empty($_SESSION['admin_logged_in'])
            || !empty($_SESSION['forms_logged_in'])
            || !empty($_SESSION['orchestrator_account']);

        if ($hasAuth && $lastActivity > 0 && (time() - $lastActivity) > self::SESSION_TIMEOUT) {
            $_SESSION = [];
            session_destroy();
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: ' . ADMIN_PATH . '/login.php?timeout=1&redirect=' . urlencode($currentUrl));
            exit;
        }

        if ($hasAuth) {
            $_SESSION['_last_activity'] = time();
        }
    }

    public static function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['_last_activity'] = time();
        }
    }

    public static function checkRateLimit(string $identifier): bool
    {
        $key = self::LOGIN_ATTEMPTS_KEY . '_' . md5($identifier);
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'time' => 0];

        if ($attempts['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $elapsed = time() - $attempts['time'];
            if ($elapsed < self::LOGIN_LOCKOUT_MINUTES * 60) {
                return false;
            }
            unset($_SESSION[$key]);
            return true;
        }
        return true;
    }

    public static function incrementRateLimit(string $identifier): void
    {
        $key = self::LOGIN_ATTEMPTS_KEY . '_' . md5($identifier);
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'time' => time()];
        $attempts['count']++;
        $attempts['time'] = time();
        $_SESSION[$key] = $attempts;
    }

    public static function resetRateLimit(string $identifier): void
    {
        $key = self::LOGIN_ATTEMPTS_KEY . '_' . md5($identifier);
        unset($_SESSION[$key]);
    }

    public static function validatePath(string $path, string $allowedBase): bool
    {
        $resolved = realpath($path);
        $allowedBase = realpath($allowedBase);
        if ($resolved === false || $allowedBase === false) return false;
        return str_starts_with($resolved, $allowedBase);
    }

    public static function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^\w\-\.\s]/u', '_', $filename);
        $filename = preg_replace('/\.\.+/', '.', $filename);
        return trim($filename);
    }
}
