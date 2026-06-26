<?php
$title = 'Events - OSIS SMAN 1 Bantul';
$events = json_decode(file_get_contents(BASE_PATH . '/data/events.json'), true);
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-center mb-4 text-gray-900 dark:text-white">School
            Events</h1>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto text-lg">
            Discover exciting events and activities organized by OSIS SMAN 1 Bantul
        </p>

        <!-- Search Bar -->
        <div class="max-w-2xl mx-auto mb-16">
            <div class="relative">
                <input type="text" placeholder="Search events..."
                    class="w-full px-6 py-4 pl-14 rounded-xl border-2 border-gray-200 dark:border-gray-700 focus:border-[#2C3E7C] dark:focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <svg class="w-6 h-6 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <?php foreach ($events as $event): ?>
                <a href="/event/<?= $event['slug'] ?>"
                    class="card overflow-hidden group hover:shadow-2xl transition-all duration-300 cursor-pointer">
                    <div class="aspect-video overflow-hidden bg-gray-200 dark:bg-gray-800">
                        <img src="<?= asset('assets/images/' . $event['image']) ?>"
                            alt="<?= htmlspecialchars($event['title']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            <?= htmlspecialchars($event['title']) ?></h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            <?= htmlspecialchars($event['description']) ?></p>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span><?= date('M j, Y', strtotime($event['date'])) ?></span>
                            </div>
                            <span
                                class="badge <?= $event['status'] === 'upcoming' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' ?>">
                                <?= ucfirst($event['status']) ?>
                            </span>
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