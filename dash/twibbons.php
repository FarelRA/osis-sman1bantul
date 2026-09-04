<?php
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Core/CSRF.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_PATH . '/login.php');
    exit;
}

$twibbons_file = BASE_PATH . '/data/twibbons.json';
$twibbons = json_decode(file_get_contents($twibbons_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid form token, please try again';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $fields = [];
            if (isset($_POST['field_key'])) {
                foreach ($_POST['field_key'] as $i => $key) {
                    if (!empty($key)) {
                        $fields[] = [
                            'key' => $key,
                            'label' => $_POST['field_label'][$i] ?? '',
                            'placeholder' => $_POST['field_placeholder'][$i] ?? ''
                        ];
                    }
                }
            }

            $twibbon = [
                'id' => $_POST['id'],
                'name' => $_POST['name'],
                'caption' => [
                    'template' => $_POST['caption_template'] ?? '',
                    'fields' => $fields
                ]
            ];

            if ($_POST['action'] === 'add') {
                $twibbons[] = $twibbon;
            } else {
                foreach ($twibbons as $key => $item) {
                    if ($item['id'] === $_POST['old_id']) {
                        $twibbons[$key] = $twibbon;
                        break;
                    }
                }
            }

            file_put_contents($twibbons_file, json_encode($twibbons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/twibbons.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $twibbons = array_values(array_filter($twibbons, fn($t) => $t['id'] !== $_POST['id']));
            file_put_contents($twibbons_file, json_encode($twibbons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ' . ADMIN_PATH . '/twibbons.php');
            exit;
        }
    }
}

$editTwibbon = null;
if (isset($_GET['edit'])) {
    foreach ($twibbons as $twibbon) {
        if ($twibbon['id'] === $_GET['edit']) {
            $editTwibbon = $twibbon;
            break;
        }
    }
}

$csrfToken = CSRF::generate();
$title = 'Manage Twibbons - Admin';
ob_start();
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Twibbons</h2>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Add Twibbon
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php foreach ($twibbons as $twibbon): ?>
        <div class="card p-4">
            <img src="<?= asset('assets/twibbon/' . $twibbon['id'] . '.webp') ?>" class="w-full h-40 object-contain mb-3"
                alt="<?= $twibbon['name'] ?>">
            <h3 class="font-semibold mb-2 text-gray-900 dark:text-white"><?= htmlspecialchars($twibbon['name']) ?></h3>
            <div class="flex gap-2 mt-3">
                <button onclick="shareTwibbon('<?= $twibbon['id'] ?>')"
                    class="flex-1 px-3 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700">Share</button>
                <a href="?edit=<?= $twibbon['id'] ?>"
                    class="flex-1 px-3 py-2 bg-yellow-500 text-white rounded text-center text-sm hover:bg-yellow-600">Edit</a>
                <form method="POST" class="flex-1" onsubmit="return confirm('Delete this twibbon?')">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $twibbon['id'] ?>">
                    <button type="submit"
                        class="w-full px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div id="addModal"
    class="<?= $editTwibbon ? '' : 'hidden' ?> fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full my-8">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white"><?= $editTwibbon ? 'Edit' : 'Add' ?> Twibbon
        </h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="<?= $editTwibbon ? 'edit' : 'add' ?>">
            <?php if ($editTwibbon): ?>
                <input type="hidden" name="old_id" value="<?= $editTwibbon['id'] ?>">
            <?php endif; ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">ID</label>
                    <input type="text" name="id" value="<?= htmlspecialchars($editTwibbon['id'] ?? '') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="e.g., mpls-62">
                    <p class="text-xs text-gray-500 mt-1">Must match filename without .webp (e.g., mpls-62 for mpls-62.webp)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editTwibbon['name'] ?? '') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div class="border-t pt-4 mt-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Caption Template</label>
                    <textarea name="caption_template" rows="8"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm"
                        placeholder="Use {{field_key}} for dynamic fields"><?= htmlspecialchars($editTwibbon['caption']['template'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use {{field_key}} syntax for dynamic fields</p>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-gray-900 dark:text-white">Caption Fields</label>
                        <button type="button" onclick="addField()"
                            class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">+ Add Field</button>
                    </div>
                    <div id="fieldsContainer" class="space-y-2">
                        <?php if (!empty($editTwibbon['caption']['fields'])): ?>
                            <?php foreach ($editTwibbon['caption']['fields'] as $field): ?>
                                <div class="flex gap-2 field-row">
                                    <input type="text" name="field_key[]" value="<?= htmlspecialchars($field['key']) ?>"
                                        class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                        placeholder="Key (e.g., nama)">
                                    <input type="text" name="field_label[]" value="<?= htmlspecialchars($field['label']) ?>"
                                        class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                        placeholder="Label">
                                    <input type="text" name="field_placeholder[]" value="<?= htmlspecialchars($field['placeholder']) ?>"
                                        class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                        placeholder="Placeholder">
                                    <button type="button" onclick="this.parentElement.remove()"
                                        class="px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">×</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="window.location.href='<?= ADMIN_PATH ?>/twibbons.php'"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function shareTwibbon(id) {
    const url = window.location.origin + '/twibbon?id=' + encodeURIComponent(id);
    navigator.clipboard.writeText(url).then(() => {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg z-50 text-sm';
        toast.textContent = 'Share link copied!';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }).catch(() => {
        prompt('Copy this link:', url);
    });
}

function addField() {
    const container = document.getElementById('fieldsContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 field-row';
    div.innerHTML = `
        <input type="text" name="field_key[]" class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="Key (e.g., nama)">
        <input type="text" name="field_label[]" class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="Label">
        <input type="text" name="field_placeholder[]" class="flex-1 px-2 py-1 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="Placeholder">
        <button type="button" onclick="this.parentElement.remove()" class="px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">×</button>
    `;
    container.appendChild(div);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>