<?php
/**
 * Form Wizard - Premium Multi-Step Form
 * Dynamic step-based form with beautiful styling
 * 
 * Expected variables:
 * - $title: Form title
 * - $description: Form description  
 * - $steps: Array of step configurations
 * - $submitUrl: Form submission URL
 * - $returnUrl: Return URL after success
 * - $success: Success message (if any)
 * - $errors: Validation errors array
 * - $old: Previously submitted values
 */
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
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
        .bg-mesh {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 40% 20%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(6, 182, 212, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(168, 85, 247, 0.1) 0px, transparent 50%),
                radial-gradient(at 80% 100%, rgba(59, 130, 246, 0.1) 0px, transparent 50%);
        }

        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-light {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2), 0 0 20px rgba(99, 102, 241, 0.1);
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-shine:hover::before {
            left: 100%;
        }

        /* Progress pulse */
        .progress-pulse {
            animation: progressPulse 2s ease-in-out infinite;
        }

        @keyframes progressPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            50% { box-shadow: 0 0 0 6px rgba(99, 102, 241, 0); }
        }

        /* Step connector animation */
        .step-connector-active {
            position: relative;
            overflow: hidden;
        }

        .step-connector-active::after {
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

        /* Entrance animation */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        /* Success confetti effect */
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

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.5); }
    </style>
</head>

<body class="bg-mesh min-h-screen font-sans antialiased" x-data="formWizard()">
    <!-- Decorative Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <header class="relative z-20 glass border-b border-white/5">
        <div class="max-w-4xl mx-auto px-4 py-4 sm:py-5">
            <div class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-3 group">
                    <img src="/public/assets/images/osis.png" alt="OSIS" class="h-10 w-10 transition-transform group-hover:scale-110">
                    <div>
                        <span class="block text-white font-bold leading-tight">OSIS</span>
                        <span class="block text-white/60 text-xs">SMAN 1 Bantul</span>
                    </div>
                </a>
                <a href="<?= htmlspecialchars($returnUrl ?? '/') ?>" 
                   class="flex items-center gap-2 text-white/60 hover:text-white transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 max-w-4xl mx-auto px-4 py-8 sm:py-12">
        
        <?php if ($success): ?>
                <!-- Success State -->
                <div class="animate-fade-in-up">
                    <div class="glass rounded-3xl p-8 sm:p-12 text-center relative overflow-hidden">
                        <!-- Confetti Effect -->
                        <div class="absolute inset-0" x-init="createConfetti()">
                            <template x-for="i in 20" :key="i">
                                <div class="confetti-piece" 
                                     :style="`left: ${Math.random() * 100}%; background: ${['#6366f1', '#06b6d4', '#8b5cf6', '#ec4899', '#f59e0b'][Math.floor(Math.random() * 5)]}; animation-delay: ${Math.random() * 2}s;`">
                                </div>
                            </template>
                        </div>
                    
                        <!-- Success Icon -->
                        <div class="relative mb-8">
                            <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-green-500/30">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    
                        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Registration Complete!</h2>
                        <p class="text-slate-300 text-lg mb-8 max-w-md mx-auto"><?= htmlspecialchars($success) ?></p>
                    
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
                </div>
        <?php else: ?>
                <!-- Form Header -->
                <div class="text-center mb-8 animate-fade-in-up">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">
                        <?= htmlspecialchars($title) ?>
                    </h1>
                    <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                        <?= htmlspecialchars($description ?? '') ?>
                    </p>
                </div>

                <!-- Step Indicators -->
                <?php if (count($steps) > 1): ?>
                        <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.1s; opacity: 0;">
                            <div class="flex items-center justify-center">
                                <?php foreach ($steps as $index => $stepData):
                                    $stepNum = $index + 1;
                                    $isLast = $index === count($steps) - 1;
                                    ?>
                                        <div class="flex items-center">
                                            <!-- Step Circle -->
                                            <div class="flex flex-col items-center">
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
                                                     :class="step > <?= $stepNum ?> ? 'bg-gradient-to-br from-green-400 to-emerald-500 text-white shadow-lg shadow-green-500/30' : 
                                                 step === <?= $stepNum ?> ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/40 progress-pulse' : 
                                                 'bg-white/10 text-white/40'">
                                                    <template x-if="step > <?= $stepNum ?>">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </template>
                                                    <template x-if="step <= <?= $stepNum ?>">
                                                        <span><?= $stepNum ?></span>
                                                    </template>
                                                </div>
                                                <span class="mt-2 text-xs font-medium hidden sm:block"
                                                      :class="step >= <?= $stepNum ?> ? 'text-white' : 'text-white/40'">
                                                    <?= htmlspecialchars($stepData['title'] ?? 'Step ' . $stepNum) ?>
                                                </span>
                                            </div>
                                
                                            <?php if (!$isLast): ?>
                                                    <!-- Connector Line -->
                                                    <div class="w-12 sm:w-20 h-1 mx-2 sm:mx-3 mb-6 sm:mb-7 rounded-full overflow-hidden"
                                                         :class="step > <?= $stepNum ?> ? 'bg-gradient-to-r from-green-400 to-emerald-500' : 'bg-white/10'">
                                                        <div x-show="step === <?= $stepNum ?>" class="step-connector-active h-full"></div>
                                                    </div>
                                            <?php endif; ?>
                                        </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                <?php endif; ?>

                <!-- Form Card -->
                <div class="glass-light dark:glass rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up" 
                     style="animation-delay: 0.2s; opacity: 0;">
                
                    <!-- Progress Bar -->
                    <?php if (count($steps) > 1): ?>
                            <div class="h-1.5 bg-slate-200 dark:bg-white/10">
                                <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-500 transition-all duration-500 ease-out"
                                     :style="'width: ' + ((step / totalSteps) * 100) + '%'"></div>
                            </div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars($submitUrl) ?>" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 lg:p-10">
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
                                    <div class="mb-8">
                                        <div class="flex items-center justify-between gap-4 mb-2">
                                            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                                                <?= htmlspecialchars($stepData['title']) ?>
                                            </h2>
                                            <span class="px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                                                <span x-text="step"></span> / <span x-text="totalSteps"></span>
                                            </span>
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400">
                                            Please fill in all required fields to proceed.
                                        </p>
                                    </div>

                                    <!-- Fields -->
                                    <div class="space-y-5">
                                        <?php foreach ($stepData['fields'] as $field):
                                            $type = $field['type'] ?? 'text';
                                            $name = $field['name'];
                                            $label = $field['label'] ?? ucfirst($name);
                                            $required = !empty($field['required']) ? 'required' : '';
                                            $placeholder = $field['placeholder'] ?? '';
                                            $value = htmlspecialchars($old[$name] ?? '');
                                            $error = $errors[$name] ?? null;
                                            $inputClasses = $error
                                                ? 'border-rose-300 dark:border-rose-500 focus:border-rose-500 focus:ring-rose-500/20'
                                                : 'border-slate-200 dark:border-white/10 focus:border-indigo-500 focus:ring-indigo-500/20';
                                            ?>
                                                <div class="group">
                                                    <label for="<?= $name ?>" 
                                                           class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                                        <?= htmlspecialchars($label) ?>
                                                        <?php if ($required): ?>
                                                                <span class="text-rose-500">*</span>
                                                        <?php endif; ?>
                                                    </label>

                                                    <?php if ($type === 'textarea'): ?>
                                                            <textarea name="<?= $name ?>" id="<?= $name ?>" rows="4" <?= $required ?>
                                                                class="w-full px-4 py-3.5 rounded-xl border-2 <?= $inputClasses ?> 
                                                       bg-white dark:bg-white/5 text-slate-900 dark:text-white 
                                                       placeholder-slate-400 dark:placeholder-slate-500
                                                       transition-all duration-200 outline-none input-glow resize-none"
                                                                placeholder="<?= htmlspecialchars($placeholder) ?>"><?= $value ?></textarea>

                                                    <?php elseif ($type === 'select'): ?>
                                                            <div class="relative">
                                                                <select name="<?= $name ?>" id="<?= $name ?>" <?= $required ?>
                                                                    class="w-full px-4 py-3.5 rounded-xl border-2 <?= $inputClasses ?> 
                                                           bg-white dark:bg-white/5 text-slate-900 dark:text-white 
                                                           transition-all duration-200 outline-none appearance-none cursor-pointer input-glow">
                                                                    <option value="" class="text-slate-400">Select <?= htmlspecialchars($label) ?></option>
                                                                    <?php foreach (($field['options'] ?? []) as $opt): ?>
                                                                            <option value="<?= htmlspecialchars($opt) ?>" <?= $value === $opt ? 'selected' : '' ?>
                                                                                    class="text-slate-900 dark:text-white bg-white dark:bg-slate-800">
                                                                                <?= htmlspecialchars($opt) ?>
                                                                            </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                    <?php elseif ($type === 'file'): ?>
                                                            <div class="relative">
                                                                <input type="file" name="<?= $name ?>" id="<?= $name ?>" <?= $required ?>
                                                                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                                    class="w-full px-4 py-3.5 rounded-xl border-2 border-dashed <?= $inputClasses ?> 
                                                           bg-slate-50 dark:bg-white/5 text-slate-900 dark:text-white 
                                                           file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 
                                                           file:text-sm file:font-semibold file:bg-indigo-100 file:text-indigo-700 
                                                           hover:file:bg-indigo-200 dark:file:bg-indigo-900/50 dark:file:text-indigo-300
                                                           transition-all cursor-pointer hover:border-indigo-400">
                                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                    </svg>
                                                                    Allowed: JPG, PNG, PDF, DOC (max 5MB)
                                                                </p>
                                                            </div>

                                                    <?php elseif ($type === 'tel'): ?>
                                                            <div class="relative">
                                                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                                                    <span class="text-slate-400 dark:text-slate-500 font-medium">+62</span>
                                                                </div>
                                                                <input type="tel" name="<?= $name ?>" id="<?= $name ?>"
                                                                    value="<?= $value ?>" <?= $required ?>
                                                                    pattern="^(0|62|\+62)?8[0-9]{8,12}$"
                                                                    title="Enter a valid Indonesian phone number"
                                                                    class="w-full pl-14 pr-4 py-3.5 rounded-xl border-2 <?= $inputClasses ?> 
                                                           bg-white dark:bg-white/5 text-slate-900 dark:text-white 
                                                           placeholder-slate-400 dark:placeholder-slate-500
                                                           transition-all duration-200 outline-none input-glow"
                                                                    placeholder="<?= htmlspecialchars($placeholder ?: '812xxxxxxxx') ?>">
                                                            </div>

                                                    <?php else: ?>
                                                            <input type="<?= htmlspecialchars($type) ?>" name="<?= $name ?>" id="<?= $name ?>"
                                                                value="<?= $value ?>" <?= $required ?>
                                                                class="w-full px-4 py-3.5 rounded-xl border-2 <?= $inputClasses ?> 
                                                       bg-white dark:bg-white/5 text-slate-900 dark:text-white 
                                                       placeholder-slate-400 dark:placeholder-slate-500
                                                       transition-all duration-200 outline-none input-glow"
                                                                placeholder="<?= htmlspecialchars($placeholder) ?>">
                                                    <?php endif; ?>

                                                    <?php if ($error): ?>
                                                            <p class="mt-2 text-sm text-rose-500 flex items-center gap-1.5">
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
                        <div class="mt-10 pt-8 border-t border-slate-200 dark:border-white/10 flex items-center justify-between gap-4">
                            <!-- Back Button -->
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

                            <!-- Next/Submit Buttons -->
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

                <!-- Footer Note -->
                <div class="mt-6 text-center">
                    <p class="text-slate-500 text-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Your information is secure and encrypted
                    </p>
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
                
                createConfetti() {
                    // Confetti is created via Alpine template
                }
            };
        }

        // Auto dark mode detection
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            document.documentElement.classList.toggle('dark', e.matches);
        });
    </script>
</body>

</html>