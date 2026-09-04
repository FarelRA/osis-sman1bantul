<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_PATH . '/login.php');
    exit;
}

$programs_file = BASE_PATH . '/data/program-kerja.json';
$programs = json_decode(file_get_contents($programs_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid form token, please try again';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_role') {
            $div = $_POST['division'];
            $role = $_POST['role_name'];
            $programs[$div][$role] = [];
            file_put_contents($programs_file, json_encode($programs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/program-kerja.php');
            exit;
        } elseif ($_POST['action'] === 'delete_role') {
            $div = $_POST['division'];
            $role = $_POST['role'];
            unset($programs[$div][$role]);
            file_put_contents($programs_file, json_encode($programs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/program-kerja.php');
            exit;
        } elseif ($_POST['action'] === 'add_program') {
            $div = $_POST['division'];
            $role = $_POST['role'];
            $programs[$div][$role][] = $_POST['program'];
            file_put_contents($programs_file, json_encode($programs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/program-kerja.php');
            exit;
        } elseif ($_POST['action'] === 'edit_program') {
            $div = $_POST['division'];
            $role = $_POST['role'];
            $idx = (int) $_POST['index'];
            $programs[$div][$role][$idx] = $_POST['program'];
            file_put_contents($programs_file, json_encode($programs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/program-kerja.php');
            exit;
        } elseif ($_POST['action'] === 'delete_program') {
            $div = $_POST['division'];
            $role = $_POST['role'];
            $idx = (int) $_POST['index'];
            array_splice($programs[$div][$role], $idx, 1);
            file_put_contents($programs_file, json_encode($programs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/program-kerja.php');
            exit;
        }
    }
}

$csrfToken = CSRF::generate();
$title = 'Manage Program Kerja - Admin';
ob_start();
?>

<h2 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Program Kerja</h2>

<div class="grid gap-6">
    <?php foreach ($programs as $num => $program): ?>
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                        <?= $num ?>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Sekbid <?= $num ?></h3>
                </div>
                <button onclick="addRole(<?= $num ?>)"
                    class="px-3 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">Add Role</button>
            </div>

            <div class="space-y-4">
                <?php foreach ($program as $role => $items): ?>
                    <?php if (is_array($items)): ?>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900 dark:text-white capitalize">
                                    <?= str_replace('_', ' ', $role) ?>
                                </h4>
                                <div class="flex gap-2">
                                    <button onclick="addProgram(<?= $num ?>, '<?= $role ?>')"
                                        class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600">Add
                                        Program</button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this role?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="delete_role">
                                        <input type="hidden" name="division" value="<?= $num ?>">
                                        <input type="hidden" name="role" value="<?= $role ?>">
                                        <button type="submit"
                                            class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600">Delete
                                            Role</button>
                                    </form>
                                </div>
                            </div>
                            <ul class="space-y-2">
                                <?php foreach ($items as $idx => $item): ?>
                                    <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="flex-1">• <?= htmlspecialchars($item) ?></span>
                                        <button
                                            onclick="editProgram(<?= $num ?>, '<?= $role ?>', <?= $idx ?>, '<?= htmlspecialchars($item) ?>')"
                                            class="px-2 py-1 bg-yellow-500 text-white rounded text-xs hover:bg-yellow-600">Edit</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="action" value="delete_program">
                                            <input type="hidden" name="division" value="<?= $num ?>">
                                            <input type="hidden" name="role" value="<?= $role ?>">
                                            <input type="hidden" name="index" value="<?= $idx ?>">
                                            <button type="submit"
                                                class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600">Del</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Role Modal -->
<div id="roleModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Add Role</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="add_role">
            <input type="hidden" name="division" id="role_div">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Role Name (use
                        underscore for spaces)</label>
                    <input type="text" name="role_name" required placeholder="e.g., koordinator, sie_agama"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="document.getElementById('roleModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Program Modal -->
<div id="programModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white" id="program_modal_title">Add Program</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" id="program_action" value="add_program">
            <input type="hidden" name="division" id="program_div">
            <input type="hidden" name="role" id="program_role">
            <input type="hidden" name="index" id="program_idx">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Program
                        Description</label>
                    <textarea name="program" id="program_text" rows="3" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="document.getElementById('programModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function addRole(div) {
        document.getElementById('role_div').value = div;
        document.getElementById('roleModal').classList.remove('hidden');
    }

    function addProgram(div, role) {
        document.getElementById('program_modal_title').textContent = 'Add Program';
        document.getElementById('program_action').value = 'add_program';
        document.getElementById('program_div').value = div;
        document.getElementById('program_role').value = role;
        document.getElementById('program_text').value = '';
        document.getElementById('programModal').classList.remove('hidden');
    }

    function editProgram(div, role, idx, text) {
        document.getElementById('program_modal_title').textContent = 'Edit Program';
        document.getElementById('program_action').value = 'edit_program';
        document.getElementById('program_div').value = div;
        document.getElementById('program_role').value = role;
        document.getElementById('program_idx').value = idx;
        document.getElementById('program_text').value = text;
        document.getElementById('programModal').classList.remove('hidden');
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>