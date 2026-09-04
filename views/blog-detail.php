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
                <div class="mb-8 rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 aspect-video">
                    <img src="<?= asset('assets/images/' . $blog['image']) ?>" class="w-full h-full object-cover"
                        alt="<?= htmlspecialchars($blog['title']) ?>"
                        onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
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