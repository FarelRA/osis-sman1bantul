<?php
$title = '404 - Page Not Found';
ob_start();
?>

<section class="py-32">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto text-center">
            <div class="mb-8">
                <div
                    class="text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#2C3E7C] to-[#1e2a54] dark:from-blue-400 dark:to-cyan-400">
                    404
                </div>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900 dark:text-white">
                Page Not Found
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-400 mb-10">
                Sorry, the page you're looking for doesn't exist or has been moved.
            </p>
            <div class="flex gap-4 justify-center flex-wrap">
                <a href="/"
                    class="px-8 py-4 bg-gradient-to-r from-[#2C3E7C] to-[#1e2a54] text-white rounded-lg font-semibold hover:shadow-lg transition-all">
                    Back to Home
                </a>
                <a href="/events"
                    class="px-8 py-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-2 border-gray-200 dark:border-gray-700 rounded-lg font-semibold hover:border-[#2C3E7C] dark:hover:border-blue-400 transition-all">
                    View Events
                </a>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>