<?php
require_once BASE_PATH . '/src/Markdown.php';
$title = htmlspecialchars($event['title']) . ' - OSIS SMAN 1 Bantul';
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Event Header -->
            <div class="mb-8">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900 dark:text-white">
                    <?= htmlspecialchars($event['title']) ?>
                </h1>
                <div class="flex items-center gap-4 text-gray-600 dark:text-gray-400 mb-6">
                    <span><?= date('F j, Y', strtotime($event['date'])) ?></span>
                    <span
                        class="badge <?= $event['status'] === 'upcoming' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                        <?= ucfirst($event['status']) ?>
                    </span>
                </div>
            </div>

            <!-- Event Image -->
            <div class="mb-8 rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 aspect-video">
                <img src="<?= asset('assets/images/' . $event['image']) ?>" class="w-full h-full object-cover"
                    alt="<?= htmlspecialchars($event['title']) ?>"
                    onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
            </div>

            <!-- Event Content -->
            <div class="card p-8">
                <div class="prose dark:prose-invert max-w-none">
                    <?= markdown($event['content'] ?? $event['description']) ?>
                </div>

                <?php if (!empty($event['registration']['enabled']) && $event['registration']['enabled']): ?>
                    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Join this Event</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Registration is open! Secure your spot now.</p>
                        <a href="<?= getFormUrl('event', $event['slug']) ?>"
                            class="inline-block bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold py-3 px-8 rounded-full shadow-lg transform hover:-translate-y-1 transition-all duration-200">
                            Register Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>