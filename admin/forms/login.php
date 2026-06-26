<?php
/**
 * Standalone Login for Forms Management
 * This provides separate access to forms without full admin privileges
 */
session_start();
require_once __DIR__ . '/../../src/Config.php';
require_once __DIR__ . '/../../src/Core/CSRF.php';

// If already logged in as forms admin, redirect to forms
if (isset($_SESSION['forms_logged_in'])) {
    header('Location: /admin/forms/forms.php');
    exit;
}

// If already logged in as full admin, also allow access
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/forms/forms.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credentialsFile = BASE_PATH . '/data/forms-admin.json';

    if (file_exists($credentialsFile)) {
        $admin = json_decode(file_get_contents($credentialsFile), true);

        if ($_POST['username'] === $admin['username'] && password_verify($_POST['password'], $admin['password'])) {
            $_SESSION['forms_logged_in'] = true;
            $_SESSION['forms_username'] = $admin['username'];

            header('Location: /admin/forms.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Credentials file not found';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms Login - OSIS</title>
    <link rel="icon" type="image/png" href="<?= asset('assets/images/osis.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>

<body
    class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-3 sm:p-4">
    <div class="w-full max-w-[320px] sm:max-w-[360px]">
        <div class="card p-6 sm:p-8 shadow-xl dark:shadow-2xl">
            <div class="text-center mb-6 sm:mb-8">
                <div
                    class="w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 shadow-lg">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-1">Forms Login</h1>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">OSIS SMAN 1 Bantul</p>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg mb-5 sm:mb-6 flex items-center gap-2 sm:gap-3">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-xs sm:text-sm font-medium">
                        <?= htmlspecialchars($error) ?>
                    </span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4 sm:mb-5">
                    <label
                        class="block text-xs sm:text-sm font-medium text-gray-900 dark:text-white mb-1.5 sm:mb-2">Username</label>
                    <input type="text" name="username" required autofocus
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-gray-900 dark:text-white text-sm sm:text-base transition-all">
                </div>
                <div class="mb-5 sm:mb-6">
                    <label
                        class="block text-xs sm:text-sm font-medium text-gray-900 dark:text-white mb-1.5 sm:mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-gray-900 dark:text-white text-sm sm:text-base transition-all">
                </div>
                <button type="submit"
                    class="w-full px-4 py-2.5 sm:py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-lg transition-all text-sm sm:text-base shadow-lg hover:shadow-xl active:scale-[0.98]">
                    Login
                </button>
            </form>
        </div>
    </div>
</body>

</html>