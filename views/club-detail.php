<?php
require_once BASE_PATH . '/src/Markdown.php';
$title = htmlspecialchars($club['name']) . ' - OSIS SMAN 1 Bantul';
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Club Header -->
            <div class="text-center mb-12">
                <div class="w-32 h-32 mx-auto mb-6">
                    <img src="<?= asset('assets/images/' . $club['logo']) ?>" class="w-full h-full object-contain"
                        alt="<?= $club['name'] ?>">
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900 dark:text-white">
                    <?= htmlspecialchars($club['name']) ?>
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400"><?= htmlspecialchars($club['description']) ?></p>
            </div>

            <!-- Club Info -->
            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">About This Club</h2>
                <div class="prose dark:prose-invert max-w-none">
                    <?= markdown($club['about'] ?? $club['description']) ?>
                </div>
            </div>

            <!-- Activities -->
            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">Our Activities</h2>
                <ul class="space-y-3">
                    <?php foreach ($club['activities'] ?? [] as $activity): ?>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#2C3E7C] dark:text-blue-400 mr-3 flex-shrink-0 mt-0.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-600 dark:text-gray-400"><?= htmlspecialchars($activity) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="card p-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">Get Involved</h2>

                <?php if ($formUrl = getFormUrl('club', $club['slug'])): ?>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Registration for <?= htmlspecialchars($club['name']) ?> is currently open! Fill out the form to
                        join.
                    </p>
                    <a href="<?= $formUrl ?>"
                        class="inline-block px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-bold hover:shadow-lg transition-all mb-8">
                        Join This Club
                    </a>
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-6 mt-2">
                    <?php else: ?>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Interested in joining <?= htmlspecialchars($club['name']) ?>? Contact us through the OSIS office
                            or reach out to our club coordinators during school hours.
                        </p>
                    <?php endif; ?>

                    <a href="/clubs"
                        class="inline-block px-8 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-md font-medium text-sm hover:bg-gray-200 transition-all border border-gray-200 dark:border-gray-700">
                        Back to All Clubs
                    </a>

                    <?php if (isset($formUrl)): ?>
                    </div><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>