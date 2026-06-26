<?php
/**
 * Orchestrator Login Page
 * Separate login for orchestrator accounts (not forms admin)
 */
session_start();
require_once __DIR__ . '/../../../src/Config.php';
require_once __DIR__ . '/../../../src/Core/CSRF.php';
require_once __DIR__ . '/../../../src/Repository/OrchestratorAccountRepository.php';

// Get form ID from URL
$formId = $_GET['form_id'] ?? null;

// If already logged in as orchestrator for this form, redirect to scanner
if (isset($_SESSION['orchestrator_account']) && isset($_SESSION['orchestrator_form_id'])) {
    if ($_SESSION['orchestrator_form_id'] === $formId) {
        header('Location: /admin/forms/orchestrator.php?form_id=' . urlencode($formId));
        exit;
    }
}

// If already logged in as forms admin or full admin, redirect to scanner
if (isset($_SESSION['admin_logged_in']) || isset($_SESSION['forms_logged_in'])) {
    if ($formId) {
        header('Location: /admin/forms/orchestrator.php?form_id=' . urlencode($formId));
        exit;
    }
}

// Load form info
$formsFile = BASE_PATH . '/data/forms.json';
$formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
$forms = $formData['forms'] ?? $formData;

$activeForm = null;
if ($formId) {
    foreach ($forms as $f) {
        if ($f['id'] === $formId) {
            $activeForm = $f;
            break;
        }
    }
}

// Handle login POST
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!CSRF::verify($csrfToken)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $formIdPost = $_POST['form_id'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter username and password.';
        } else {
            $accountRepo = new OrchestratorAccountRepository();
            $account = $accountRepo->authenticate($formIdPost, $username, $password);

            if ($account) {
                $_SESSION['orchestrator_account'] = $account;
                $_SESSION['orchestrator_form_id'] = $formIdPost;

                header('Location: /admin/forms/orchestrator.php?form_id=' . urlencode($formIdPost));
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
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
    <title>Orchestrator Login<?= $activeForm ? ' - ' . htmlspecialchars($activeForm['title']) : '' ?></title>
    <link rel="icon" type="image/png" href="<?= asset('assets/images/osis.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Login Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="relative inline-block">
                    <div class="w-20 h-20 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center mx-auto shadow-xl shadow-amber-500/30 transform rotate-3">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-6">Orchestrator Login</h1>
                <?php if ($activeForm): ?>
                    <p class="text-gray-500 dark:text-gray-400 mt-2"><?= htmlspecialchars($activeForm['title']) ?></p>
                <?php else: ?>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Sign in with your orchestrator account</p>
                <?php endif; ?>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <?php if (!$formId): ?>
                    <!-- Form selector if no form_id in URL -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Event</label>
                        <select name="form_id" required
                            class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none transition-all">
                            <option value="">Select event...</option>
                            <?php foreach ($forms as $form): ?>
                                <option value="<?= htmlspecialchars($form['id']) ?>"><?= htmlspecialchars($form['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="form_id" value="<?= htmlspecialchars($formId) ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Username
                        </span>
                    </label>
                    <input type="text" name="username" required autocomplete="username"
                        class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none transition-all"
                        placeholder="Enter your username">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Password
                        </span>
                    </label>
                    <input type="password" name="password" required autocomplete="current-password"
                        class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none transition-all"
                        placeholder="Enter your password">
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold py-4 px-6 rounded-xl transition-all shadow-lg shadow-amber-500/30 text-lg">
                    Sign In
                </button>
            </form>

            <!-- Admin Login Link -->
            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 text-center">
                <p class="text-sm text-gray-500">
                    Are you an admin?
                    <a href="/admin/forms/login.php" class="text-amber-600 hover:text-amber-700 font-bold">Admin Login</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-gray-500 text-sm">
            OSIS SMAN 1 Bantul
        </div>
    </div>
</body>
</html>
