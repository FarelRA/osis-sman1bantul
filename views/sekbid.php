<?php
$title = 'Student Council - OSIS SMAN 1 Bantul';

// Load Data
$leadership = json_decode(file_get_contents(BASE_PATH . '/data/leadership.json'), true);
$sekbid_data = json_decode(file_get_contents(BASE_PATH . '/data/sekbid.json'), true);
$programs = json_decode(file_get_contents(BASE_PATH . '/data/program-kerja.json'), true);

// data enrichment
foreach ($sekbid_data as $id => &$data) {
    if (isset($programs[$id])) {
        $data['programs'] = $programs[$id];
    } else {
        $data['programs'] = [];
    }
    $data['id'] = $id;
}
unset($data); // break reference

// Prepare list for sidebar
$nav_items = [];

// Add Leadership
$nav_items[] = [
    'id' => 'leadership',
    'label' => 'Core Leadership',
    'short' => 'Core',
    'image' => 'sekbid/inti/ketua/ketua_team.jpg'
];

// Add Sekbids
foreach ($sekbid_data as $id => $data) {
    $nav_items[] = [
        'id' => (string) $id,
        'label' => 'Sekbid ' . $id,
        'short' => $id,
        'title' => $data['title'],
        'image' => $data['team_photo']
    ];
}

ob_start();
?>

<div x-data="{ 
    activeTab: 'leadership',
    sidebarOpen: false,
    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}" class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col md:flex-row">

    <!-- Desktop Sidebar (Floating) -->
    <aside
        class="hidden md:flex flex-col w-80 fixed left-6 top-28 bottom-6 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/20 dark:border-gray-700/50 rounded-3xl z-40 shadow-2xl overflow-hidden p-2">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700/50 mb-2">
            <h2 class="text-xs font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-widest pl-2">Council
                Structure</h2>
        </div>

        <nav class="flex-1 overflow-y-auto custom-scrollbar px-2 space-y-2">
            <?php foreach ($nav_items as $item): ?>
                <button @click="activeTab = '<?= $item['id'] ?>'; scrollToTop()"
                    class="w-full text-left p-2 rounded-2xl transition-all duration-300 flex items-center gap-4 group relative overflow-hidden"
                    :class="activeTab === '<?= $item['id'] ?>' ? 'bg-white dark:bg-gray-700 shadow-lg scale-[1.02]' : 'hover:bg-gray-100/50 dark:hover:bg-gray-700/50'">

                    <!-- Active Indicator -->
                    <div x-show="activeTab === '<?= $item['id'] ?>'"
                        class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-[#2C3E7C] dark:bg-blue-400"></div>

                    <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 shadow-sm ring-2 ring-transparent group-hover:ring-gray-200 dark:group-hover:ring-gray-600 transition-all"
                        :class="activeTab === '<?= $item['id'] ?>' ? 'ring-[#2C3E7C] dark:ring-blue-400' : ''">
                        <img src="<?= asset('assets/images/' . $item['image']) ?>" class="w-full h-full object-cover"
                            onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'" alt="Thumbnail">
                    </div>

                    <div class="min-w-0 flex-1 py-1">
                        <div
                            class="font-bold text-sm text-gray-800 dark:text-gray-100 truncate group-hover:text-[#2C3E7C] dark:group-hover:text-blue-300 transition-colors">
                            <?= $item['label'] ?>
                        </div>
                        <?php if (isset($item['title'])): ?>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1 font-medium">
                                <?= htmlspecialchars($item['title']) ?>
                            </div>
                        <?php else: ?>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1 font-medium">Main Management
                            </div>
                        <?php endif; ?>
                    </div>
                </button>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Mobile Bottom Title Bar -->
    <div
        class="md:hidden fixed bottom-6 left-4 right-4 z-40 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md border border-white/20 dark:border-gray-700/50 rounded-2xl px-5 py-4 shadow-lg flex items-center justify-between">
        <div class="flex items-center gap-3 overflow-hidden">
            <div class="w-1.5 h-6 bg-[#2C3E7C] dark:bg-blue-400 rounded-full flex-shrink-0"></div>
            <span class="font-bold text-gray-900 dark:text-white truncate text-base"
                x-text="activeTab === 'leadership' ? 'Core Leadership' : (activeTab === 'leadership' ? '' : 'Sekbid ' + activeTab)"></span>
        </div>
    </div>

    <!-- Mobile Nav Bar (Floating & Square) -->
    <div
        class="md:hidden fixed bottom-24 left-4 right-4 z-50 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/20 dark:border-gray-700/50 rounded-3xl shadow-2xl p-3 overflow-x-auto no-scrollbar flex items-center gap-3 snap-x">
        <?php foreach ($nav_items as $item): ?>
            <button @click="activeTab = '<?= $item['id'] ?>'; scrollToTop()"
                class="snap-center flex-shrink-0 w-20 h-20 rounded-2xl relative overflow-hidden transition-all duration-300 transform"
                :class="activeTab === '<?= $item['id'] ?>' ? 'ring-2 ring-offset-2 ring-[#2C3E7C] scale-105 shadow-lg' : 'opacity-70 hover:opacity-100 hover:scale-105'">

                <img src="<?= asset('assets/images/' . $item['image']) ?>" class="w-full h-full object-cover"
                    onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'" alt="Thumb">

                <div x-show="activeTab === '<?= $item['id'] ?>'"
                    class="absolute inset-0 bg-[#2C3E7C]/20 pointer-events-none"></div>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Main Content -->
    <main class="flex-1 md:ml-96 p-4 md:p-8 pt-28 md:pt-28 pb-64 md:pb-12 min-h-screen">

        <!-- Leadership Content -->
        <div x-show="activeTab === 'leadership'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="max-w-5xl mx-auto">
                <div
                    class="bg-gradient-to-r from-[#2C3E7C] to-[#1e2a54] rounded-3xl p-8 md:p-12 text-white mb-12 relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-32 translate-x-32 blur-3xl">
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-3xl md:text-5xl font-bold mb-4">Core Leadership</h2>
                        <p class="text-blue-100 text-lg max-w-2xl">The driving force behind OSIS SMAN 1 Bantul. Our core
                            team ensures vision becomes reality through strategic planning and effective coordination.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($leadership as $leader): ?>
                        <div
                            class="group bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700">
                            <div class="aspect-[4/5] rounded-xl overflow-hidden mb-4 bg-gray-100 dark:bg-gray-700">
                                <img src="<?= asset('assets/images/' . $leader['photo']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                    onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'"
                                    alt="<?= htmlspecialchars($leader['name']) ?>">
                            </div>
                            <div class="text-center">
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-1 leading-tight">
                                    <?= htmlspecialchars($leader['name']) ?>
                                </h3>
                                <p class="text-[#2C3E7C] dark:text-blue-400 font-medium text-sm mb-3">
                                    <?= htmlspecialchars($leader['position']) ?>
                                </p>
                                <?php if (!empty($leader['instagram'])): ?>
                                    <a href="https://instagram.com/<?= str_replace('@', '', $leader['instagram']) ?>"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-pink-600 transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                        </svg>
                                        @<?= htmlspecialchars($leader['instagram'] ?? 'osis.sman1bantul') ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sekbids Content -->
        <?php foreach ($sekbid_data as $id => $data): ?>
            <div x-show="activeTab === '<?= $id ?>'" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="max-w-5xl mx-auto">
                    <!-- Header -->
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-3xl p-6 md:p-10 shadow-sm border border-gray-100 dark:border-gray-700 mb-8 overflow-hidden">
                        <div
                            class="absolute top-0 right-0 w-96 h-96 bg-blue-50 dark:bg-blue-900/20 rounded-full translate-x-1/3 -translate-y-1/3 blur-3xl">
                        </div>

                        <div class="relative z-10 flex flex-col lg:flex-row gap-8 items-center lg:items-start">
                            <div class="flex-1 text-center lg:text-left">
                                <div
                                    class="inline-block px-4 py-1.5 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-full text-sm font-bold mb-4">
                                    Sekbid <?= $id ?>
                                </div>
                                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
                                    <?= htmlspecialchars($data['title']) ?>
                                </h2>
                                <p class="text-gray-600 dark:text-gray-400 text-lg leading-relaxed">
                                    Dedicated to fostering excellence and innovation in their respective fields, working
                                    together to bring positive change to our school community.
                                </p>
                            </div>
                            <div
                                class="w-full lg:w-1/2 aspect-video bg-gray-200 dark:bg-gray-700 rounded-2xl overflow-hidden shadow-lg transform rotate-1 hover:rotate-0 transition-transform duration-500">
                                <img src="<?= asset('assets/images/' . $data['team_photo']) ?>"
                                    class="w-full h-full object-cover"
                                    onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'"
                                    alt="Team Sekbid <?= $id ?>">
                            </div>
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-3 gap-8">
                        <!-- Members Column -->
                        <div class="lg:col-span-2 space-y-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                <span class="w-2 h-8 bg-blue-500 rounded-full"></span>
                                Team Members
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach ($data['members'] as $member): ?>
                                    <div
                                        class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 flex items-center gap-4 hover:shadow-md transition-shadow">
                                        <img src="<?= asset('assets/images/' . $member['photo']) ?>"
                                            class="w-16 h-16 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700"
                                            onerror="this.src='<?= asset('assets/images/placeholder.jpg') ?>'"
                                            alt="<?= htmlspecialchars($member['name']) ?>">
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-base">
                                                <?= htmlspecialchars($member['name']) ?>
                                            </h4>
                                            <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                                                <?= htmlspecialchars($member['position']) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Programs Column -->
                        <div class="space-y-6">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                <span class="w-2 h-8 bg-purple-500 rounded-full"></span>
                                Work Programs
                            </h3>

                            <div class="space-y-4">
                                <?php if (!empty($data['programs'])): ?>
                                    <?php foreach ($data['programs'] as $role => $program_list): ?>
                                        <div
                                            class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
                                            <div
                                                class="absolute top-0 right-0 w-24 h-24 bg-purple-50 dark:bg-purple-900/20 rounded-full -translate-y-12 translate-x-12 opacity-0 group-hover:opacity-100 transition-opacity">
                                            </div>
                                            <h4
                                                class="font-bold text-gray-900 dark:text-white mb-3 capitalize text-lg pb-2 border-b border-gray-100 dark:border-gray-700">
                                                <?= str_replace(['sie_', '_'], ['Sie ', ' '], $role) ?>
                                            </h4>
                                            <ul class="space-y-2">
                                                <?php foreach ($program_list as $prog): ?>
                                                    <li class="pl-4 relative text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                                        <span class="absolute left-0 top-2 w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                                                        <?= htmlspecialchars($prog) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-6 text-center text-gray-500 bg-gray-100 dark:bg-gray-800 rounded-xl">
                                        No work programs listed yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </main>
</div>

<style>
    /* Hide scrollbar for Chrome, Safari and Opera */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* Hide scrollbar for IE, Edge and Firefox */
    .no-scrollbar {
        -ms-overflow-style: none;
        /* IE and Edge */
        scrollbar-width: none;
        /* Firefox */
    }

    /* Custom Scrollbar for sidebar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 20px;
    }

    [x-cloak] {
        display: none !important;
    }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>