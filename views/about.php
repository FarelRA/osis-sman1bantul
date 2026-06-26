<?php
$title = 'About - OSIS SMAN 1 Bantul';
$about = json_decode(file_get_contents(BASE_PATH . '/data/about.json'), true);
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-center mb-4 text-gray-900 dark:text-white">About OSIS
        </h1>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-16 max-w-2xl mx-auto text-lg">
            Learn more about our organization, mission, and vision
        </p>

        <div class="max-w-4xl mx-auto space-y-12">
            <!-- Mission & Vision -->
            <div class="grid md:grid-cols-2 gap-8">
                <div class="card p-8">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Our Mission</h2>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        <?= htmlspecialchars($about['mission']) ?>
                    </p>
                </div>

                <div class="card p-8">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Our Vision</h2>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        <?= htmlspecialchars($about['vision']) ?>
                    </p>
                </div>
            </div>

            <!-- What We Do -->
            <div class="card p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">What We Do</h2>
                <div class="grid sm:grid-cols-2 gap-6">
                    <?php
                    $icons = [
                        'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                        'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                        'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                    ];
                    $colors = ['blue', 'green', 'purple', 'orange'];
                    foreach ($about['activities'] as $idx => $activity):
                        ?>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-12 h-12 bg-<?= $colors[$idx] ?>-100 dark:bg-<?= $colors[$idx] ?>-900 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-<?= $colors[$idx] ?>-600 dark:text-<?= $colors[$idx] ?>-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="<?= $icons[$idx] ?>" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2">
                                    <?= htmlspecialchars($activity['title']) ?>
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars($activity['description']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contact CTA -->
            <div class="card p-8 bg-gradient-to-br from-[#2C3E7C] to-[#1e2a54] text-white text-center">
                <h2 class="text-2xl font-bold mb-4">Get Involved</h2>
                <p class="text-white/90 mb-6 max-w-2xl mx-auto">
                    Interested in joining OSIS or have questions? We'd love to hear from you!
                </p>
                <a href="/sekbid"
                    class="inline-block px-8 py-3 bg-white text-[#2C3E7C] rounded-lg font-medium hover:bg-gray-100 transition-colors">
                    Meet Our Team
                </a>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>