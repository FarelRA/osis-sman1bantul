<?php
$title = 'Contact Us - OSIS SMAN 1 Bantul';
ob_start();
?>

<section class="py-20">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-center mb-4 text-gray-900 dark:text-white">Get in
            Touch
        </h1>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-16 max-w-2xl mx-auto text-lg">
            Have questions or suggestions? We'd love to hear from you.
        </p>

        <div class="max-w-4xl mx-auto space-y-12">
            <!-- Contact Info Cards -->
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Address Card -->
                <div class="card p-8 text-center hover:shadow-lg transition-shadow">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Visit Us</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                        SMAN 1 Bantul<br>
                        Jl. KH. Wahid Hasyim<br>
                        Bantul, Yogyakarta
                    </p>
                </div>

                <!-- Email Card -->
                <div class="card p-8 text-center hover:shadow-lg transition-shadow">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Email Us</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                        General inquiries
                    </p>
                    <a href="mailto:osis@sman1bantul.sch.id"
                        class="text-blue-600 dark:text-blue-400 text-sm font-medium hover:underline">
                        osis@sman1bantul.sch.id
                    </a>
                </div>

                <!-- Social Card -->
                <div class="card p-8 text-center hover:shadow-lg transition-shadow">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-orange-400 to-red-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2">Follow Us</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                        @osis.sman1bantul
                    </p>
                    <a href="https://instagram.com/osis.sman1bantul" target="_blank"
                        class="inline-flex items-center gap-1 text-pink-600 font-medium text-sm hover:underline">
                        Instagram &rarr;
                    </a>
                </div>
            </div>

            <!-- Contact Form Card -->
            <div class="card p-8 md:p-12">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Send us a message</h2>
                    <p class="text-gray-600 dark:text-gray-400">We'll get back to you as soon as possible.</p>
                </div>

                <form class="space-y-6 max-w-2xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                            <input type="text"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                                placeholder="Your name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                                placeholder="your@email.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                        <input type="text"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                            placeholder="What is this about?">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                        <textarea rows="5"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                            placeholder="Tell us more..."></textarea>
                    </div>
                    <button type="submit"
                        class="w-full py-4 bg-[#2C3E7C] hover:bg-[#1e2a54] text-white font-bold rounded-xl transition-colors shadow-lg shadow-blue-900/20">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>