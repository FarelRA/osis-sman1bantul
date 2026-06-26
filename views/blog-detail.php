<?php
require_once BASE_PATH . '/src/Markdown.php';
$title = htmlspecialchars($blog['title']) . ' - OSIS SMAN 1 Bantul';
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Blog Header -->
            <div class="mb-8">
                <a href="/blogs" class="text-cyan-500 hover:text-cyan-600 mb-4 inline-block">&larr; Back to Blogs</a>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900 dark:text-white">
                    <?= htmlspecialchars($blog['title']) ?>
                </h1>
                <div class="flex items-center gap-4 text-gray-600 dark:text-gray-400 mb-6">
                    <span>By <?= htmlspecialchars($blog['author']) ?></span>
                    <span>•</span>
                    <span><?= date('F j, Y', strtotime($blog['date'])) ?></span>
                    <span class="badge bg-blue-100 text-blue-700"><?= htmlspecialchars($blog['category']) ?></span>
                </div>
            </div>

            <!-- Blog Image -->
            <?php if (!empty($blog['image'])): ?>
                <div class="mb-8 rounded-xl overflow-hidden">
                    <img src="<?= asset('assets/images/' . $blog['image']) ?>" class="w-full h-96 object-cover"
                        alt="<?= $blog['title'] ?>">
                </div>
            <?php endif; ?>

            <!-- Blog Content -->
            <div class="card p-8">
                <div class="prose dark:prose-invert max-w-none">
                    <?= markdown($blog['content']) ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>