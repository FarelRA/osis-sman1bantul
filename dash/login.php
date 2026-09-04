<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form token, please try again';
    } elseif (!Security::checkRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        $error = 'Too many login attempts. Try again in 15 minutes.';
    } else {
        $admin = json_decode(file_get_contents(BASE_PATH . '/data/admin.json'), true);

        if ($_POST['password'] === 'password') {
            $error = 'Nice try, but "password" is not going to cut it.';
            Security::incrementRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        } elseif (isset($admin['username']) && $_POST['username'] === $admin['username'] && password_verify($_POST['password'], $admin['password'])) {
            Security::regenerateSession();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['_last_activity'] = time();
            Security::resetRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            header('Location: ' . ADMIN_PATH);
            exit;
        } else {
            $error = 'Invalid credentials';
            Security::incrementRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
    }
}

$csrfToken = CSRF::generate();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - OSIS</title>
    <link rel="icon" type="image/png" href="<?= asset('assets/images/osis.webp') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script>
        if (!window.matchMedia || window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="w-full" style="max-width: 340px;">
        <div class="card p-8">
            <div class="text-center mb-8">
                <div
                    class="w-16 h-16 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">Admin Login</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">OSIS SMAN 1 Bantul</p>
            </div>

            <?php if (isset($error)): ?>
                <div
                    class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium"><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Username</label>
                    <input type="text" name="username" required autofocus
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white">
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:text-white">
                </div>
                <button type="submit" class="w-full btn-primary">
                    Login
                </button>
            </form>
        </div>
    </div>
</body>

</html>