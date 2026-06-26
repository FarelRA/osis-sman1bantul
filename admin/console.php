<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Verify secret parameter
// require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$output = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {
    $command = "cd " . BASE_PATH . " && " . $_POST['command'] . " 2>&1";

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open($command, $descriptors, $pipes);

    if (is_resource($process)) {
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        if ($stderr) {
            $output .= "\n" . $stderr;
        }
    } else {
        $error = 'Failed to execute command';
    }
}

$title = 'Console - Admin';
ob_start();
?>

<h2 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Console Terminal</h2>

<div class="card p-6">
    <form method="POST" class="mb-4">
        <div class="flex gap-2">
            <input type="text" name="command" required autofocus placeholder="Enter command..."
                class="flex-1 px-4 py-2 bg-gray-900 dark:bg-black text-green-400 font-mono border border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="btn-primary">Run</button>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="bg-red-900/20 border border-red-800 text-red-400 p-4 rounded-lg mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($output): ?>
        <div class="bg-gray-900 dark:bg-black text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
            <pre><?= htmlspecialchars($output) ?></pre>
        </div>
    <?php endif; ?>
</div>

<div class="card p-6 mt-6">
    <h3 class="font-bold text-gray-900 dark:text-white mb-3">Quick Commands</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <button
            onclick="document.querySelector('input[name=command]').value='ls -la'; document.querySelector('form').submit();"
            class="btn-secondary text-sm">ls -la</button>
        <button
            onclick="document.querySelector('input[name=command]').value='pwd'; document.querySelector('form').submit();"
            class="btn-secondary text-sm">pwd</button>
        <button
            onclick="document.querySelector('input[name=command]').value='git status'; document.querySelector('form').submit();"
            class="btn-secondary text-sm">git status</button>
        <button
            onclick="document.querySelector('input[name=command]').value='git log --oneline -5'; document.querySelector('form').submit();"
            class="btn-secondary text-sm">git log</button>
        <button
            onclick="document.querySelector('input[name=command]').value='php -v'; document.querySelector('form').submit();"
            class="btn-secondary text-sm">php -v</button>
        <button
            onclick="document.querySelector('input[name=command]').value='df -h'; document.querySelector('form').submit();"
            class="btn-secondary text-sm">df -h</button>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>