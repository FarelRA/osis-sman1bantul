<?php
/**
 * Document Verification Step (Dynamic)
 * Uses form configuration for title and AI settings
 */
$regSettings = $form['registration_settings'] ?? [];
$docTitle = $regSettings['document_verification_title'] ?? 'Verify Your Identity';
$aiEnabled = !empty($regSettings['ai_verification']) && !empty($settings['ai_api_key']);
$uploadedPhoto = $registration['data']['student_id_photo'] ?? null;
$aiResult = $registration['ai_verification']['student_id'] ?? null;
$documentAttempts = (int) ($registration['data']['document_attempts'] ?? 0);

ob_start();
?>

<div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden card-hover"
    x-data="documentVerification()">

    <!-- Card Header -->
    <div
        class="relative px-6 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-violet-50 via-purple-50 to-fuchsia-50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-800">
        <!-- Decorative Elements -->
        <div
            class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-violet-400/30 to-purple-400/30 rounded-full blur-3xl">
        </div>
        <div
            class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-br from-fuchsia-400/20 to-pink-400/20 rounded-full blur-2xl">
        </div>

        <div class="relative flex items-start gap-4">
            <!-- Icon -->
            <div
                class="hidden sm:flex flex-shrink-0 w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 items-center justify-center shadow-lg shadow-violet-500/30">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                </svg>
            </div>

            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    <?= htmlspecialchars($docTitle) ?>
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    Upload a clear photo of your student ID card.
                    <?php if ($aiEnabled): ?>
                        Our AI will verify it automatically.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="p-6 sm:p-8">
        <!-- Upload Area -->
        <div x-show="!isVerifying && !isVerified && !hasError">
            <div class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all duration-300 cursor-pointer
                        border-slate-300 dark:border-slate-600 
                        hover:border-violet-400 dark:hover:border-violet-500
                        hover:bg-violet-50/50 dark:hover:bg-violet-900/10" @click="$refs.fileInput.click()"
                @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop($event)"
                :class="{ 'border-violet-500 bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20': isDragging }">

                <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" accept="image/*" class="hidden">

                <div x-show="!previewUrl">
                    <div
                        class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30 rounded-2xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Click or drag to upload your Student ID
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Take a clear photo of your physical student ID card
                    </p>
                </div>

                <!-- Preview -->
                <div x-show="previewUrl" class="relative">
                    <div class="relative inline-block rounded-xl overflow-hidden shadow-lg">
                        <img :src="previewUrl" class="max-w-full sm:max-w-sm rounded-xl">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                    <button x-show="!hasError" @click.stop="clearPreview()"
                        class="absolute top-3 right-3 p-2 bg-rose-500 hover:bg-rose-600 text-white rounded-full shadow-lg transition-all transform hover:scale-105">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Upload Button -->
            <div x-show="previewUrl" class="mt-6 text-center">
                <button @click="uploadAndVerify()" class="inline-flex items-center gap-3 bg-gradient-to-r from-violet-600 via-purple-600 to-violet-600 bg-[length:200%_100%]
                           hover:bg-right text-white font-bold py-4 px-8 rounded-xl transition-all duration-500 
                           shadow-lg shadow-violet-500/30 hover:shadow-xl hover:shadow-violet-500/40
                           transform hover:-translate-y-0.5 btn-shine">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?= $aiEnabled ? 'Verify My Student ID' : 'Upload Student ID' ?>
                </button>
            </div>
        </div>

        <?php if ($aiEnabled): ?>
            <!-- AI Verification Loading -->
            <div x-show="isVerifying" class="py-8">
                <div class="max-w-md mx-auto">
                    <div class="ai-glow-container mb-8">
                        <div class="relative rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                            <img :src="previewUrl" class="w-full rounded-xl">
                            <div class="ai-scanning-line"></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-violet-600/20 to-transparent"></div>
                        </div>
                    </div>

                    <div class="text-center mb-6">
                        <div class="inline-flex items-center gap-2 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 
                                px-4 py-2 rounded-full text-sm font-semibold">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="verificationStatus" class="typing-dots">Analyzing document</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(step, index) in verificationSteps" :key="index">
                            <div class="flex items-center gap-3 p-4 rounded-xl transition-all duration-300" :class="{ 
                                 'bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800': step.done,
                                 'bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 border border-violet-200 dark:border-violet-800': step.active && !step.done,
                                 'bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700': !step.active && !step.done
                             }">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                                    :class="{
                                     'bg-gradient-to-br from-green-400 to-emerald-500 text-white shadow-lg shadow-green-500/30': step.done,
                                     'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/30 animate-pulse': step.active && !step.done,
                                     'bg-slate-200 dark:bg-slate-700 text-slate-500': !step.active && !step.done
                                 }">
                                    <template x-if="step.done">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                    <template x-if="!step.done">
                                        <span x-text="index + 1"></span>
                                    </template>
                                </div>
                                <span class="font-medium" :class="{ 
                                      'text-green-700 dark:text-green-300': step.done,
                                      'text-violet-700 dark:text-violet-300': step.active && !step.done,
                                      'text-slate-500 dark:text-slate-400': !step.active && !step.done
                                  }" x-text="step.text"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Verification Complete -->
        <div x-show="isVerified" class="py-8 text-center">
            <div
                class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-full flex items-center justify-center shadow-lg shadow-green-500/20">
                <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                <?= $aiEnabled ? 'Identity Verified!' : 'Document Uploaded!' ?>
            </h3>
            <p class="text-slate-600 dark:text-slate-400 mb-8">
                <?= $aiEnabled ? 'Your student ID has been verified.' : 'Your document has been uploaded successfully.' ?>
            </p>
            <a href="<?= htmlspecialchars($basePath) ?>" class="inline-flex items-center gap-3 bg-gradient-to-r from-green-600 via-emerald-600 to-green-600 bg-[length:200%_100%]
                       hover:bg-right text-white font-bold py-4 px-8 rounded-xl transition-all duration-500 
                       shadow-lg shadow-green-500/30 hover:shadow-xl hover:shadow-green-500/40
                       transform hover:-translate-y-0.5 btn-shine">
                Continue to Next Step
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>

        <!-- Error State -->
        <div x-show="hasError" class="py-8">
            <div class="max-w-md mx-auto bg-gradient-to-br from-rose-50 to-red-50 dark:from-rose-900/20 dark:to-red-900/20 
                        border border-rose-200 dark:border-rose-800 rounded-2xl p-6 shadow-lg shadow-rose-500/10">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 p-3 bg-rose-100 dark:bg-rose-800 rounded-xl">
                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-rose-800 dark:text-rose-200">Verification Failed</h3>
                        <p class="text-sm text-rose-700 dark:text-rose-300 mt-1" x-text="errorMessage"></p>
                        <button @click="retry()"
                            class="mt-4 inline-flex items-center gap-2 text-rose-600 dark:text-rose-400 font-semibold hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function documentVerification() {
        return {
            previewUrl: null,
            selectedFile: null,
            isDragging: false,
            isVerifying: false,
            isVerified: false,
            hasError: false,
            errorMessage: '',
            verificationStatus: 'Analyzing document',
            aiEnabled: <?= $aiEnabled ? 'true' : 'false' ?>,
            attempts: <?= $documentAttempts ?>,
            maxAttempts: 2,
            verificationSteps: [
                { text: 'Detecting document type', active: false, done: false },
                { text: 'Extracting information', active: false, done: false },
                { text: 'Matching with your data', active: false, done: false },
                { text: 'Final verification', active: false, done: false }
            ],

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.validateAndSetFile(file);
            },

            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.validateAndSetFile(file);
                }
            },

            async validateAndSetFile(file) {
                // Only accept images
                if (!file.type.startsWith('image/')) {
                    this.hasError = true;
                    this.errorMessage = 'Please upload an image file (JPG, PNG).';
                    return;
                }

                this.hasError = false;

                // Compress if larger than 1.5MB (PHP default limit is usually 2MB)
                const maxSize = 1.5 * 1024 * 1024;
                if (file.size > maxSize) {
                    try {
                        const compressed = await this.compressImage(file, maxSize);
                        this.setFile(compressed);
                    } catch (e) {
                        console.error('Compression failed:', e);
                        this.hasError = true;
                        this.errorMessage = 'Failed to compress image. Please try a smaller file.';
                    }
                } else {
                    this.setFile(file);
                }
            },

            async compressImage(file, maxSize) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => {
                        // Calculate new dimensions (max 1920px on longest side)
                        let { width, height } = img;
                        const maxDim = 1920;
                        if (width > maxDim || height > maxDim) {
                            if (width > height) {
                                height = Math.round((height * maxDim) / width);
                                width = maxDim;
                            } else {
                                width = Math.round((width * maxDim) / height);
                                height = maxDim;
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // Try different quality levels
                        let quality = 0.8;
                        const tryCompress = () => {
                            canvas.toBlob(
                                (blob) => {
                                    if (blob.size <= maxSize || quality <= 0.3) {
                                        const compressedFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                                            type: 'image/jpeg',
                                            lastModified: Date.now()
                                        });
                                        resolve(compressedFile);
                                    } else {
                                        quality -= 0.1;
                                        tryCompress();
                                    }
                                },
                                'image/jpeg',
                                quality
                            );
                        };
                        tryCompress();
                    };
                    img.onerror = reject;
                    img.src = URL.createObjectURL(file);
                });
            },

            setFile(file) {
                this.selectedFile = file;
                this.previewUrl = URL.createObjectURL(file);
            },

            clearPreview() {
                this.previewUrl = null;
                this.selectedFile = null;
            },

            async uploadAndVerify() {
                if (!this.selectedFile) return;

                // Check if attempts already exhausted
                if (this.attempts >= this.maxAttempts) {
                    window.location.href = '<?= htmlspecialchars($basePath) ?>';
                    return;
                }

                this.hasError = false;
                this.isVerifying = true;

                // Start upload immediately
                const formData = new FormData();
                formData.append('student_id_photo', this.selectedFile);
                formData.append('field', 'student_id_photo');

                const uploadPromise = fetch('<?= htmlspecialchars($basePath) ?>/upload', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());

                // Run animation in parallel with upload (only if AI enabled)
                let animationPromise = Promise.resolve();
                if (this.aiEnabled) {
                    animationPromise = (async () => {
                        const base = 500;
                        for (let i = 0; i < this.verificationSteps.length; i++) {
                            this.verificationSteps[i].active = true;
                            this.verificationStatus = this.verificationSteps[i].text;
                            await this.sleep(base * (i + 1));
                            this.verificationSteps[i].done = true;
                            this.verificationSteps[i].active = false;
                        }
                    })();
                }

                try {
                    const [result] = await Promise.all([uploadPromise, animationPromise]);

                    if (result.success === false) {
                        this.hasError = true;
                        this.errorMessage = result.error || 'Upload failed. Please try again.';
                    } else if (result.verified) {
                        this.isVerified = true;
                    } else if (result.failed) {
                        window.location.href = '<?= htmlspecialchars($basePath) ?>';
                    } else {
                        this.attempts++;
                        this.hasError = true;
                        this.errorMessage = result.message || 'Verification failed. Please try again with a clearer photo.';
                    }
                } catch (e) {
                    console.error('Upload error:', e);
                    this.hasError = true;
                    this.errorMessage = 'Upload failed. Please try again.';
                }

                this.isVerifying = false;
            },

            retry() {
                // Check if retry is allowed
                if (this.attempts >= this.maxAttempts) {
                    window.location.href = '<?= htmlspecialchars($basePath) ?>';
                    return;
                }

                this.hasError = false;
                this.previewUrl = null;
                this.selectedFile = null;
                this.verificationSteps.forEach(s => { s.active = false; s.done = false; });
            },

            sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }
        };
    }
</script>

<?php
$stepContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>