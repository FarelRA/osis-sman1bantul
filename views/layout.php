<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'OSIS SMAN 1 Bantul' ?></title>
    <link rel="icon" type="image/png" href="<?= asset('assets/images/osis.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
</head>
<?php
require_once BASE_PATH . '/src/Analytics.php';
trackVisit();
$isHomePage = ($_SERVER['REQUEST_URI'] === '/' || strpos($_SERVER['REQUEST_URI'], '/index.php') !== false);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<body class="min-h-screen" x-data="{ scrolled: false, mobileMenuOpen: false }"
    @scroll.window="scrolled = window.pageYOffset > <?= $isHomePage ? '500' : '50' ?>">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        :class="scrolled || mobileMenuOpen ? 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-lg' : 'bg-transparent'">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <a href="/" class="flex items-center gap-3 group">
                    <img src="<?= asset('assets/images/osis.png') ?>"
                        class="h-12 w-12 transition-transform duration-300 group-hover:scale-110" alt="OSIS Logo">
                    <div class="flex flex-col">
                        <span class="font-bold text-lg transition-colors duration-300 leading-tight"
                            :class="scrolled ? 'text-gray-900 dark:text-white' : 'text-white'">OSIS</span>
                        <span class="text-xs transition-colors duration-300"
                            :class="scrolled ? 'text-gray-600 dark:text-gray-400' : 'text-white/80'">SMAN 1
                            Bantul</span>
                    </div>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="/" class="transition-colors duration-300 font-medium text-sm"
                        :class="scrolled ? '<?= $currentPath === '/' ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' ?>' : '<?= $currentPath === '/' ? 'text-white' : 'text-white/60 hover:text-white/80' ?>'">Home</a>
                    <a href="/sekbid" class="transition-colors duration-300 font-medium text-sm"
                        :class="scrolled ? '<?= $currentPath === '/sekbid' ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' ?>' : '<?= $currentPath === '/sekbid' ? 'text-white' : 'text-white/60 hover:text-white/80' ?>'">Council</a>
                    <a href="/events" class="transition-colors duration-300 font-medium text-sm"
                        :class="scrolled ? '<?= $currentPath === '/events' ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' ?>' : '<?= $currentPath === '/events' ? 'text-white' : 'text-white/60 hover:text-white/80' ?>'">Events</a>
                    <a href="/clubs" class="transition-colors duration-300 font-medium text-sm"
                        :class="scrolled ? '<?= $currentPath === '/clubs' ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' ?>' : '<?= $currentPath === '/clubs' ? 'text-white' : 'text-white/60 hover:text-white/80' ?>'">Clubs</a>
                    <a href="/communities" class="transition-colors duration-300 font-medium text-sm"
                        :class="scrolled ? '<?= $currentPath === '/communities' ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' ?>' : '<?= $currentPath === '/communities' ? 'text-white' : 'text-white/60 hover:text-white/80' ?>'">Communities</a>
                    <a href="/blogs" class="transition-colors duration-300 font-medium text-sm"
                        :class="scrolled ? '<?= $currentPath === '/blogs' ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' ?>' : '<?= $currentPath === '/blogs' ? 'text-white' : 'text-white/60 hover:text-white/80' ?>'">Blogs</a>
                    <a href="/about" class="transition-colors duration-300 font-medium text-sm"
                        :class="scrolled ? '<?= $currentPath === '/about' ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' ?>' : '<?= $currentPath === '/about' ? 'text-white' : 'text-white/60 hover:text-white/80' ?>'">About</a>
                    <a href="/contact"
                        class="px-5 py-2.5 rounded-lg font-medium text-sm transition-all duration-300 border"
                        :class="scrolled ? 'bg-[#2C3E7C] text-white hover:bg-[#1e2a54] border-[#2C3E7C]' : 'bg-white/20 text-white hover:bg-white/30 backdrop-blur-sm border-white/30'">Contact</a>
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-lg transition-colors duration-300"
                    :class="scrolled ? 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-900 dark:text-white' : 'hover:bg-white/10 text-white'">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-transition
            class="md:hidden bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-lg border-t border-gray-200 dark:border-gray-800">
            <div class="container mx-auto px-4 py-4 space-y-2">
                <a href="/"
                    class="block px-4 py-3 rounded-lg font-medium <?= $currentPath === '/' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50' ?>">Home</a>
                <a href="/sekbid"
                    class="block px-4 py-3 rounded-lg font-medium <?= $currentPath === '/sekbid' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50' ?>">Council</a>
                <a href="/events"
                    class="block px-4 py-3 rounded-lg font-medium <?= $currentPath === '/events' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50' ?>">Events</a>
                <a href="/clubs"
                    class="block px-4 py-3 rounded-lg font-medium <?= $currentPath === '/clubs' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50' ?>">Clubs</a>
                <a href="/communities"
                    class="block px-4 py-3 rounded-lg font-medium <?= $currentPath === '/communities' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50' ?>">Communities</a>
                <a href="/blogs"
                    class="block px-4 py-3 rounded-lg font-medium <?= $currentPath === '/blogs' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50' ?>">Blogs</a>
                <a href="/about"
                    class="block px-4 py-3 rounded-lg font-medium <?= $currentPath === '/about' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50' ?>">About</a>
                <a href="/contact"
                    class="block px-4 py-3 rounded-lg bg-[#2C3E7C] text-white font-medium text-center">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 py-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="<?= asset('assets/images/osis.png') ?>" class="h-16 w-16" alt="OSIS Logo">
                        <div>
                            <h3 class="font-bold text-2xl text-gray-900 dark:text-white">OSIS SMAN 1 Bantul</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Student Council</p>
                        </div>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
                        Empowering student voices and fostering leadership through diverse activities and programs at
                        SMAN 1 Bantul.
                    </p>
                    <div class="flex gap-4">
                        <a href="#"
                            class="w-10 h-10 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="w-10 h-10 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="w-10 h-10 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                            </svg>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4 text-gray-900 dark:text-white">Quick Links</h4>
                    <div class="space-y-3">
                        <a href="/"
                            class="block text-gray-600 dark:text-gray-400 hover:text-[#2C3E7C] dark:hover:text-blue-400 transition-colors">Home</a>
                        <a href="/sekbid"
                            class="block text-gray-600 dark:text-gray-400 hover:text-[#2C3E7C] dark:hover:text-blue-400 transition-colors">Council</a>
                        <a href="/events"
                            class="block text-gray-600 dark:text-gray-400 hover:text-[#2C3E7C] dark:hover:text-blue-400 transition-colors">Events</a>
                        <a href="/clubs"
                            class="block text-gray-600 dark:text-gray-400 hover:text-[#2C3E7C] dark:hover:text-blue-400 transition-colors">Clubs</a>
                        <a href="/communities"
                            class="block text-gray-600 dark:text-gray-400 hover:text-[#2C3E7C] dark:hover:text-blue-400 transition-colors">Communities</a>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4 text-gray-900 dark:text-white">Contact</h4>
                    <div class="space-y-3 text-gray-600 dark:text-gray-400">
                        <p class="flex items-start gap-2">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>SMAN 1 Bantul<br />Jl. KH. Wahid Hasyim<br />Bantul, Yogyakarta</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            osis@sman1bantul.sch.id
                        </p>
                    </div>
                </div>
            </div>
            <div
                class="border-t border-gray-200 dark:border-gray-800 pt-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                <p>&copy; <?= date('Y') ?> OSIS SMAN 1 Bantul Student Council. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>