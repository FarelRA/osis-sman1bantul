<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_PATH . '/login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid form token, please try again';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    $clubs = json_decode(file_get_contents(BASE_PATH . '/data/clubs.json'), true);

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $club = [
                'id' => $_POST['action'] === 'add' ? (max(array_column($clubs, 'id')) + 1) : (int) $_POST['id'],
                'name' => $_POST['name'],
                'slug' => $_POST['slug'],
                'logo' => $_POST['logo'],
                'category' => $_POST['category'] ?? 'General',
                'members' => (int) ($_POST['members'] ?? 0),
                'description' => $_POST['description'],
                'about' => $_POST['about'],
                'activities' => array_filter(explode("\n", $_POST['activities'] ?? '')),
                'content' => $_POST['content'] ?? '',
                'instagram' => $_POST['instagram'] ?? '',
                'youtube' => $_POST['youtube'] ?? '',
                'tiktok' => $_POST['tiktok'] ?? '',
            ];

            $links = [];
            foreach (array_filter(explode("\n", $_POST['links'] ?? '')) as $line) {
                $parts = explode('|', $line, 2);
                if (count($parts) === 2) {
                    $links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
                }
            }
            $club['links'] = $links;

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
            header('Location: ' . ADMIN_PATH . '/clubs.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $clubs = array_values(array_filter($clubs, fn($item) => $item['id'] != $_POST['id']));
            file_put_contents(BASE_PATH . '/data/clubs.json', json_encode($clubs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/clubs.php');
            exit;
        }
    }
}

$csrfToken = CSRF::generate();
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
        <div class="card overflow-hidden">
            <div class="aspect-video overflow-hidden bg-gray-100 dark:bg-gray-700">
                <img src="<?= asset('assets/images/' . $org['logo']) ?>" class="w-full h-full object-cover"
                    alt="<?= $org['name'] ?>"
                    onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
            </div>
            <div class="p-4">
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
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $org['id'] ?>">
                        <button type="submit"
                            class="w-full px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">Delete</button>
                    </form>
                </div>
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
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Category</label>
                    <select name="category" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <?php foreach (['Technology & Academics', 'Arts & Culture', 'Sports & Wellness', 'Science & Research', 'Social & Environment', 'Media & Journalism', 'Music & Performance', 'Leadership & Advocacy'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($editClub['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Member Count</label>
                    <input type="number" name="members" min="0"
                        value="<?= htmlspecialchars($editClub['members'] ?? '0') ?>"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">Displayed as "{count}+ members" on the club card.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">About (Markdown)</label>
                    <textarea name="about" rows="4" required
                        class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars($editClub['about'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use Markdown syntax. HTML is also supported.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Content (Markdown)</label>
                    <textarea name="content" rows="12"
                        class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars($editClub['content'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Main blog-style article content. Use Markdown. Leave empty to hide.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Activities (one per
                        line)</label>
                    <textarea name="activities" rows="5"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars(implode("\n", $editClub['activities'] ?? [])) ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Optional. Leave empty to hide activities section.</p>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Social Media (Optional)</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">Instagram URL</label>
                            <input type="url" name="instagram" placeholder="https://instagram.com/..."
                                value="<?= htmlspecialchars($editClub['instagram'] ?? '') ?>"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">YouTube URL</label>
                            <input type="url" name="youtube" placeholder="https://youtube.com/..."
                                value="<?= htmlspecialchars($editClub['youtube'] ?? '') ?>"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">TikTok URL</label>
                            <input type="url" name="tiktok" placeholder="https://tiktok.com/..."
                                value="<?= htmlspecialchars($editClub['tiktok'] ?? '') ?>"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Additional Links</label>
                    <textarea name="links" rows="4" placeholder="label|url"
                        class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars(implode("\n", array_map(fn($l) => $l['label'] . '|' . $l['url'], $editClub['links'] ?? []))) ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">One per line: <code>Label|https://...</code></p>
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="window.location.href='<?= ADMIN_PATH ?>/clubs.php'"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>