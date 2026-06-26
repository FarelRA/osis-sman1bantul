<?php
session_start();
require_once __DIR__ . '/../src/Config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$about_file = BASE_PATH . '/data/about.json';
$about = json_decode(file_get_contents($about_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $about = [
        'mission' => $_POST['mission'],
        'vision' => $_POST['vision'],
        'activities' => [
            ['title' => $_POST['activity_title_0'], 'description' => $_POST['activity_desc_0']],
            ['title' => $_POST['activity_title_1'], 'description' => $_POST['activity_desc_1']],
            ['title' => $_POST['activity_title_2'], 'description' => $_POST['activity_desc_2']],
            ['title' => $_POST['activity_title_3'], 'description' => $_POST['activity_desc_3']]
        ]
    ];

    file_put_contents($about_file, json_encode($about, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $success = true;
}

$title = 'Edit About - Admin';
ob_start();
?>

<div class="mb-6 sm:mb-8">
    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Edit About Page</h2>
</div>

<?php if (isset($success)): ?>
    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg">
        About page updated successfully!
    </div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <div class="card p-4 sm:p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Mission</h3>
        <textarea name="mission" rows="4" required
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars($about['mission']) ?></textarea>
    </div>

    <div class="card p-4 sm:p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Vision</h3>
        <textarea name="vision" rows="4" required
            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars($about['vision']) ?></textarea>
    </div>

    <div class="card p-4 sm:p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">What We Do</h3>
        <div class="space-y-4">
            <?php foreach ($about['activities'] as $idx => $activity): ?>
                <div class="border dark:border-gray-700 rounded-lg p-4">
                    <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-white">Activity <?= $idx + 1 ?>
                        Title</label>
                    <input type="text" name="activity_title_<?= $idx ?>" value="<?= htmlspecialchars($activity['title']) ?>"
                        required
                        class="w-full px-3 py-2 border rounded-lg mb-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white">

                    <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-white">Activity <?= $idx + 1 ?>
                        Description</label>
                    <textarea name="activity_desc_<?= $idx ?>" rows="2" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars($activity['description']) ?></textarea>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button type="submit"
        class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
        Save Changes
    </button>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>