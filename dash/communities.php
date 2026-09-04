<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_PATH . '/login.php');
    exit;
}

$communities_file = BASE_PATH . '/data/communities.json';
$communities = json_decode(file_get_contents($communities_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid form token, please try again';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $community = [
                'id' => $_POST['action'] === 'add' ? (max(array_column($communities, 'id')) + 1) : (int) $_POST['id'],
                'name' => $_POST['name'],
                'slug' => $_POST['slug'],
                'description' => $_POST['description'],
                'image' => $_POST['image'],
                'category' => $_POST['category'] ?? 'General',
                'members' => (int) ($_POST['members'] ?? 0),
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
            $community['links'] = $links;

            if ($_POST['action'] === 'add') {
                $communities[] = $community;
            } else {
                foreach ($communities as $key => $item) {
                    if ($item['id'] == $_POST['id']) {
                        $communities[$key] = $community;
                        break;
                    }
                }
            }

            file_put_contents($communities_file, json_encode($communities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/communities.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $communities = array_values(array_filter($communities, fn($c) => $c['id'] != $_POST['id']));
            file_put_contents($communities_file, json_encode($communities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/communities.php');
            exit;
        }
    }
}

$csrfToken = CSRF::generate();
$title = 'Manage Communities - Admin';
ob_start();
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 sm:mb-8">
    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Manage Communities</h2>
    <button onclick="openModal('add')"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 w-full sm:w-auto">Add Community</button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    <?php foreach ($communities as $community): ?>
        <div class="card p-4 sm:p-6">
            <div class="aspect-video bg-gray-200 dark:bg-gray-800 rounded-lg mb-4 overflow-hidden">
                <img src="<?= asset('assets/images/' . $community['image']) ?>" class="w-full h-full object-cover"
                    alt="<?= htmlspecialchars($community['name']) ?>"
                    onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
            </div>
            <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-white"><?= htmlspecialchars($community['name']) ?>
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                <?= htmlspecialchars($community['description']) ?>
            </p>
            <div class="text-xs text-gray-500 dark:text-gray-500 mb-4">
                <span><?= $community['members'] ?> members</span>
            </div>
            <div class="flex gap-2">
                <button onclick='openModal("edit", <?= json_encode($community) ?>)'
                    class="flex-1 px-3 py-2 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">Edit</button>
                <form method="POST" class="flex-1" onsubmit="return confirm('Delete this community?')">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $community['id'] ?>">
                    <button type="submit"
                        class="w-full px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl sm:text-2xl font-bold mb-4 text-gray-900 dark:text-white" id="modalTitle">Add Community</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="id" id="id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Name</label>
                    <input type="text" name="name" id="name" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Slug</label>
                    <input type="text" name="slug" id="slug" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Image Path</label>
                    <input type="text" name="image" id="image" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="communities/image.webp">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Short Description</label>
                    <input type="text" name="description" id="description" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Category</label>
                    <select name="category" id="category" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <?php foreach (['Technology & Academics', 'Arts & Culture', 'Sports & Wellness', 'Science & Research', 'Social & Environment', 'Media & Journalism', 'Music & Performance', 'Leadership & Advocacy'] as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Member Count</label>
                    <input type="number" name="members" id="members" min="0"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">Displayed as "{count}+ members" on the community card.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">About (Markdown)</label>
                    <textarea name="about" id="about" rows="4" required
                        class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use Markdown syntax. HTML is also supported.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Content (Markdown)</label>
                    <textarea name="content" id="content" rows="12"
                        class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Main blog-style article content. Use Markdown. Leave empty to hide.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Activities (one per line)</label>
                    <textarea name="activities" id="activities" rows="5"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Optional. Leave empty to hide activities section.</p>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Social Media (Optional)</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">Instagram URL</label>
                            <input type="url" name="instagram" id="instagram" placeholder="https://instagram.com/..."
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">YouTube URL</label>
                            <input type="url" name="youtube" id="youtube" placeholder="https://youtube.com/..."
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">TikTok URL</label>
                            <input type="url" name="tiktok" id="tiktok" placeholder="https://tiktok.com/..."
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Additional Links</label>
                    <textarea name="links" id="links" rows="4" placeholder="label|url"
                        class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    <p class="text-xs text-gray-500 mt-1">One per line: <code>Label|https://...</code></p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="closeModal()"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(action, data = null) {
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('action').value = action;
        document.getElementById('modalTitle').textContent = action === 'add' ? 'Add Community' : 'Edit Community';

        if (action === 'edit' && data) {
            document.getElementById('id').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('slug').value = data.slug;
            document.getElementById('image').value = data.image;
            document.getElementById('description').value = data.description;
            document.getElementById('category').value = data.category || 'General';
            document.getElementById('members').value = data.members;
            document.getElementById('about').value = data.about || '';
            document.getElementById('content').value = data.content || '';
            document.getElementById('activities').value = (data.activities || []).join('\n');
            document.getElementById('instagram').value = data.instagram || '';
            document.getElementById('youtube').value = data.youtube || '';
            document.getElementById('tiktok').value = data.tiktok || '';
            document.getElementById('links').value = (data.links || []).map(l => l.label + '|' + l.url).join('\n');
        } else {
            document.getElementById('id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('slug').value = '';
            document.getElementById('image').value = 'communities/';
            document.getElementById('description').value = '';
            document.getElementById('category').value = 'Technology & Academics';
            document.getElementById('members').value = '0';
            document.getElementById('about').value = '';
            document.getElementById('content').value = '';
            document.getElementById('activities').value = '';
            document.getElementById('instagram').value = '';
            document.getElementById('youtube').value = '';
            document.getElementById('tiktok').value = '';
            document.getElementById('links').value = '';
        }
    }

    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
