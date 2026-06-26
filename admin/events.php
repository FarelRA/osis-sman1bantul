<?php
session_start();
require_once __DIR__ . '/../src/Config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$events_file = BASE_PATH . '/data/events.json';
$events = json_decode(file_get_contents($events_file), true);

$forms_file = BASE_PATH . '/data/forms.json';
$formsData = file_exists($forms_file) ? json_decode(file_get_contents($forms_file), true) : [];
// Handle new structure with 'forms' key, or legacy flat array
$forms = isset($formsData['forms']) ? $formsData['forms'] : $formsData;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {

            // Construct Registration Config
            $registration = [
                'enabled' => isset($_POST['reg_enabled']),
                'title' => $_POST['reg_title'] ?? 'Register',
                'form_id' => $_POST['reg_form_id'] ?? ''
            ];

            $event = [
                'id' => $_POST['action'] === 'add' ? (max(array_column($events, 'id') ?: [0]) + 1) : (int) $_POST['id'],
                'title' => $_POST['title'],
                'slug' => $_POST['slug'],
                'description' => $_POST['description'],
                'date' => $_POST['date'],
                'image' => $_POST['image'],
                'status' => $_POST['status'],
                'content' => $_POST['content'],
                'registration' => $registration
            ];

            if ($_POST['action'] === 'add') {
                $events[] = $event;
            } else {
                foreach ($events as $key => $item) {
                    if ($item['id'] == $_POST['id']) {
                        $events[$key] = $event;
                        break;
                    }
                }
            }

            file_put_contents($events_file, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/events.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $events = array_values(array_filter($events, fn($e) => $e['id'] != $_POST['id']));
            file_put_contents($events_file, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/events.php');
            exit;
        }
    }
}

$editEvent = null;
if (isset($_GET['edit'])) {
    foreach ($events as $event) {
        if ($event['id'] == $_GET['edit']) {
            $editEvent = $event;
            break;
        }
    }
}

$title = 'Manage Events - Admin';
ob_start();
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Events</h2>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Add Event
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($events as $event): ?>
        <div class="card p-6">
            <div class="h-40 overflow-hidden rounded mb-4 bg-gray-100">
                <img src="<?= asset('assets/images/' . $event['image']) ?>" class="w-full h-full object-cover"
                    alt="<?= $event['title'] ?>">
            </div>
            <span
                class="badge <?= $event['status'] === 'upcoming' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' ?> mb-2">
                <?= ucfirst($event['status']) ?>
            </span>
            <h3 class="font-semibold text-lg mb-2 text-gray-900 dark:text-white"><?= htmlspecialchars($event['title']) ?>
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4"><?= date('F j, Y', strtotime($event['date'])) ?></p>

            <?php if (!empty($event['registration']['enabled'])): ?>
                <div class="mb-4">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Registration Active
                    </span>
                </div>
            <?php endif; ?>

            <div class="flex gap-2">
                <a href="?edit=<?= $event['id'] ?>"
                    class="flex-1 px-3 py-2 bg-yellow-500 text-white rounded text-center text-sm hover:bg-yellow-600">Edit</a>
                <form method="POST" class="flex-1" onsubmit="return confirm('Delete this event?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $event['id'] ?>">
                    <button type="submit"
                        class="w-full px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div id="addModal"
    class="<?= $editEvent ? '' : 'hidden' ?> fixed inset-0 bg-gray-900/90 backdrop-blur-sm z-50 overflow-y-auto"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="min-h-screen flex items-start justify-center p-4">
        <div class="bg-white dark:bg-gray-900 w-full max-w-6xl rounded-2xl shadow-2xl my-8 overflow-hidden">

            <form method="POST">
                <input type="hidden" name="action" value="<?= $editEvent ? 'edit' : 'add' ?>">
                <?php if ($editEvent): ?>
                    <input type="hidden" name="id" value="<?= $editEvent['id'] ?>">
                <?php endif; ?>

                <!-- Modal Header -->
                <div
                    class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-8 py-5 flex items-center justify-between sticky top-0 z-10">
                    <div class="flex items-center gap-4">
                        <button type="button" onclick="window.location.href='/admin/events.php'"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            <?= $editEvent ? 'Edit Event' : 'Create Event' ?>
                        </h2>
                    </div>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Save Changes
                    </button>
                </div>

                <div class="p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- Left Column: Settings (4/12) -->
                    <div class="lg:col-span-4 space-y-6">
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3
                                class="font-bold text-lg mb-6 text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2">
                                Event Details</h3>
                            <div class="space-y-5">
                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Event
                                        Title</label>
                                    <input type="text" name="title"
                                        value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>" required
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 placeholder-gray-400"
                                        placeholder="e.g. SINTESA 2026">
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">URL
                                        Slug</label>
                                    <input type="text" name="slug"
                                        value="<?= htmlspecialchars($editEvent['slug'] ?? '') ?>" required
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 placeholder-gray-400 font-mono text-sm"
                                        placeholder="sintesa-2026">
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Short
                                        Description</label>
                                    <textarea name="description" required rows="3"
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 placeholder-gray-400"><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Date
                                        & Time</label>
                                    <input type="datetime-local" name="date"
                                        value="<?= isset($editEvent['date']) ? date('Y-m-d\TH:i', strtotime($editEvent['date'])) : '' ?>"
                                        required
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 cursor-pointer">
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Status</label>
                                    <div class="relative">
                                        <select name="status" required
                                            class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 appearance-none cursor-pointer">
                                            <option value="upcoming" <?= ($editEvent['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                            <option value="done" <?= ($editEvent['status'] ?? '') === 'done' ? 'selected' : '' ?>>Done</option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Image
                                        Path</label>
                                    <input type="text" name="image"
                                        value="<?= htmlspecialchars($editEvent['image'] ?? 'event/') ?>" required
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 font-mono text-sm">
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3
                                class="font-bold text-lg mb-6 text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2">
                                Registration</h3>
                            <div class="space-y-5">
                                <div
                                    class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Enable Registration</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="reg_enabled"
                                            <?= !empty($editEvent['registration']['enabled']) ? 'checked' : '' ?>
                                            class="sr-only peer">
                                        <div
                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                        </div>
                                    </label>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Linked
                                        Form</label>
                                    <div class="relative">
                                        <select name="reg_form_id"
                                            class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 text-sm appearance-none cursor-pointer">
                                            <option value="">-- Select a Form --</option>
                                            <?php foreach ($forms as $form): ?>
                                                <option value="<?= $form['id'] ?>" <?= ($editEvent['registration']['form_id'] ?? '') === $form['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($form['title']) ?> (ID: <?= $form['id'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Create forms in <a href="/admin/forms.php"
                                            class="text-blue-600 hover:text-blue-700 font-medium hover:underline">Form
                                            Builder</a>
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-bold mb-2 text-gray-800 dark:text-gray-200 uppercase tracking-wide">Reg.
                                        Page Title</label>
                                    <input type="text" name="reg_title"
                                        value="<?= htmlspecialchars($editEvent['registration']['title'] ?? 'Register') ?>"
                                        class="w-full px-4 py-3 border-2 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white border-gray-100 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 outline-none transition-all duration-200 text-sm"
                                        placeholder="e.g. Join the Event">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Content Editor (8/12) -->
                    <div class="lg:col-span-8 space-y-6">
                        <div
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                            <div
                                class="bg-gray-50 dark:bg-gray-700/30 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Content (Markdown)</h3>
                            </div>
                            <div class="p-0 flex-1">
                                <textarea name="content" required
                                    class="w-full h-full min-h-[600px] p-6 border-0 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-0 outline-none transition-all duration-200 font-mono text-sm leading-relaxed placeholder-gray-400 resize-none"
                                    placeholder="# Event Details..."><?= htmlspecialchars($editEvent['content'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>