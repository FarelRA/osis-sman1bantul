<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin - OSIS' ?></title>
    <link rel="icon" type="image/png" href="<?= asset('assets/images/osis.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen">
        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden fixed top-4 left-4 z-50 p-2 bg-gray-900 text-white rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="lg:hidden fixed inset-0 bg-black/50 z-30"
            x-transition></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed w-64 bg-gray-900 dark:bg-black text-white h-screen overflow-y-auto transition-transform duration-300 z-40 lg:translate-x-0">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </aside>

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            <div class="p-4 sm:p-6 lg:p-8 pt-16 lg:pt-8">
                <?= $content ?>
            </div>
        </main>
    </div>
</body>

</html>
