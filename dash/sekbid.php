<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_PATH . '/login.php');
    exit;
}

$sekbid_file = BASE_PATH . '/data/sekbid.json';
$sekbid = json_decode(file_get_contents($sekbid_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid form token, please try again';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_division') {
            $div = $_POST['division'];
            $sekbid[$div]['title'] = $_POST['title'];
            $sekbid[$div]['team_photo'] = $_POST['team_photo'];
            file_put_contents($sekbid_file, json_encode($sekbid, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/sekbid.php');
            exit;
        } elseif ($_POST['action'] === 'add_member') {
            $div = $_POST['division'];
            $sekbid[$div]['members'][] = [
                'name' => $_POST['name'],
                'position' => $_POST['position'],
                'photo' => $_POST['photo']
            ];
            file_put_contents($sekbid_file, json_encode($sekbid, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/sekbid.php');
            exit;
        } elseif ($_POST['action'] === 'edit_member') {
            $div = $_POST['division'];
            $idx = (int) $_POST['index'];
            $sekbid[$div]['members'][$idx] = [
                'name' => $_POST['name'],
                'position' => $_POST['position'],
                'photo' => $_POST['photo']
            ];
            file_put_contents($sekbid_file, json_encode($sekbid, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/sekbid.php');
            exit;
        } elseif ($_POST['action'] === 'delete_member') {
            $div = $_POST['division'];
            $idx = (int) $_POST['index'];
            array_splice($sekbid[$div]['members'], $idx, 1);
            file_put_contents($sekbid_file, json_encode($sekbid, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/sekbid.php');
            exit;
        }
    }
}

$csrfToken = CSRF::generate();
$title = 'Manage Sekbid - Admin';
ob_start();
?>

<h2 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Manage Sekbid</h2>

<div class="grid gap-6">
    <?php foreach ($sekbid as $num => $division): ?>
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                        <?= $num ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Sekbid <?= $num ?></h3>
                        <p class="text-gray-600 dark:text-gray-400"><?= h($division['title']) ?></p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        onclick="editDivision(<?= $num ?>, '<?= js($division['title']) ?>', '<?= js($division['team_photo']) ?>')"
                        class="px-3 py-2 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">Edit
                        Division</button>
                    <button onclick="addMember(<?= $num ?>)"
                        class="px-3 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">Add Member</button>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ($division['members'] as $idx => $member): ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-center">
                        <img src="<?= asset('assets/images/' . $member['photo']) ?>"
                            class="w-16 h-16 rounded-full mx-auto mb-2 object-cover" alt="<?= h($member['name']) ?>">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white"><?= h($member['name']) ?>
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2"><?= h($member['position']) ?>
                        </p>
                        <div class="flex gap-1">
                            <button
                                onclick="editMember(<?= $num ?>, <?= $idx ?>, '<?= js($member['name']) ?>', '<?= js($member['position']) ?>', '<?= js($member['photo']) ?>')"
                                class="flex-1 px-2 py-1 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600">Edit</button>
                            <form method="POST" class="flex-1" onsubmit="return confirm('Delete?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="delete_member">
                                <input type="hidden" name="division" value="<?= $num ?>">
                                <input type="hidden" name="index" value="<?= $idx ?>">
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

<!-- Division Modal -->
<div id="divisionModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Edit Division</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="update_division">
            <input type="hidden" name="division" id="div_num">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Title</label>
                    <input type="text" name="title" id="div_title" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Team Photo Path</label>
                    <input type="text" name="team_photo" id="div_photo" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="document.getElementById('divisionModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Member Modal -->
<div id="memberModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white" id="member_modal_title">Add Member</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" id="member_action" value="add_member">
            <input type="hidden" name="division" id="member_div">
            <input type="hidden" name="index" id="member_idx">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Name</label>
                    <input type="text" name="name" id="member_name" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Position</label>
                    <input type="text" name="position" id="member_position" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Photo Path</label>
                    <input type="text" name="photo" id="member_photo" required
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
    function editDivision(num, title, photo) {
        document.getElementById('div_num').value = num;
        document.getElementById('div_title').value = title;
        document.getElementById('div_photo').value = photo;
        document.getElementById('divisionModal').classList.remove('hidden');
    }

    function addMember(div) {
        document.getElementById('member_modal_title').textContent = 'Add Member';
        document.getElementById('member_action').value = 'add_member';
        document.getElementById('member_div').value = div;
        document.getElementById('member_name').value = '';
        document.getElementById('member_position').value = '';
        document.getElementById('member_photo').value = 'sekbid/sekbid_' + div + '/';
        document.getElementById('memberModal').classList.remove('hidden');
    }

    function editMember(div, idx, name, position, photo) {
        document.getElementById('member_modal_title').textContent = 'Edit Member';
        document.getElementById('member_action').value = 'edit_member';
        document.getElementById('member_div').value = div;
        document.getElementById('member_idx').value = idx;
        document.getElementById('member_name').value = name;
        document.getElementById('member_position').value = position;
        document.getElementById('member_photo').value = photo;
        document.getElementById('memberModal').classList.remove('hidden');
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>