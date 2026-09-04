<?php
require_once BASE_PATH . '/src/Markdown.php';
$title = htmlspecialchars($community['name']) . ' - OSIS SMAN 1 Bantul';
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Community Header -->

            <div class="mb-8 rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 aspect-video">
                <img src="<?= asset('assets/images/' . $community['image']) ?>" class="w-full h-full object-cover"
                    alt="<?= htmlspecialchars($community['name']) ?>"
                    onerror="this.src='<?= asset('assets/images/placeholder.webp') ?>'">
            </div>

            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900 dark:text-white">
                    <?= htmlspecialchars($community['name']) ?>
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400"><?= htmlspecialchars($community['description']) ?></p>
                <?php if (!empty($community['category']) || !empty($community['members'])): ?>
                    <div class="flex items-center justify-center gap-4 mt-4">
                        <?php if (!empty($community['category'])): ?>
                            <?php
                            $catColors = [
                                'Technology & Academics' => 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
                                'Arts & Culture' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300',
                                'Sports & Wellness' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300',
                                'Science & Research' => 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300',
                                'Social & Environment' => 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300',
                                'Media & Journalism' => 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300',
                                'Music & Performance' => 'bg-pink-100 dark:bg-pink-900 text-pink-700 dark:text-pink-300',
                                'Leadership & Advocacy' => 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300',
                            ];
                            $catColor = $catColors[$community['category']] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                            ?>
                            <span class="badge <?= $catColor ?>"><?= htmlspecialchars($community['category']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($community['members'])): ?>
                            <span class="text-sm text-gray-500 dark:text-gray-400"><?= (int) $community['members'] ?>+ members</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- About -->
            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">About This Community</h2>
                <div class="prose dark:prose-invert max-w-none">
                    <?= markdown($community['about'] ?? $community['description']) ?>
                </div>
            </div>

            <!-- Blog Content -->
            <?php if (!empty($community['content'])): ?>
                <div class="card p-8 mb-8">
                    <article class="prose dark:prose-invert max-w-none">
                        <?= markdown($community['content']) ?>
                    </article>
                </div>
            <?php endif; ?>

            <!-- Activities -->
            <?php if (!empty($community['activities'])): ?>
                <div class="card p-8 mb-8">
                    <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">Our Activities</h2>
                    <ul class="space-y-3">
                        <?php foreach ($community['activities'] as $activity): ?>
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
            <?php endif; ?>

            <!-- Get Involved -->
            <div class="card p-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-white">Get Involved</h2>

                <?php if ($formUrl = getFormUrl('community', $community['slug'])): ?>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Registration for <?= htmlspecialchars($community['name']) ?> is currently open! Fill out the form to
                        join.
                    </p>
                    <a href="<?= $formUrl ?>"
                        class="inline-block px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-bold hover:shadow-lg transition-all mb-8">
                        Join This Community
                    </a>
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-6 mt-2">
                    <?php else: ?>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Interested in joining <?= htmlspecialchars($community['name']) ?>? Contact us through the OSIS office
                            or reach out to our community coordinators during school hours.
                        </p>
                    <?php endif; ?>

                    <?php
                    $socials = [];
                    if (!empty($community['instagram'])) $socials[] = ['name' => 'Instagram', 'url' => $community['instagram'], 'color' => 'hover:text-pink-600'];
                    if (!empty($community['youtube'])) $socials[] = ['name' => 'YouTube', 'url' => $community['youtube'], 'color' => 'hover:text-red-600'];
                    if (!empty($community['tiktok'])) $socials[] = ['name' => 'TikTok', 'url' => $community['tiktok'], 'color' => 'hover:text-gray-900 dark:hover:text-white'];
                    if (!empty($socials) || !empty($community['links'])): ?>
                        <div class="border-t border-gray-100 dark:border-gray-800 pt-6 mt-6">
                            <div class="flex flex-wrap items-center gap-4">
                                <?php foreach ($socials as $s): ?>
                                    <a href="<?= htmlspecialchars($s['url']) ?>" target="_blank" rel="noopener"
                                        class="text-gray-400 <?= $s['color'] ?> transition-colors">
                                        <?php if ($s['name'] === 'Instagram'): ?>
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                        <?php elseif ($s['name'] === 'YouTube'): ?>
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                        <?php elseif ($s['name'] === 'TikTok'): ?>
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                                <?php foreach ($community['links'] ?? [] as $link): ?>
                                    <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        <?= htmlspecialchars($link['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
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
