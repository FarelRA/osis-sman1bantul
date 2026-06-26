<?php
/**
 * Dynamic Form Step View
 * Renders form fields based on step configuration from forms.json
 */
$old = $_SESSION['flash_old'] ?? [];
unset($_SESSION['flash_old']);

$step = $stepConfig['step'] ?? [];
$fields = $step['fields'] ?? [];
$stepTitle = $step['title'] ?? 'Step ' . ($currentStep + 1);
$regData = $registration['data'] ?? [];

ob_start();
?>

<div
    class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden card-hover">
    <!-- Card Header -->
    <div
        class="relative px-6 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-slate-50 to-white dark:from-slate-800 dark:to-slate-800">
        <div
            class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-indigo-100/50 to-purple-100/50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2">
        </div>
        <div class="relative">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">
                <?= htmlspecialchars($stepTitle) ?>
            </h2>
            <?php if (!empty($form['description']) && $currentStep === 0): ?>
                <p class="text-slate-600 dark:text-slate-400">
                    <?= htmlspecialchars($form['description']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <form action="<?= htmlspecialchars($basePath) ?>/step/<?= $currentStep ?>" method="POST" data-autosave
        class="p-6 sm:p-8">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="space-y-6">
            <?php foreach ($fields as $field):
                $name = $field['name'];
                $label = $field['label'] ?? $name;
                $type = $field['type'] ?? 'text';
                $required = $field['required'] ?? false;
                $placeholder = $field['placeholder'] ?? '';
                $options = $field['options'] ?? [];
                $value = $old[$name] ?? $regData[$name] ?? '';
                $error = $_SESSION['flash_errors'][$name] ?? null;
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
                        <textarea name="<?= $name ?>" id="<?= $name ?>" rows="3"
                            placeholder="<?= htmlspecialchars($placeholder) ?>" <?= $required ? 'required' : '' ?>
                            class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200 resize-none
                                   <?= $error ? 'border-rose-300 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900' ?>
                                   text-slate-900 dark:text-white placeholder-slate-400
                                   focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                   hover:border-slate-300 dark:hover:border-slate-500"><?= htmlspecialchars($value) ?></textarea>

                    <?php elseif ($type === 'select'): ?>
                        <div class="relative">
                            <select name="<?= $name ?>" id="<?= $name ?>" <?= $required ? 'required' : '' ?> class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200 appearance-none cursor-pointer
                                       <?= $error ? 'border-rose-300 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900' ?>
                                       text-slate-900 dark:text-white
                                       focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                       hover:border-slate-300 dark:hover:border-slate-500">
                                <option value="">Select...</option>
                                <?php foreach ($options as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= $value === $opt ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                    <?php elseif ($type === 'tel'): ?>
                        <div class="relative group">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                            </span>
                            <input type="tel" name="<?= $name ?>" id="<?= $name ?>" value="<?= htmlspecialchars($value) ?>"
                                placeholder="<?= htmlspecialchars($placeholder) ?>" pattern="^(0|62|\+62)8[0-9]{8,12}$"
                                <?= $required ? 'required' : '' ?> class="w-full pl-12 pr-4 py-3.5 rounded-xl border-2 transition-all duration-200
                                       <?= $error ? 'border-rose-300 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900' ?>
                                       text-slate-900 dark:text-white placeholder-slate-400
                                       focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                       hover:border-slate-300 dark:hover:border-slate-500">
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Format: 08xx or 628xx
                        </p>

                    <?php elseif ($type === 'email'): ?>
                        <div class="relative group">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <input type="email" name="<?= $name ?>" id="<?= $name ?>" value="<?= htmlspecialchars($value) ?>"
                                placeholder="<?= htmlspecialchars($placeholder) ?>" <?= $required ? 'required' : '' ?> class="w-full pl-12 pr-4 py-3.5 rounded-xl border-2 transition-all duration-200
                                       <?= $error ? 'border-rose-300 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900' ?>
                                       text-slate-900 dark:text-white placeholder-slate-400
                                       focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                       hover:border-slate-300 dark:hover:border-slate-500">
                        </div>

                    <?php elseif ($type === 'file'): ?>
                        <div class="relative">
                            <input type="file" name="<?= $name ?>" id="<?= $name ?>" <?= $required ? 'required' : '' ?>
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200 cursor-pointer
                                       <?= $error ? 'border-rose-300 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900' ?>
                                       text-slate-900 dark:text-white
                                       file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                       file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300
                                       hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50
                                       focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none">
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            JPG, PNG, PDF, DOC (max 5MB)
                        </p>

                    <?php else: ?>
                        <input type="<?= htmlspecialchars($type) ?>" name="<?= $name ?>" id="<?= $name ?>"
                            value="<?= htmlspecialchars($value) ?>" placeholder="<?= htmlspecialchars($placeholder) ?>"
                            <?= $required ? 'required' : '' ?> class="w-full px-4 py-3.5 rounded-xl border-2 transition-all duration-200
                                   <?= $error ? 'border-rose-300 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900' ?>
                                   text-slate-900 dark:text-white placeholder-slate-400
                                   focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:focus:ring-indigo-500/20 focus:outline-none
                                   hover:border-slate-300 dark:hover:border-slate-500">
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-400 flex items-center gap-1.5 animate-fade-in-up">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?= htmlspecialchars($error) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Submit Button -->
        <div class="mt-10 pt-6 border-t border-slate-100 dark:border-slate-700">
            <button type="submit" class="w-full sm:w-auto sm:min-w-[220px] group relative overflow-hidden
                       bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-[length:200%_100%]
                       hover:bg-right text-white font-bold py-4 px-8 
                       rounded-xl transition-all duration-500 
                       shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40
                       flex items-center justify-center gap-3 mx-auto
                       transform hover:-translate-y-0.5 active:translate-y-0 btn-shine">
                <span>Continue</span>
                <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </form>
</div>

<?php
$stepContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>