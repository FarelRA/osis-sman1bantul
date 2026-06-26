<?php
/**
 * Form Full / Quota Reached Page
 * Premium design with helpful feedback and alternatives
 * 
 * Expected variables:
 * - $title: Page title
 * - $heading: Main heading text
 * - $message: Description message
 * - $buttonText: Primary button text
 * - $buttonLink: Primary button URL
 * - $form (optional): Form data for additional context
 * - $quota (optional): Total quota number
 * - $currentCount (optional): Current registration count
 */

$title = $title ?? 'Registration Closed';
$heading = $heading ?? 'Quota Reached';
$message = $message ?? 'Sorry, the quota for this form has been reached. Please check back later or contact the admin for more information.';
$buttonText = $buttonText ?? 'Back to Home';
$buttonLink = $buttonLink ?? '/';

// Extract additional context if available
$formTitle = $form['title'] ?? null;
$quota = $quota ?? ($form['quota'] ?? null);
$contactEmail = 'osis@sman1bantul.sch.id';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - OSIS SMAN 1 Bantul</title>
    <link rel="icon" type="image/png" href="/public/assets/images/osis.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        /* Animated gradient background */
        .bg-animated {
            background: linear-gradient(-45deg, #1e3a5f, #2d1b4e, #1a1a2e, #16213e);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Floating animation */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        .float-animation-delay {
            animation: float 8s ease-in-out infinite;
            animation-delay: -2s;
        }

        /* Pulse ring */
        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                transform: scale(1.4);
                opacity: 0;
            }
        }

        .pulse-ring::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 9999px;
            border: 2px solid currentColor;
            animation: pulse-ring 2s ease-out infinite;
        }

        /* Entrance animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animation-delay-100 {
            animation-delay: 0.1s;
            opacity: 0;
        }

        .animation-delay-200 {
            animation-delay: 0.2s;
            opacity: 0;
        }

        .animation-delay-300 {
            animation-delay: 0.3s;
            opacity: 0;
        }

        .animation-delay-400 {
            animation-delay: 0.4s;
            opacity: 0;
        }

        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Button shine effect */
        .btn-shine {
            position: relative;
            overflow: hidden;
        }

        .btn-shine::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-shine:hover::before {
            left: 100%;
        }
    </style>
</head>

<body class="bg-animated min-h-screen font-sans antialiased">
    <!-- Decorative Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-rose-500/10 rounded-full blur-3xl float-animation"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl float-animation-delay">
        </div>
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-500/5 rounded-full blur-3xl">
        </div>
    </div>

    <!-- Main Content -->
    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-lg w-full">
            <!-- Card -->
            <div class="glass rounded-3xl p-8 sm:p-10 shadow-2xl animate-fade-in-up">
                <!-- Icon -->
                <div class="flex justify-center mb-8 animate-fade-in-up animation-delay-100">
                    <div class="relative pulse-ring text-rose-400">
                        <div
                            class="w-24 h-24 rounded-full bg-gradient-to-br from-rose-500/20 to-amber-500/20 flex items-center justify-center border border-rose-500/30">
                            <svg class="w-12 h-12 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Heading -->
                <div class="text-center mb-6 animate-fade-in-up animation-delay-200">
                    <h1 class="text-3xl sm:text-4xl font-bold text-white mb-3">
                        <?= htmlspecialchars($heading) ?>
                    </h1>
                    <?php if ($formTitle): ?>
                        <p class="text-rose-300/80 text-sm font-medium">
                            <?= htmlspecialchars($formTitle) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Message -->
                <div class="text-center mb-8 animate-fade-in-up animation-delay-300">
                    <p class="text-slate-300 leading-relaxed">
                        <?= htmlspecialchars($message) ?>
                    </p>
                </div>

                <!-- Stats Card (if quota info available) -->
                <?php if ($quota): ?>
                    <div
                        class="bg-white/5 rounded-2xl p-5 mb-8 border border-white/10 animate-fade-in-up animation-delay-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 uppercase tracking-wider">Registration Quota</p>
                                    <p class="text-white font-semibold">Fully Booked</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-rose-400"><?= number_format($quota) ?></p>
                                <p class="text-xs text-slate-400">spots filled</p>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="mt-4">
                            <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-rose-500 to-amber-500 rounded-full"
                                    style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="space-y-4 animate-fade-in-up animation-delay-400">
                    <!-- Primary Button -->
                    <a href="<?= htmlspecialchars($buttonLink) ?>" class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 
                               hover:from-blue-500 hover:to-cyan-500 text-white font-semibold rounded-xl 
                               transition-all duration-300 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40
                               transform hover:-translate-y-0.5 btn-shine">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <?= htmlspecialchars($buttonText) ?>
                    </a>

                    <!-- Secondary Actions -->
                    <div class="flex items-center gap-3">
                        <a href="/events" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-white/5 hover:bg-white/10 
                                   border border-white/10 hover:border-white/20 text-white font-medium rounded-xl 
                                   transition-all duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm">Other Events</span>
                        </a>
                        <a href="/contact" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-white/5 hover:bg-white/10 
                                   border border-white/10 hover:border-white/20 text-white font-medium rounded-xl 
                                   transition-all duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm">Contact Us</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Text -->
            <div class="mt-6 text-center animate-fade-in-up animation-delay-400">
                <p class="text-slate-400 text-sm">
                    Need assistance? Email us at
                    <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"
                        class="text-blue-400 hover:text-blue-300 underline underline-offset-2 transition-colors">
                        <?= htmlspecialchars($contactEmail) ?>
                    </a>
                </p>
            </div>

            <!-- Logo -->
            <div class="mt-8 flex justify-center animate-fade-in-up animation-delay-400">
                <a href="/" class="flex items-center gap-3 opacity-60 hover:opacity-100 transition-opacity">
                    <img src="/public/assets/images/osis.png" alt="OSIS" class="h-8 w-8">
                    <span class="text-white/80 text-sm font-medium">OSIS SMAN 1 Bantul</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto dark mode
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>

</html>