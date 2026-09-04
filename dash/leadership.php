<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_PATH . '/login.php');
    exit;
}

$leadership_file = BASE_PATH . '/data/leadership.json';
$leadership = json_decode(file_get_contents($leadership_file), true);

// Normalize to new format (backward compat with flat array)
if (!isset($leadership['members'])) {
    $leadership = [
        'title' => 'Core Leadership',
        'team_photo' => 'sekbid/inti/ketua/ketua_team.webp',
        'members' => $leadership
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid form token, please try again';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_leadership') {
            $leadership['title'] = $_POST['title'];
            $leadership['team_photo'] = $_POST['team_photo'];
            file_put_contents($leadership_file, json_encode($leadership, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/leadership.php');
            exit;
        } elseif ($_POST['action'] === 'add') {
            $members = $leadership['members'];
            $members[] = [
                'id' => count($members) > 0 ? max(array_column($members, 'id')) + 1 : 1,
                'name' => $_POST['name'],
                'position' => $_POST['position'],
                'photo' => $_POST['photo'],
                'instagram' => $_POST['instagram']
            ];
            $leadership['members'] = $members;
            file_put_contents($leadership_file, json_encode($leadership, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/leadership.php');
            exit;
        } elseif ($_POST['action'] === 'edit') {
            foreach ($leadership['members'] as $key => $item) {
                if ($item['id'] == $_POST['id']) {
                    $leadership['members'][$key] = [
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
            header('Location: ' . ADMIN_PATH . '/leadership.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $leadership['members'] = array_values(array_filter($leadership['members'], fn($l) => $l['id'] != $_POST['id']));
            file_put_contents($leadership_file, json_encode($leadership, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/leadership.php');
            exit;
        }
    }
}

$grouped = [
    'Ketua' => [],
    'Sekretaris' => [],
    'Bendahara' => []
];

foreach ($leadership['members'] as $member) {
    if (stripos($member['position'], 'ketua') !== false) {
        $grouped['Ketua'][] = $member;
    } elseif (stripos($member['position'], 'sekretaris') !== false) {
        $grouped['Sekretaris'][] = $member;
    } elseif (stripos($member['position'], 'bendahara') !== false) {
        $grouped['Bendahara'][] = $member;
    }
}

$csrfToken = CSRF::generate();
$title = 'Manage Leadership - Admin';
ob_start();
?>

<h2 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Manage Leadership</h2>

<div class="flex items-center justify-between mb-6">
    <p class="text-gray-600 dark:text-gray-400">
        Title: <strong><?= h($leadership['title']) ?></strong> &middot;
        Team Photo: <strong><?= h($leadership['team_photo']) ?></strong>
    </p>
    <button onclick="editLeadership()"
        class="px-3 py-2 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">Edit Leadership</button>
</div>

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
                <button onclick="addMember('<?= js($role) ?>')"
                    class="px-3 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">Add Member</button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ($members as $member): ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center">
                        <img src="<?= asset('assets/images/' . $member['photo']) ?>"
                            class="w-16 h-16 rounded-full mx-auto mb-2 object-cover" alt="<?= h($member['name']) ?>">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white"><?= h($member['name']) ?>
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2"><?= h($member['position']) ?>
                        </p>
                        <div class="flex gap-1">
                            <button
                                onclick="editMember(<?= $member['id'] ?>, '<?= js($member['name']) ?>', '<?= js($member['position']) ?>', '<?= js($member['photo']) ?>', '<?= js($member['instagram']) ?>')"
                                class="flex-1 px-2 py-1 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600">Edit</button>
                            <form method="POST" class="flex-1" onsubmit="return confirm('Delete?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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

<!-- Leadership Modal -->
<div id="leadershipModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Edit Leadership</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="update_leadership">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Title</label>
                    <input type="text" name="title" id="leadership_title" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Team Photo Path</label>
                    <input type="text" name="team_photo" id="leadership_team_photo" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="document.getElementById('leadershipModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Member Modal -->
<div id="memberModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white" id="modal_title">Add Member</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
    function editLeadership() {
        document.getElementById('leadership_title').value = <?= json_encode($leadership['title'], JSON_UNESCAPED_UNICODE) ?>;
        document.getElementById('leadership_team_photo').value = <?= json_encode($leadership['team_photo'], JSON_UNESCAPED_UNICODE) ?>;
        document.getElementById('leadershipModal').classList.remove('hidden');
    }

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
