<?php
session_start();
require_once __DIR__ . '/../src/Config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$communities_file = BASE_PATH . '/data/communities.json';
$communities = json_decode(file_get_contents($communities_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $community = [
                'id' => $_POST['action'] === 'add' ? (max(array_column($communities, 'id')) + 1) : (int) $_POST['id'],
                'name' => $_POST['name'],
                'slug' => $_POST['slug'],
                'description' => $_POST['description'],
                'image' => $_POST['image'],
                'members' => (int) $_POST['members'],
                'instagram' => $_POST['instagram']
            ];

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
            header('Location: /admin/communities.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $communities = array_values(array_filter($communities, fn($c) => $c['id'] != $_POST['id']));
            file_put_contents($communities_file, json_encode($communities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/communities.php');
            exit;
        }
    }
}

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
                    onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'">
            </div>
            <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-white"><?= htmlspecialchars($community['name']) ?>
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                <?= htmlspecialchars($community['description']) ?>
            </p>
            <div class="text-xs text-gray-500 dark:text-gray-500 mb-4">
                <span><?= $community['members'] ?> members</span> •
                <span><?= htmlspecialchars($community['instagram']) ?></span>
            </div>
            <div class="flex gap-2">
                <button onclick='openModal("edit", <?= json_encode($community) ?>)'
                    class="flex-1 px-3 py-2 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">Edit</button>
                <form method="POST" class="flex-1" onsubmit="return confirm('Delete this community?')">
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
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl sm:text-2xl font-bold mb-4 text-gray-900 dark:text-white" id="modalTitle">Add Community</h3>
        <form method="POST">
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
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Description</label>
                    <textarea name="description" id="description" rows="3" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Image Path</label>
                    <input type="text" name="image" id="image" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="communities/image.jpg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Members Count</label>
                    <input type="number" name="members" id="members" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Instagram</label>
                    <input type="text" name="instagram" id="instagram" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="@username">
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
            document.getElementById('description').value = data.description;
            document.getElementById('image').value = data.image;
            document.getElementById('members').value = data.members;
            document.getElementById('instagram').value = data.instagram;
        } else {
            document.getElementById('id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('slug').value = '';
            document.getElementById('description').value = '';
            document.getElementById('image').value = 'communities/';
            document.getElementById('members').value = '0';
            document.getElementById('instagram').value = '@';
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