<?php
/**
 * Registration Layout
 * Dynamic wrapper using form configuration
 */

// Extract variables
$form = $form ?? [];
$registration = $registration ?? [];
$stepConfig = $stepConfig ?? [];
$currentStep = $currentStep ?? 0;
$totalSteps = $totalSteps ?? 1;
$remainingTime = $remainingTime ?? null;
$settings = $settings ?? [];
$csrfToken = $csrfToken ?? '';
$formSlug = $formSlug ?? '';
$basePath = $basePath ?? '/register/' . $formSlug; // Support for attached forms

$formTitle = $form['title'] ?? 'Registration';
$stepTitle = $stepConfig['step']['title'] ?? $stepConfig['title'] ?? 'Step ' . ($currentStep + 1);
$progress = min(100, (($currentStep + 1) / $totalSteps) * 100);

$regData = $registration['data'] ?? [];

// Step labels for progress
$stepLabels = ['Info', 'Identity', 'Contact', 'Document', 'Payment', 'Complete'];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($stepTitle) ?> - <?= htmlspecialchars($formTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script>
        // Use device theme, default to dark
        if (!window.matchMedia || window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Premium Design System */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --accent-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.3);
            --shadow-soft: 0 4px 30px rgba(0, 0, 0, 0.08);
            --shadow-glow: 0 0 40px rgba(102, 126, 234, 0.3);
        }

        .dark {
            --glass-bg: rgba(30, 41, 59, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        /* Animated Background */
        .bg-animated {
            background: linear-gradient(-45deg, #667eea, #764ba2, #6B8DD6, #8E54E9);
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

        /* Glass morphism */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }

        /* Progress animations */
        .progress-pulse {
            animation: progressPulse 2s ease-in-out infinite;
        }

        @keyframes progressPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(99, 102, 241, 0);
            }
        }

        /* AI Scanning effects */
        .ai-glow-container {
            position: relative;
            padding: 4px;
            border-radius: 1rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899, #3b82f6);
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
        }

        .ai-scanning-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
            animation: scan 2s ease-in-out infinite;
        }

        @keyframes scan {
            0% {
                top: 0;
                opacity: 0;
            }

            50% {
                opacity: 1;
            }

            100% {
                top: 100%;
                opacity: 0;
            }
        }

        /* Typing animation */
        .typing-dots::after {
            content: '';
            animation: typingDots 1.5s infinite;
        }

        @keyframes typingDots {

            0%,
            20% {
                content: '.';
            }

            40% {
                content: '..';
            }

            60%,
            100% {
                content: '...';
            }
        }

        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3), 0 0 20px rgba(99, 102, 241, 0.15);
        }

        /* Button hover effects */
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

        /* Card hover lift */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }

        /* Timer ring animation */
        .timer-ring {
            animation: timerPulse 1s ease-in-out infinite;
        }

        @keyframes timerPulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.5);
        }

        /* Step connector animation */
        .step-connector-animated {
            position: relative;
            overflow: hidden;
        }

        .step-connector-animated::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.5), transparent);
            animation: connectorFlow 2s ease-in-out infinite;
        }

        @keyframes connectorFlow {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Entrance animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .mobile-compact-header {
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
        }
    </style>
</head>

<body class="bg-slate-50 dark:bg-slate-900 min-h-screen font-sans antialiased" x-data="registrationApp()"
    x-init="init()">

    <!-- Decorative Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div
            class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-indigo-400/20 to-purple-400/20 rounded-full blur-3xl">
        </div>
        <div
            class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-br from-blue-400/20 to-cyan-400/20 rounded-full blur-3xl">
        </div>
    </div>

    <!-- Header -->
    <header class="glass sticky top-0 z-50 border-b border-slate-200/50 dark:border-slate-700/50">
        <div class="max-w-4xl mx-auto px-4 py-4 sm:py-5 mobile-compact-header">
            <div class="flex items-center justify-between gap-4">
                <!-- Title Section -->
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white truncate">
                        <?= htmlspecialchars($formTitle) ?>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 truncate mt-0.5">
                        <?= htmlspecialchars($stepTitle) ?>
                    </p>
                </div>

                <!-- Timer -->
                <?php if ($remainingTime !== null && $remainingTime > 0 && $currentStep > 0): ?>
                    <div class="flex-shrink-0 text-right">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800">
                            <div class="relative">
                                <svg class="w-5 h-5 text-indigo-500 timer-ring" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div
                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-medium hidden sm:block">
                                    Time Left</div>
                                <div class="text-sm sm:text-base font-mono font-bold text-indigo-700 dark:text-indigo-300"
                                    x-text="formatTime(remainingTime)">
                                    <?= sprintf('%02d:%02d', floor($remainingTime / 60), $remainingTime % 60) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Progress Bar -->
            <?php if ($currentStep < $totalSteps): ?>
                <div class="mt-4 sm:mt-6">
                    <!-- Desktop Progress -->
                    <div class="hidden sm:block">
                        <div class="flex items-center justify-between">
                            <?php for ($i = 0; $i < $totalSteps; $i++): ?>
                                <div class="flex items-center <?= $i < $totalSteps - 1 ? 'flex-1' : '' ?>">
                                    <div class="flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300
                                            <?php if ($i < $currentStep): ?>
                                                bg-gradient-to-br from-green-400 to-emerald-500 text-white shadow-lg shadow-green-500/30
                                            <?php elseif ($i === $currentStep): ?>
                                                bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/40 progress-pulse
                                            <?php else: ?>
                                                bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500
                                            <?php endif; ?>">
                                            <?php if ($i < $currentStep): ?>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            <?php else: ?>
                                                <?= $i + 1 ?>
                                            <?php endif; ?>
                                        </div>
                                        <span
                                            class="mt-2 text-xs font-medium <?= $i <= $currentStep ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 dark:text-slate-500' ?>">
                                            <?= $stepLabels[$i] ?? 'Step ' . ($i + 1) ?>
                                        </span>
                                    </div>
                                    <?php if ($i < $totalSteps - 1): ?>
                                        <div class="flex-1 mx-3 mb-6">
                                            <div
                                                class="h-1 rounded-full overflow-hidden <?= $i < $currentStep ? 'bg-gradient-to-r from-green-400 to-emerald-500' : 'bg-slate-200 dark:bg-slate-700' ?>">
                                                <?php if ($i === $currentStep - 1): ?>
                                                    <div class="step-connector-animated h-full"></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Mobile Progress (Dots) -->
                    <div class="sm:hidden">
                        <div class="flex items-center justify-center gap-2">
                            <?php for ($i = 0; $i < $totalSteps; $i++): ?>
                                <div class="transition-all duration-300 
                                    <?php if ($i < $currentStep): ?>
                                        w-2 h-2 rounded-full bg-gradient-to-r from-green-400 to-emerald-500
                                    <?php elseif ($i === $currentStep): ?>
                                        w-8 h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600
                                    <?php else: ?>
                                        w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600
                                    <?php endif; ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                        <p class="text-center mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Step <?= $currentStep + 1 ?> of <?= $totalSteps ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 max-w-4xl mx-auto px-4 py-6 sm:py-10 animate-fade-in-up">
        <?php if (isset($_SESSION['flash_errors']) && !empty($_SESSION['flash_errors']['general'])): ?>
            <div class="mb-6 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 
                        border border-red-200 dark:border-red-800 rounded-2xl p-4 shadow-lg shadow-red-500/10">
                <div class="flex items-start gap-3">
                    <div
                        class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-100 dark:bg-red-800 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-red-800 dark:text-red-200">Error</h3>
                        <p class="text-sm text-red-700 dark:text-red-300">
                            <?= htmlspecialchars($_SESSION['flash_errors']['general']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php
            unset($_SESSION['flash_errors']['general']);
        endif;
        ?>

        <?php echo $stepContent ?? ''; ?>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 max-w-4xl mx-auto px-4 py-8 sm:py-10">
        <div class="text-center">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/50 dark:bg-slate-800/50 backdrop-blur border border-slate-200 dark:border-slate-700">
                <span class="text-xs text-slate-500 dark:text-slate-400">ID:</span>
                <span
                    class="font-mono text-sm font-semibold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($registration['id'] ?? 'N/A') ?></span>
            </div>
            <p class="mt-3 text-xs text-slate-400 dark:text-slate-500 flex items-center justify-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Progress saved automatically
            </p>
        </div>
    </footer>

    <script>
        function registrationApp() {
            return {
                remainingTime: <?= $remainingTime ?? 'null' ?>,
                formSlug: '<?= htmlspecialchars($formSlug) ?>',

                init() {
                    if (this.remainingTime !== null && !this._timerStarted) {
                        this._timerStarted = true;
                        setInterval(() => {
                            if (this.remainingTime > 0) {
                                this.remainingTime--;
                            } else {
                                window.location.reload();
                            }
                        }, 1000);
                    }

                    this.setupAutoSave();
                    this.setupDarkMode();
                },

                formatTime(seconds) {
                    if (seconds === null) return '--:--';
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                },

                setupDarkMode() {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                        document.documentElement.classList.toggle('dark', e.matches);
                    });
                },

                setupAutoSave() {
                    const form = document.querySelector('form[data-autosave]');
                    if (!form) return;

                    const debouncedSave = this.debounce(() => {
                        this.autoSave(form);
                    }, 1500);

                    form.querySelectorAll('input, select, textarea').forEach(el => {
                        el.addEventListener('input', debouncedSave);
                        el.addEventListener('change', debouncedSave);
                    });
                },

                async autoSave(form) {
                    const formData = new FormData(form);
                    formData.append('form_id', this.formSlug);

                    try {
                        await fetch('/api/registration/save', {
                            method: 'POST',
                            body: formData
                        });
                        // Auto-save does NOT reset timer - just silently saves data
                    } catch (e) {
                        console.error('Auto-save failed:', e);
                    }
                },

                debounce(fn, delay) {
                    let timer;
                    return (...args) => {
                        clearTimeout(timer);
                        timer = setTimeout(() => fn.apply(this, args), delay);
                    };
                }
            };
        }
    </script>
</body>

</html>