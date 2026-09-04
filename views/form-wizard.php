<?php
/**
 * Form Wizard - Premium Multi-Step Form
 * Dynamic step-based form matching registration flow styling
 */
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - OSIS SMAN 1 Bantul</title>
    <link rel="icon" type="image/png" href="/public/assets/images/osis.webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        if (!window.matchMedia || window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.3);
        }
        .dark {
            --glass-bg: rgba(30, 41, 59, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
        }
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }
        .progress-pulse {
            animation: progressPulse 2s ease-in-out infinite;
        }
        @keyframes progressPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
        }
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
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        @keyframes confetti {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            animation: confetti 3s ease-out forwards;
        }
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-shine:hover::before {
            left: 100%;
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.5); }
    </style>
</head>

<body class="bg-slate-50 dark:bg-slate-900 min-h-screen font-sans antialiased" x-data="formWizard()">

    <!-- Decorative Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-indigo-400/20 to-purple-400/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-br from-blue-400/20 to-cyan-400/20 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <header class="glass sticky top-0 z-50 border-b border-slate-200/50 dark:border-slate-700/50">
        <div class="max-w-4xl mx-auto px-4 py-4 sm:py-5">
            <div class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-3 group">
                    <img src="/public/assets/images/osis.webp" alt="OSIS" class="h-10 w-10 transition-transform group-hover:scale-110">
                    <div>
                        <span class="block text-slate-900 dark:text-white font-bold leading-tight">OSIS</span>
                        <span class="block text-slate-500 dark:text-slate-400 text-xs">SMAN 1 Bantul</span>
                    </div>
                </a>
                <a href="<?= htmlspecialchars($returnUrl ?? '/') ?>"
                   class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 max-w-4xl mx-auto px-4 py-6 sm:py-10 animate-fade-in-up">

        <?php if ($success): ?>
                <!-- Success State -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden p-8 sm:p-12 text-center relative">
                    <div class="absolute inset-0" x-init="createConfetti()">
                        <template x-for="i in 20" :key="i">
                            <div class="confetti-piece"
                                 :style="`left: ${Math.random() * 100}%; background: ${['#6366f1', '#06b6d4', '#8b5cf6', '#ec4899', '#f59e0b'][Math.floor(Math.random() * 5)]}; animation-delay: ${Math.random() * 2}s;`">
                            </div>
                        </template>
                    </div>
                    <div class="relative mb-8">
                        <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-green-500/30">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Submission Complete!</h2>
                    <p class="text-slate-600 dark:text-slate-400 text-lg mb-8 max-w-md mx-auto"><?= htmlspecialchars($success) ?></p>
                    <a href="<?= htmlspecialchars($returnUrl ?? '/') ?>"
                       class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-indigo-600 to-cyan-600
                          hover:from-indigo-500 hover:to-cyan-500 text-white font-semibold rounded-xl
                          transition-all duration-300 shadow-lg shadow-indigo-500/30 hover:shadow-xl
                          transform hover:-translate-y-0.5 btn-shine">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Return
                    </a>
                </div>
        <?php else: ?>
                <!-- Step Indicators (Desktop) -->
                <?php if (count($steps) > 1): ?>
                    <div class="hidden sm:block mb-8">
                        <div class="flex items-center justify-between">
                            <?php foreach ($steps as $index => $stepData):
                                $stepNum = $index + 1;
                                $isLast = $index === count($steps) - 1;
                                ?>
                                    <div class="flex items-center <?= !$isLast ? 'flex-1' : '' ?>">
                                        <div class="flex flex-col items-center">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
                                                 :class="step > <?= $stepNum ?> ? 'bg-gradient-to-br from-green-400 to-emerald-500 text-white shadow-lg shadow-green-500/30' :
                                                     step === <?= $stepNum ?> ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/40 progress-pulse' :
                                                     'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500'">
                                                <template x-if="step > <?= $stepNum ?>">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </template>
                                                <template x-if="step <= <?= $stepNum ?>">
                                                    <span><?= $stepNum ?></span>
                                                </template>
                                            </div>
                                            <span class="mt-2 text-xs font-medium"
                                                  :class="step >= <?= $stepNum ?> ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400 dark:text-slate-500'">
                                                <?= htmlspecialchars($stepData['title'] ?? 'Step ' . $stepNum) ?>
                                            </span>
                                        </div>
                                        <?php if (!$isLast): ?>
                                            <div class="flex-1 mx-3 mb-6">
                                                <div class="h-1 rounded-full overflow-hidden"
                                                     :class="step > <?= $stepNum ?> ? 'bg-gradient-to-r from-green-400 to-emerald-500' : 'bg-slate-200 dark:bg-slate-700'">
                                                    <div x-show="step === <?= $stepNum ?>" class="step-connector-animated h-full"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Step Indicators (Mobile Dots) -->
                <?php if (count($steps) > 1): ?>
                    <div class="sm:hidden mb-6">
                        <div class="flex items-center justify-center gap-2">
                            <?php foreach ($steps as $index => $stepData):
                                $stepNum = $index + 1;
                                ?>
                                    <div class="transition-all duration-300"
                                         :class="step > <?= $stepNum ?> ? 'w-2 h-2 rounded-full bg-gradient-to-r from-green-400 to-emerald-500' :
                                             step === <?= $stepNum ?> ? 'w-8 h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600' :
                                             'w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600'">
                                    </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-center mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Step <span x-text="step"></span> of <span x-text="totalSteps"></span>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Form Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                        <?= htmlspecialchars($title) ?>
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400 text-lg max-w-2xl mx-auto">
                        <?= htmlspecialchars($description ?? '') ?>
                    </p>
                </div>

                <!-- Form Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden card-hover">

                    <!-- Progress Bar -->
                    <?php if (count($steps) > 1): ?>
                        <div class="h-1.5 bg-slate-100 dark:bg-slate-700">
                            <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-500 transition-all duration-500 ease-out"
                                 :style="'width: ' + ((step / totalSteps) * 100) + '%'"></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars($submitUrl) ?>" method="POST" enctype="multipart/form-data"
                        class="relative px-6 sm:px-8 pt-8 pb-6 sm:p-8">
                        <?= CSRF::input() ?>

                        <!-- Steps Loop -->
                        <?php foreach ($steps as $index => $stepData):
                            $stepNum = $index + 1;
                            ?>
                                <div x-show="step === <?= $stepNum ?>" x-ref="step<?= $stepNum ?>"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 transform translate-x-4"
                                     x-transition:enter-end="opacity-100 transform translate-x-0"
                                     class="space-y-6">

                                    <!-- Step Header -->
                                    <div class="relative px-6 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-800 -mx-6 sm:-mx-8 -mt-8 mb-8">
                                        <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-indigo-100/50 to-purple-100/50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                                        <div class="relative">
                                            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">
                                                <?= htmlspecialchars($stepData['title']) ?>
                                            </h2>
                                            <p class="text-slate-600 dark:text-slate-400">
                                                Please fill in all required fields to proceed.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Fields -->
                                    <div class="space-y-6">
                                        <?php foreach ($stepData['fields'] as $field):
                                            $type = $field['type'] ?? 'text';
                                            $name = $field['name'];
                                            $label = $field['label'] ?? ucfirst($name);
                                            $required = !empty($field['required']) ? 'required' : '';
                                            $placeholder = $field['placeholder'] ?? '';
                                            $value = htmlspecialchars($old[$name] ?? '');
                                            $error = $errors[$name] ?? null;
                                            $inputClasses = $error
                                                ? 'border-rose-300 bg-rose-50 dark:bg-rose-900/20'
                                                : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900';
                                            ?>
                                                <div class="group">
                                                    <label for="<?= $name ?>"
                                                           class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                                        <?= htmlspecialchars($label) ?>
                                                        <?php if ($required): ?>
                                                            <span class="text-rose-500">*</span>
                                                        <?php endif; ?>
                                                    </label>

                                                    <?php if ($type === 'textarea'): ?>
                                                            <textarea name="<?= $name ?>" id="<?= $name ?>" rows="3" <?= $required ?>
                                                                class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200 resize-none
                                                                       <?= $inputClasses ?>
                                                                       text-slate-900 dark:text-white placeholder-slate-400
                                                                       focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                                                       hover:border-slate-300 dark:hover:border-slate-500"
                                                                placeholder="<?= htmlspecialchars($placeholder) ?>"><?= $value ?></textarea>

                                                    <?php elseif ($type === 'select'): ?>
                                                            <div class="relative">
                                                                <select name="<?= $name ?>" id="<?= $name ?>" <?= $required ?>
                                                                    class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200 appearance-none cursor-pointer
                                                                           <?= $inputClasses ?>
                                                                           text-slate-900 dark:text-white
                                                                           focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                                                           hover:border-slate-300 dark:hover:border-slate-500">
                                                                    <option value="">Select <?= htmlspecialchars($label) ?></option>
                                                                    <?php foreach (($field['options'] ?? []) as $opt): ?>
                                                                        <option value="<?= htmlspecialchars($opt) ?>" <?= $value === $opt ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($opt) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                    <?php elseif ($type === 'file'): ?>
                                                            <div class="relative">
                                                                <input type="file" name="<?= $name ?>" id="<?= $name ?>" <?= $required ?>
                                                                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                                    class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200 cursor-pointer
                                                                           <?= $inputClasses ?>
                                                                           text-slate-900 dark:text-white
                                                                           file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                                                           file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300
                                                                           hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50
                                                                           focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none">
                                                            </div>
                                                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                                </svg>
                                                                JPG, PNG, PDF, DOC (max 5MB)
                                                            </p>

                                                    <?php elseif ($type === 'tel'): ?>
                                                            <div class="relative group">
                                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                                    </svg>
                                                                </span>
                                                                <input type="tel" name="<?= $name ?>" id="<?= $name ?>"
                                                                    value="<?= $value ?>" <?= $required ?>
                                                                    pattern="^(0|62|\+62)?8[0-9]{8,12}$"
                                                                    title="Enter a valid Indonesian phone number"
                                                                    class="w-full pl-12 pr-4 py-3.5 rounded-xl border-2 transition-all duration-200
                                                                           <?= $inputClasses ?>
                                                                           text-slate-900 dark:text-white placeholder-slate-400
                                                                           focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                                                           hover:border-slate-300 dark:hover:border-slate-500"
                                                                    placeholder="<?= htmlspecialchars($placeholder ?: '812xxxxxxxx') ?>">
                                                            </div>
                                                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                                Format: 08xx or 628xx
                                                            </p>

                                                    <?php elseif ($type === 'email'): ?>
                                                            <div class="relative group">
                                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                                    </svg>
                                                                </span>
                                                                <input type="email" name="<?= $name ?>" id="<?= $name ?>"
                                                                    value="<?= $value ?>" <?= $required ?>
                                                                    class="w-full pl-12 pr-4 py-3.5 rounded-xl border-2 transition-all duration-200
                                                                           <?= $inputClasses ?>
                                                                           text-slate-900 dark:text-white placeholder-slate-400
                                                                           focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                                                           hover:border-slate-300 dark:hover:border-slate-500"
                                                                    placeholder="<?= htmlspecialchars($placeholder) ?>">
                                                            </div>

                                                    <?php else: ?>
                                                            <input type="<?= htmlspecialchars($type) ?>" name="<?= $name ?>" id="<?= $name ?>"
                                                                value="<?= $value ?>" <?= $required ?>
                                                                class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200
                                                                       <?= $inputClasses ?>
                                                                       text-slate-900 dark:text-white placeholder-slate-400
                                                                       focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                                                       hover:border-slate-300 dark:hover:border-slate-500"
                                                                placeholder="<?= htmlspecialchars($placeholder) ?>">
                                                    <?php endif; ?>

                                                    <?php if ($error): ?>
                                                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-400 flex items-center gap-1.5 animate-fade-in-up">
                                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <?= htmlspecialchars($error) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                        <?php endforeach; ?>

                        <!-- Action Footer -->
                        <div class="mt-10 pt-6 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between gap-4">
                            <div>
                                <button type="button" x-show="step > 1" @click="prevStep()"
                                    class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white
                                       font-semibold py-3 px-6 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Back
                                </button>
                            </div>

                            <div class="flex-1 flex justify-end">
                                <button type="button" x-show="step < totalSteps" @click="nextStep()"
                                    class="flex items-center justify-center gap-3 min-w-[160px] bg-indigo-600 hover:bg-indigo-700
                                       text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200
                                       shadow-lg shadow-indigo-500/30 hover:shadow-xl transform hover:-translate-y-0.5 btn-shine">
                                    Next Step
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </button>

                                <button type="submit" x-show="step === totalSteps"
                                    class="flex items-center justify-center gap-3 min-w-[160px] bg-gradient-to-r from-indigo-600 via-purple-600 to-cyan-600
                                       hover:from-indigo-500 hover:via-purple-500 hover:to-cyan-500
                                       text-white font-semibold py-4 px-8 rounded-xl transition-all duration-300
                                       shadow-lg shadow-indigo-500/30 hover:shadow-xl transform hover:-translate-y-0.5 btn-shine">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>


        <?php endif; ?>
    </main>

    <script>
        function formWizard() {
            return {
                step: 1,
                totalSteps: <?= count($steps) ?>,

                nextStep() {
                    const currentStepContainer = this.$refs['step' + this.step];
                    const inputs = currentStepContainer.querySelectorAll('input, select, textarea');
                    let valid = true;

                    inputs.forEach(input => {
                        if (!input.checkValidity()) {
                            input.reportValidity();
                            valid = false;
                        }
                    });

                    if (valid) {
                        this.step++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                prevStep() {
                    this.step--;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                createConfetti() {}
            };
        }
    </script>
</body>

</html>