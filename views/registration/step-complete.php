<?php
/**
 * Completion Step View
 * Shows QR code, WhatsApp group, PDF download etc based on settings
 */
$regData = $registration['data'] ?? [];
$regSettings = $form['registration_settings'] ?? [];

// Get links from form settings only (not global)
$whatsappGroupLink = $regSettings['whatsapp_group_link'] ?? '';
$eventPdfUrl = $regSettings['event_pdf_url'] ?? '';
$completionMessage = $regSettings['completion_message'] ?? 'Your registration has been verified.';

ob_start();
?>
    <!-- Verified / Complete View -->
    <?php
    $assignedClass = $regData['assigned_class'] ?? null;
    $assignedGate = $regData['assigned_gate'] ?? null;
    ?>

    <div class="space-y-6" x-data="{ confettiShown: false }"
        x-init="if (!confettiShown) { confettiShown = true; showConfetti(); }">
        <!-- Success Header -->
        <div
            class="relative bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500 rounded-3xl p-8 sm:p-10 text-center text-white shadow-2xl shadow-green-500/30 overflow-hidden">
            <!-- Animated Background -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-20 -right-20 w-60 h-60 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-white/10 rounded-full blur-3xl animate-pulse"
                    style="animation-delay: 1s;"></div>
            </div>

            <div class="relative">
                <div
                    class="w-24 h-24 mx-auto mb-6 bg-white/20 backdrop-blur rounded-full flex items-center justify-center shadow-xl">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="text-3xl sm:text-4xl font-bold mb-4">🎉 Registration Complete!</h1>
                <p class="text-lg text-green-100 max-w-md mx-auto">
                    <?= htmlspecialchars($completionMessage) ?>
                </p>
            </div>
        </div>

        <!-- Class & Gate Assignment -->
        <?php if ($assignedClass || $assignedGate): ?>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php if ($assignedClass): ?>
                    <div
                        class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 rounded-2xl p-6 text-white shadow-xl shadow-indigo-500/30 card-hover">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-indigo-200 text-sm font-medium uppercase tracking-wide">Your Class</p>
                                <p class="text-3xl font-bold"><?= htmlspecialchars($assignedClass) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($assignedGate): ?>
                    <div
                        class="bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 rounded-2xl p-6 text-white shadow-xl shadow-amber-500/30 card-hover">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-amber-200 text-sm font-medium uppercase tracking-wide">Entry Gate</p>
                                <p class="text-2xl font-bold"><?= htmlspecialchars($assignedGate) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- QR Code Section -->
        <div
            class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 p-8 sm:p-10">
            <div class="text-center">
                <div class="relative inline-block">
                    <div class="bg-white p-4 rounded-2xl shadow-xl inline-block">
                        <img id="qr-code-image"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($registration['id']) ?>"
                            alt="QR Code" class="w-48 h-48 sm:w-52 sm:h-52">
                    </div>
                    <!-- Decorative ring -->
                    <div
                        class="absolute -inset-2 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-600 -z-10">
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Registration ID</p>
                    <p class="text-xl font-mono font-bold text-slate-900 dark:text-white mb-6">
                        <?= htmlspecialchars($registration['id']) ?>
                    </p>

                    <button onclick="downloadQRCode()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-[length:200%_100%]
                               hover:bg-right text-white font-bold rounded-xl transition-all duration-500 
                               shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40
                               transform hover:-translate-y-0.5 btn-shine">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download QR Code
                    </button>
                </div>
            </div>
        </div>

        <!-- Registration Details (Collapsible) -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden"
            x-data="{ detailsOpen: false }">
            <button @click="detailsOpen = !detailsOpen"
                class="w-full flex items-center justify-between p-6 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <div class="p-2 bg-slate-100 dark:bg-slate-700 rounded-lg">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    Registration Details
                </h2>
                <svg class="w-5 h-5 text-slate-500 transition-transform duration-300"
                    :class="detailsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="detailsOpen" x-collapse class="border-t border-slate-100 dark:border-slate-700">
                <div class="p-6 grid sm:grid-cols-2 gap-4">
                    <?php foreach ($regData as $key => $value):
                        if (in_array($key, ['student_id_photo', 'payment_proof', 'assigned_class', 'assigned_gate', 'document_attempts', 'payment_attempts']))
                            continue;
                        ?>
                        <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                            <p class="text-sm text-slate-500 dark:text-slate-400 capitalize mb-1">
                                <?= htmlspecialchars(str_replace('_', ' ', $key)) ?>
                            </p>
                            <p class="font-semibold text-slate-900 dark:text-white">
                                <?= htmlspecialchars($value) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <?php if (!empty($whatsappGroupLink) || !empty($eventPdfUrl)): ?>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php if (!empty($whatsappGroupLink)): ?>
                    <a href="<?= htmlspecialchars($whatsappGroupLink) ?>" target="_blank" class="block p-6 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl text-white transition-all duration-300 
                               transform hover:-translate-y-1 hover:shadow-xl hover:shadow-green-500/30 card-hover">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">Join WhatsApp Group</h3>
                                <p class="text-green-100 text-sm">Get event updates</p>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if (!empty($eventPdfUrl)): ?>
                    <a href="<?= htmlspecialchars($eventPdfUrl) ?>" target="_blank" class="block p-6 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl text-white transition-all duration-300 
                               transform hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/30 card-hover">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">Download Event Guide</h3>
                                <p class="text-blue-100 text-sm">Important information</p>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function downloadQRCode() {
            const qrImage = document.getElementById('qr-code-image');
            const regId = '<?= htmlspecialchars($registration['id']) ?>';

            fetch(qrImage.src)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `QR-${regId}.png`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                })
                .catch(() => {
                    window.open(qrImage.src, '_blank');
                });
        }

        function showConfetti() {
            // Simple confetti effect
            const colors = ['#22c55e', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'];
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'fixed pointer-events-none z-50';
                    confetti.style.cssText = `
                        left: ${Math.random() * 100}vw;
                        top: -10px;
                        width: 10px;
                        height: 10px;
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
                        animation: confettiFall ${2 + Math.random() * 2}s ease-out forwards;
                    `;
                    document.body.appendChild(confetti);
                    setTimeout(() => confetti.remove(), 4000);
                }, i * 50);
            }
        }
    </script>

    <style>
        @keyframes confettiFall {
            to {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
    </style>

<?php
$stepContent = ob_get_clean();
require __DIR__ . '/layout.php';
?>