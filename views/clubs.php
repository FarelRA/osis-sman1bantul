<?php
$title = 'Clubs - OSIS SMAN 1 Bantul';
$clubs = json_decode(file_get_contents(BASE_PATH . '/data/clubs.json'), true);
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-center mb-4 text-gray-900 dark:text-white">Explore
            Our Clubs</h1>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto text-lg">
            Connect with clubs that match your interests
        </p>

        <!-- Search Bar -->
        <div class="max-w-2xl mx-auto mb-16">
            <div class="relative">
                <input type="text" placeholder="Search clubs..."
                    class="w-full px-6 py-4 pl-14 rounded-xl border-2 border-gray-200 dark:border-gray-700 focus:border-[#2C3E7C] dark:focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <svg class="w-6 h-6 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Clubs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <?php
            $categories = ['Technology & Academics', 'Arts & Culture', 'Sports & Wellness'];
            $colors = [
                'Technology & Academics' => 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
                'Arts & Culture' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300',
                'Sports & Wellness' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300'
            ];
            foreach ($clubs as $index => $org):
                $category = $categories[$index % 3];
                ?>
                <a href="/club/<?= $org['slug'] ?>"
                    class="card overflow-hidden group hover:shadow-2xl transition-all duration-300 cursor-pointer">
                    <div class="aspect-video overflow-hidden bg-gray-200 dark:bg-gray-800">
                        <img src="<?= asset('assets/images/' . $org['logo']) ?>" alt="<?= htmlspecialchars($org['name']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            <?= htmlspecialchars($org['name']) ?></h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            <?= $org['description'] ?? 'Join us to explore your passion and develop new skills in a supportive community.' ?>
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="badge <?= $colors[$category] ?>"><?= $category ?></span>
                            <span class="text-gray-600 dark:text-gray-400"><?= rand(15, 50) ?>+ members</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>