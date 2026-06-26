<?php
session_start();
require_once __DIR__ . '/../src/Config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubs = json_decode(file_get_contents(BASE_PATH . '/data/clubs.json'), true);

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $club = [
                'id' => $_POST['action'] === 'add' ? (max(array_column($clubs, 'id')) + 1) : (int) $_POST['id'],
                'name' => $_POST['name'],
                'slug' => $_POST['slug'],
                'logo' => $_POST['logo'],
                'description' => $_POST['description'],
                'about' => $_POST['about'],
                'activities' => array_filter(explode("\n", $_POST['activities']))
            ];

            if ($_POST['action'] === 'add') {
                $clubs[] = $club;
            } else {
                foreach ($clubs as $key => $item) {
                    if ($item['id'] == $_POST['id']) {
                        $clubs[$key] = $club;
                        break;
                    }
                }
            }

            file_put_contents(BASE_PATH . '/data/clubs.json', json_encode($clubs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/ukk.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $clubs = array_values(array_filter($clubs, fn($item) => $item['id'] != $_POST['id']));
            file_put_contents(BASE_PATH . '/data/clubs.json', json_encode($clubs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/ukk.php');
            exit;
        }
    }
}

$title = 'Manage UKK - Admin';
$clubs = json_decode(file_get_contents(BASE_PATH . '/data/clubs.json'), true);
$editClub = null;

if (isset($_GET['edit'])) {
    foreach ($clubs as $club) {
        if ($club['id'] == $_GET['edit']) {
            $editClub = $club;
            break;
        }
    }
}

ob_start();
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Unit Kegiatan Kesiswaan</h2>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Add New Club
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($clubs as $org): ?>
        <div class="card p-6">
            <img src="<?= asset('assets/images/' . $org['logo']) ?>" class="w-20 h-20 mx-auto mb-4 object-contain"
                alt="<?= $org['name'] ?>">
            <h3 class="font-semibold text-lg text-center mb-2 text-gray-900 dark:text-white">
                <?= htmlspecialchars($org['name']) ?>
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">
                <?= htmlspecialchars($org['description']) ?>
            </p>
            <div class="flex gap-2">
                <a href="?edit=<?= $org['id'] ?>"
                    class="flex-1 px-3 py-2 bg-yellow-500 text-white rounded text-center text-sm hover:bg-yellow-600">Edit</a>
                <form method="POST" class="flex-1" onsubmit="return confirm('Delete this club?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $org['id'] ?>">
                    <button type="submit"
                        class="w-full px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div id="addModal"
    class="<?= $editClub ? '' : 'hidden' ?> fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white"><?= $editClub ? 'Edit' : 'Add' ?> Club</h3>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editClub ? 'edit' : 'add' ?>">
            <?php if ($editClub): ?>
                <input type="hidden" name="id" value="<?= $editClub['id'] ?>">
            <?php endif; ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editClub['name'] ?? '') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($editClub['slug'] ?? '') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Logo Path</label>
                    <input type="text" name="logo" value="<?= htmlspecialchars($editClub['logo'] ?? 'ukk/') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Short
                        Description</label>
                    <input type="text" name="description"
                        value="<?= htmlspecialchars($editClub['description'] ?? '') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">About (Markdown)</label>
                    <textarea name="about" rows="4" required
                        class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars($editClub['about'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use Markdown syntax. HTML is also supported.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Activities (one per
                        line)</label>
                    <textarea name="activities" rows="5" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars(implode("\n", $editClub['activities'] ?? [])) ?></textarea>
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="window.location.href='/admin/ukk.php'"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>