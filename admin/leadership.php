<?php
session_start();
require_once __DIR__ . '/../src/Config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$leadership_file = BASE_PATH . '/data/leadership.json';
$leadership = json_decode(file_get_contents($leadership_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $leadership[] = [
                'id' => max(array_column($leadership, 'id')) + 1,
                'name' => $_POST['name'],
                'position' => $_POST['position'],
                'photo' => $_POST['photo'],
                'instagram' => $_POST['instagram']
            ];
            file_put_contents($leadership_file, json_encode($leadership, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/leadership.php');
            exit;
        } elseif ($_POST['action'] === 'edit') {
            foreach ($leadership as $key => $item) {
                if ($item['id'] == $_POST['id']) {
                    $leadership[$key] = [
                        'id' => (int) $_POST['id'],
                        'name' => $_POST['name'],
                        'position' => $_POST['position'],
                        'photo' => $_POST['photo'],
                        'instagram' => $_POST['instagram']
                    ];
                    break;
                }
            }
            file_put_contents($leadership_file, json_encode($leadership, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/leadership.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $leadership = array_values(array_filter($leadership, fn($l) => $l['id'] != $_POST['id']));
            file_put_contents($leadership_file, json_encode($leadership, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/leadership.php');
            exit;
        }
    }
}

// Group by role
$grouped = [
    'Ketua' => [],
    'Sekretaris' => [],
    'Bendahara' => []
];

foreach ($leadership as $member) {
    if (stripos($member['position'], 'ketua') !== false) {
        $grouped['Ketua'][] = $member;
    } elseif (stripos($member['position'], 'sekretaris') !== false) {
        $grouped['Sekretaris'][] = $member;
    } elseif (stripos($member['position'], 'bendahara') !== false) {
        $grouped['Bendahara'][] = $member;
    }
}

$title = 'Manage Leadership - Admin';
ob_start();
?>

<h2 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Manage Leadership</h2>

<div class="grid gap-6">
    <?php foreach ($grouped as $role => $members): ?>
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                        <?= substr($role, 0, 1) ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white"><?= $role ?></h3>
                        <p class="text-gray-600 dark:text-gray-400"><?= count($members) ?> members</p>
                    </div>
                </div>
                <button onclick="addMember('<?= $role ?>')"
                    class="px-3 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">Add Member</button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ($members as $member): ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center">
                        <img src="<?= asset('assets/images/' . $member['photo']) ?>"
                            class="w-16 h-16 rounded-full mx-auto mb-2 object-cover" alt="<?= $member['name'] ?>">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($member['name']) ?>
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2"><?= htmlspecialchars($member['position']) ?>
                        </p>
                        <div class="flex gap-1">
                            <button
                                onclick="editMember(<?= $member['id'] ?>, '<?= htmlspecialchars($member['name']) ?>', '<?= htmlspecialchars($member['position']) ?>', '<?= htmlspecialchars($member['photo']) ?>', '<?= htmlspecialchars($member['instagram']) ?>')"
                                class="flex-1 px-2 py-1 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600">Edit</button>
                            <form method="POST" class="flex-1" onsubmit="return confirm('Delete?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $member['id'] ?>">
                                <button type="submit"
                                    class="w-full px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600">Del</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Member Modal -->
<div id="memberModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white" id="modal_title">Add Member</h3>
        <form method="POST">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="id" id="member_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Name</label>
                    <input type="text" name="name" id="name" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Position</label>
                    <input type="text" name="position" id="position" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Photo Path</label>
                    <input type="text" name="photo" id="photo" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Instagram</label>
                    <input type="text" name="instagram" id="instagram" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="document.getElementById('memberModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function addMember(role) {
        document.getElementById('modal_title').textContent = 'Add Member to ' + role;
        document.getElementById('action').value = 'add';
        document.getElementById('name').value = '';
        document.getElementById('position').value = role === 'Ketua' ? 'Ketua ' : role === 'Sekretaris' ? 'Sekretaris ' : 'Bendahara ';
        document.getElementById('photo').value = 'sekbid/' + role.toLowerCase() + '/';
        document.getElementById('instagram').value = 'sabaevent';
        document.getElementById('memberModal').classList.remove('hidden');
    }

    function editMember(id, name, position, photo, instagram) {
        document.getElementById('modal_title').textContent = 'Edit Member';
        document.getElementById('action').value = 'edit';
        document.getElementById('member_id').value = id;
        document.getElementById('name').value = name;
        document.getElementById('position').value = position;
        document.getElementById('photo').value = photo;
        document.getElementById('instagram').value = instagram;
        document.getElementById('memberModal').classList.remove('hidden');
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>