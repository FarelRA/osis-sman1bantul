<?php
$title = 'Twibbon Generator - OSIS SMAN 1 Bantul';
$twibbons = json_decode(file_get_contents(BASE_PATH . '/data/twibbons.json'), true);

// Check URL parameter
$selected_id = $_GET['id'] ?? null;
if ($selected_id) {
    // Filter to only the selected twibbon
    $available_twibbons = array_filter($twibbons, fn($t) => $t['id'] === $selected_id);
} else {
    // Show all twibbons
    $available_twibbons = $twibbons;
}

ob_start();
?>

<script>
(function() {
    var ua = navigator.userAgent;
    var isAndroid = /android/i.test(ua);

    if (/Instagram|FBAV|FBAN|Messenger|Twitter|TikTok|LinkedInApp|Pinterest|Snapchat/i.test(ua)) {
        if (isAndroid) {
            window.location.href = 'intent://' + window.location.href.replace(/^https?:\/\//, '') + '#Intent;scheme=https;action=android.intent.action.VIEW;end';
        }

        document.documentElement.style.display = 'none';
        document.addEventListener('DOMContentLoaded', function() {
            document.documentElement.style.display = '';
            var div = document.createElement('div');
            div.style.cssText = 'position:fixed;inset:0;z-index:9999;background:white;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;text-align:center;font-family:sans-serif;';
            div.innerHTML =
                '<h1 style="font-size:1.5rem;margin-bottom:1rem;color:#1a1a1a;">Open in ' + (isAndroid ? 'Chrome' : 'Safari') + '</h1>' +
                '<p style="font-size:1rem;margin-bottom:0.5rem;color:#666;max-width:28rem;">Your browser doesn\'t support saving images.</p>' +
                '<p style="font-size:1rem;margin-bottom:2rem;color:#666;max-width:28rem;line-height:1.5;">' + (isAndroid
                    ? 'Tap <strong>Open</strong> when prompted, or copy the URL below and open it manually in Chrome.'
                    : 'Tap the share button <span style="font-size:1.3rem;">☝</span> in the address bar, then choose <strong>Open in Safari</strong>, or copy the URL below and open it manually.') +
                '</p>' +
                '<button onclick="navigator.clipboard.writeText(location.href);this.textContent=\'Copied!\'" style="padding:0.75rem 2rem;background:#2C3E7C;color:white;border:none;border-radius:12px;font-size:1rem;cursor:pointer;">Copy URL</button>';
            document.body.appendChild(div);
        });
    }
})();
</script>

<section class="py-20 relative" x-data="twibbonGenerator()">
    <!-- Loading Overlay -->
    <div x-show="loading" class="absolute inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 flex flex-col items-center gap-4">
            <div class="w-16 h-16 border-4 border-[#2C3E7C] border-t-transparent rounded-full animate-spin"></div>
            <p class="text-gray-900 dark:text-white font-medium">Processing image...</p>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <h1 class="section-title text-center mb-4">Twibbon Generator</h1>
        <p class="text-center text-gray-600 dark:text-gray-400 mb-16 max-w-2xl mx-auto text-lg">
            Create custom twibbon photos to support OSIS events
        </p>

        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <!-- Upload Section -->
                <div class="card p-8 flex flex-col">
                    <h3 class="text-2xl font-medium mb-6 text-gray-900 dark:text-white flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-[#2C3E7C] to-[#1e2a54] rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        Upload Photo
                    </h3>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-16 text-center hover:border-[#2C3E7C] dark:hover:border-blue-400 transition-all duration-300 cursor-pointer bg-gray-50 dark:bg-gray-900/50 group flex-1 flex items-center justify-center"
                        @click="$refs.fileInput.click()">
                        <input type="file" x-ref="fileInput" @change="handleFileUpload" accept="image/*" class="hidden">
                        <div class="flex flex-col items-center">
                            <div
                                class="w-20 h-20 bg-gradient-to-br from-[#2C3E7C] 100 to-[#1e2a54] 100 dark:from-[#2C3E7C] 900/30 dark:to-[#1e2a54] 900/30 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-[#2C3E7C] dark:text-blue-600 dark:text-[#2C3E7C] dark:text-blue-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <p class="text-xl text-gray-900 dark:text-white font-medium mb-2">Click to upload photo</p>
                        </div>
                    </div>
                </div>

                <!-- Frame Selection -->
                <?php if (count($available_twibbons) > 1): ?>
                <div class="card p-8">
                    <h3 class="text-2xl font-medium mb-6 text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#2C3E7C] to-[#1e2a54] rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        Select Event Frame
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($available_twibbons as $tw): ?>
                            <button @click="selectFrame('<?= $tw['id'] ?>')"
                                :class="selectedFrame === '<?= $tw['id'] ?>' ? 'ring-4 ring-[#2C3E7C] dark:ring-blue-400 scale-105' : 'border-2 border-gray-200 dark:border-gray-700 hover:border-[#2C3E7C] dark:hover:border-blue-400'"
                                class="card p-4 transition-all duration-300">
                                <img src="<?= asset('assets/twibbon/' . $tw['id'] . '.webp') ?>"
                                    class="w-full aspect-square object-contain rounded-xl mb-3"
                                    alt="<?= $tw['name'] ?>">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($tw['name']) ?>
                                </p>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Preview Section -->
                <div class="card p-8 flex flex-col">
                    <h3 class="text-2xl font-medium mb-6 text-gray-900 dark:text-white flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-[#2C3E7C] to-[#1e2a54] rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        Preview
                    </h3>
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-2xl mb-6 relative overflow-hidden border-2 border-gray-200 dark:border-gray-700" style="position: relative;">
                        <img id="cropperImage" style="max-width: 100%; max-height: 100%; display: none;">
                        <div x-show="!cropper" class="flex items-center justify-center absolute inset-0">
                            <div class="text-center p-8">
                                <svg class="w-20 h-20 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-gray-500 dark:text-gray-400 text-lg">Preview will appear here</span>
                            </div>
                        </div>
                        <img :src="getFrameUrl()" x-show="cropper"
                            class="absolute inset-0 w-full h-full object-cover pointer-events-none" style="z-index: 10;">
                    </div>
                    <button @click="downloadTwibbon" :disabled="!cropper"
                        class="w-full px-8 py-4 bg-gradient-to-r from-[#2C3E7C] to-[#1e2a54] text-white rounded-xl font-medium hover:from-[#2C3E7C] 600 hover:to-[#1e2a54] 600 transition-all duration-300 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Twibbon
                    </button>
                </div>

                <!-- Caption Section -->
                <div class="card p-8" x-show="hasCaption()">
                    <h3 class="text-2xl font-medium mb-6 text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#2C3E7C] to-[#1e2a54] rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        Caption
                    </h3>
                    
                    <template x-if="getCaptionFields().length > 0">
                        <div class="space-y-4 mb-6">
                            <template x-for="field in getCaptionFields()" :key="field.key">
                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-900 dark:text-white" x-text="field.label"></label>
                                    <input type="text" x-model="captionData[field.key]" @input="updateCaption"
                                        :placeholder="field.placeholder"
                                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:border-[#2C3E7C] focus:ring-2 focus:ring-[#2C3E7C]/20 transition-all">
                                </div>
                            </template>
                        </div>
                    </template>

                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 mb-4">
                        <pre class="whitespace-pre-wrap text-sm text-gray-900 dark:text-white font-mono" x-text="generatedCaption"></pre>
                    </div>

                    <button @click="copyCaption" :disabled="!canCopy()"
                        class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl font-medium hover:from-green-700 hover:to-green-800 transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-text="copied ? 'Copied!' : 'Copy Caption'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    window.twibbonFrames = <?= json_encode(array_values($available_twibbons)) ?>;
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>