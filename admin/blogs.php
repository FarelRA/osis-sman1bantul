<?php
session_start();
require_once __DIR__ . '/../src/Config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$blogs_file = BASE_PATH . '/data/blogs.json';
$blogs = json_decode(file_get_contents($blogs_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $blog = [
                'id' => $_POST['action'] === 'add' ? (count($blogs) > 0 ? max(array_column($blogs, 'id')) + 1 : 1) : (int) $_POST['id'],
                'title' => $_POST['title'],
                'slug' => $_POST['slug'],
                'excerpt' => $_POST['excerpt'],
                'author' => $_POST['author'],
                'date' => $_POST['date'],
                'image' => $_POST['image'],
                'category' => $_POST['category'],
                'content' => $_POST['content']
            ];

            if ($_POST['action'] === 'add') {
                $blogs[] = $blog;
            } else {
                foreach ($blogs as $key => $item) {
                    if ($item['id'] == $_POST['id']) {
                        $blogs[$key] = $blog;
                        break;
                    }
                }
            }

            file_put_contents($blogs_file, json_encode($blogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/blogs.php');
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $blogs = array_values(array_filter($blogs, fn($b) => $b['id'] != $_POST['id']));
            file_put_contents($blogs_file, json_encode($blogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: /admin/blogs.php');
            exit;
        }
    }
}

$editBlog = null;
if (isset($_GET['edit'])) {
    foreach ($blogs as $blog) {
        if ($blog['id'] == $_GET['edit']) {
            $editBlog = $blog;
            break;
        }
    }
}

$title = 'Manage Blogs - Admin';
ob_start();
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Blogs</h2>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Add Blog Post
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($blogs as $blog): ?>
        <div class="card p-6">
            <?php if (!empty($blog['image'])): ?>
                <img src="<?= asset('assets/images/' . $blog['image']) ?>" class="w-full h-40 object-cover rounded mb-4"
                    alt="<?= $blog['title'] ?>">
            <?php endif; ?>
            <span class="badge bg-blue-500 text-white mb-2"><?= htmlspecialchars($blog['category']) ?></span>
            <h3 class="font-semibold text-lg mb-2 text-gray-900 dark:text-white"><?= htmlspecialchars($blog['title']) ?>
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2"><?= htmlspecialchars($blog['excerpt']) ?></p>
            <p class="text-xs text-gray-500 mb-4">By <?= htmlspecialchars($blog['author']) ?> •
                <?= date('M j, Y', strtotime($blog['date'])) ?>
            </p>
            <div class="flex gap-2">
                <a href="?edit=<?= $blog['id'] ?>"
                    class="flex-1 px-3 py-2 bg-yellow-500 text-white rounded text-center text-sm hover:bg-yellow-600">Edit</a>
                <form method="POST" class="flex-1" onsubmit="return confirm('Delete this blog post?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $blog['id'] ?>">
                    <button type="submit"
                        class="w-full px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Modal -->
<div id="addModal"
    class="<?= $editBlog ? '' : 'hidden' ?> fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full my-8">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white"><?= $editBlog ? 'Edit' : 'Add' ?> Blog Post
        </h3>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editBlog ? 'edit' : 'add' ?>">
            <?php if ($editBlog): ?>
                <input type="hidden" name="id" value="<?= $editBlog['id'] ?>">
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($editBlog['title'] ?? '') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($editBlog['slug'] ?? '') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Excerpt</label>
                <input type="text" name="excerpt" value="<?= htmlspecialchars($editBlog['excerpt'] ?? '') ?>" required
                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="grid grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Author</label>
                    <input type="text" name="author"
                        value="<?= htmlspecialchars($editBlog['author'] ?? 'OSIS SMAN 1 Bantul') ?>" required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Date</label>
                    <input type="datetime-local" name="date"
                        value="<?= isset($editBlog['date']) ? date('Y-m-d\TH:i', strtotime($editBlog['date'])) : '' ?>"
                        required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Category</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($editBlog['category'] ?? 'Tips') ?>"
                        required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Image Path</label>
                    <input type="text" name="image" value="<?= htmlspecialchars($editBlog['image'] ?? '') ?>"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-white">Content (Markdown)</label>
                <textarea name="content" rows="12" required
                    class="w-full px-3 py-2 border rounded-lg font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?= htmlspecialchars($editBlog['content'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Use Markdown syntax. HTML is also supported.</p>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                <button type="button" onclick="window.location.href='/admin/blogs.php'"
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>