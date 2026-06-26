<?php
require_once BASE_PATH . '/src/Markdown.php';
$title = htmlspecialchars($community['name']) . ' - OSIS SMAN 1 Bantul';
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Community Header -->
            <div class="text-center mb-12">
                <div class="w-32 h-32 mx-auto mb-6">
                    <img src="<?= asset('assets/images/' . $community['logo']) ?>" class="w-full h-full object-contain"
                        alt="<?= $community['name'] ?>"
                        onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'">
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900 dark:text-white">
                    <?= htmlspecialchars($community['name']) ?>
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    <?= htmlspecialchars($community['description']) ?>
                </p>
            </div>

            <!-- Community Info -->
            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">About This Community</h2>
                <div class="prose dark:prose-invert max-w-none">
                    <?= markdown($community['about'] ?? $community['description']) ?>
                </div>
            </div>

            <!-- Activities -->
            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">Our Activities</h2>
                <ul class="space-y-3">
                    <?php foreach ($community['activities'] ?? [] as $activity): ?>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-[#2C3E7C] dark:text-blue-400 mr-3 flex-shrink-0 mt-0.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= htmlspecialchars($activity) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="card p-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">Get Involved</h2>

                <?php if ($formUrl = getFormUrl('community', $community['slug'])): ?>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Registration for
                        <?= htmlspecialchars($community['name']) ?> is currently open! Fill out the form to
                        join.
                    </p>
                    <a href="<?= $formUrl ?>"
                        class="inline-block px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-bold hover:shadow-lg transition-all mb-8">
                        Join This Community
                    </a>
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-6 mt-2">
                    <?php else: ?>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Interested in joining
                            <?= htmlspecialchars($community['name']) ?>? Follow our Instagram
                            <a href="https://instagram.com/<?= htmlspecialchars($community['instagram']) ?>" target="_blank"
                                class="text-blue-600 dark:text-blue-400 font-medium hover:underline">@
                                <?= htmlspecialchars($community['instagram']) ?>
                            </a>
                            or reach out to our community coordinators.
                        </p>
                    <?php endif; ?>

                    <a href="/communities"
                        class="inline-block px-8 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-md font-medium text-sm hover:bg-gray-200 transition-all border border-gray-200 dark:border-gray-700">
                        Back to All Communities
                    </a>

                    <?php if (isset($formUrl)): ?>
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