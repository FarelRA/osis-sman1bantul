<?php
/**
 * Orchestrator Accounts Management View
 * Allows forms admin to create, edit, and delete orchestrator accounts
 */
?>

<div x-data="accountsManager()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="/admin/forms.php"
                class="p-2 -ml-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Orchestrator Accounts</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($activeForm['title']) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="?form_id=<?= urlencode($activeFormId) ?>&mode=scanner"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-sm font-bold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                Scanner
            </a>
            <button @click="openCreateModal()"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-amber-500/25">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Account
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="accounts.length">0</p>
                    <p class="text-xs text-gray-500">Total Accounts</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"
                        x-text="accounts.filter(a => a.active).length">0</p>
                    <p class="text-xs text-gray-500">Active</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"
                        x-text="accounts.filter(a => a.permission === 'super').length">0</p>
                    <p class="text-xs text-gray-500">Super Admins</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"
                        x-text="accounts.filter(a => a.last_login).length">0</p>
                    <p class="text-xs text-gray-500">Have Logged In</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Accounts Table -->
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-bold text-gray-900 dark:text-white">All Accounts</h2>
        </div>

        <!-- Empty State -->
        <template x-if="accounts.length === 0">
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">No Accounts Yet</h3>
                <p class="text-gray-500 mb-6">Create orchestrator accounts to allow team members to scan participants.</p>
                <button @click="openCreateModal()"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create First Account
                </button>
            </div>
        </template>

        <!-- Accounts List -->
        <template x-if="accounts.length > 0">
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="account in accounts" :key="account.id">
                    <div
                        class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <!-- Account Info -->
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0"
                                :class="{
                                    'bg-gradient-to-br from-purple-500 to-purple-600': account.permission === 'super',
                                    'bg-gradient-to-br from-amber-500 to-amber-600': account.permission === 'high',
                                    'bg-gradient-to-br from-blue-500 to-blue-600': account.permission === 'normal'
                                }"
                                x-text="account.display_name.charAt(0).toUpperCase()">
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-gray-900 dark:text-white truncate"
                                        x-text="account.display_name"></h3>
                                    <span x-show="!account.active"
                                        class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 text-xs font-bold rounded-full">
                                        Inactive
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 font-mono" x-text="'@' + account.username"></p>
                            </div>
                        </div>

                        <!-- Permission Badge -->
                        <div class="flex items-center gap-4">
                            <span class="px-3 py-1.5 rounded-lg text-xs font-bold"
                                :class="{
                                    'bg-purple-100 dark:bg-purple-900/30 text-purple-600': account.permission === 'super',
                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-600': account.permission === 'high',
                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-600': account.permission === 'normal'
                                }">
                                <span x-text="account.permission.charAt(0).toUpperCase() + account.permission.slice(1)"></span>
                            </span>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button @click="openEditModal(account)"
                                    class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors"
                                    title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="confirmDelete(account)"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Details (collapsible) -->
                        <div x-show="account.permission === 'normal'" class="w-full sm:hidden text-sm text-gray-500">
                            <template x-if="account.allowed_actions?.length">
                                <p><span class="font-medium">Actions:</span>
                                    <span x-text="account.allowed_actions.join(', ')"></span>
                                </p>
                            </template>
                            <template x-if="account.allowed_locations?.length">
                                <p><span class="font-medium">Locations:</span>
                                    <span x-text="account.allowed_locations.join(', ')"></span>
                                </p>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showModal" x-cloak
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="closeModal()">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white"
                    x-text="editingAccount ? 'Edit Account' : 'Create Account'"></h2>
                <button @click="closeModal()"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="saveAccount()" class="p-6 space-y-5">
                <!-- Username -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Username</label>
                    <input type="text" x-model="formData.username" required :disabled="editingAccount"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        placeholder="e.g. john_doe">
                    <p class="text-xs text-gray-500 mt-1">Used for login. Cannot be changed after creation.</p>
                </div>

                <!-- Display Name -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Display Name</label>
                    <input type="text" x-model="formData.display_name" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none transition-all"
                        placeholder="e.g. John Doe">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        <span x-text="editingAccount ? 'New Password (leave blank to keep)' : 'Password'"></span>
                    </label>
                    <input type="password" x-model="formData.password" :required="!editingAccount"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:border-amber-500 focus:bg-white dark:focus:bg-gray-800 focus:outline-none transition-all"
                        placeholder="Enter password">
                </div>

                <!-- Permission Level -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Permission Level</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" @click="formData.permission = 'normal'"
                            class="p-3 rounded-xl border-2 text-center transition-all"
                            :class="formData.permission === 'normal' 
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' 
                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                            <div class="font-bold text-blue-600">Normal</div>
                            <div class="text-xs text-gray-500 mt-1">Pre-set only</div>
                        </button>
                        <button type="button" @click="formData.permission = 'high'"
                            class="p-3 rounded-xl border-2 text-center transition-all"
                            :class="formData.permission === 'high' 
                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/30' 
                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                            <div class="font-bold text-amber-600">High</div>
                            <div class="text-xs text-gray-500 mt-1">Select any</div>
                        </button>
                        <button type="button" @click="formData.permission = 'super'"
                            class="p-3 rounded-xl border-2 text-center transition-all"
                            :class="formData.permission === 'super' 
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' 
                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                            <div class="font-bold text-purple-600">Super</div>
                            <div class="text-xs text-gray-500 mt-1">Full control</div>
                        </button>
                    </div>
                </div>

                <!-- Normal Permission: Allowed Actions -->
                <div x-show="formData.permission === 'normal'" x-transition>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Allowed Actions</label>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach ($allActions as $action): 
                            $info = OrchestratorAccountRepository::getActionInfo($action);
                        ?>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-amber-300 cursor-pointer transition-colors">
                            <input type="checkbox" value="<?= $action ?>"
                                x-model="formData.allowed_actions"
                                class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($info['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Normal Permission: Allowed Locations -->
                <div x-show="formData.permission === 'normal'" x-transition>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Allowed Locations</label>
                    <div class="grid grid-cols-2 gap-2 max-h-52 overflow-y-auto pr-1 rounded-xl border border-gray-100 dark:border-gray-700 p-2 bg-gray-50 dark:bg-gray-900/50">
                        <?php foreach ($configuredStations as $station): ?>
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-amber-300 cursor-pointer transition-colors bg-white dark:bg-gray-800 flex-shrink-0">
                            <input type="checkbox" value="<?= htmlspecialchars($station['label']) ?>"
                                x-model="formData.allowed_locations"
                                class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 flex-shrink-0">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                <?= htmlspecialchars(($station['emoji'] ? $station['emoji'] . ' ' : '') . $station['label']) ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-1"><?= count($configuredStations) ?> locations available</p>
                </div>

                <!-- Active Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div>
                        <div class="font-bold text-gray-900 dark:text-white">Account Active</div>
                        <div class="text-sm text-gray-500">Inactive accounts cannot log in</div>
                    </div>
                    <button type="button" @click="formData.active = !formData.active"
                        class="relative w-12 h-7 rounded-full transition-colors"
                        :class="formData.active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'">
                        <span class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="formData.active ? 'translate-x-5' : ''"></span>
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="closeModal()"
                        class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-bold transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!saving" x-text="editingAccount ? 'Save Changes' : 'Create Account'"></span>
                        <span x-show="saving">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="showDeleteModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Delete Account?</h3>
                <p class="text-gray-500 mb-6">
                    Are you sure you want to delete <strong x-text="deletingAccount?.display_name"></strong>? 
                    This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = false"
                        class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-bold transition-colors">
                        Cancel
                    </button>
                    <button @click="deleteAccount()" :disabled="deleting"
                        class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50">
                        <span x-show="!deleting">Delete</span>
                        <span x-show="deleting">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function accountsManager() {
    return {
        accounts: <?= json_encode($accounts) ?>,
        showModal: false,
        showDeleteModal: false,
        editingAccount: null,
        deletingAccount: null,
        saving: false,
        deleting: false,
        formData: {
            username: '',
            display_name: '',
            password: '',
            permission: 'normal',
            allowed_actions: [],
            allowed_locations: [],
            active: true
        },
        
        config: {
            formId: <?= json_encode($activeFormId) ?>,
            csrfToken: <?= json_encode($csrfToken) ?>,
            apiUrl: '/admin/forms/orchestrator/api.php'
        },

        init() {
            // Nothing to init
        },

        openCreateModal() {
            this.editingAccount = null;
            this.formData = {
                username: '',
                display_name: '',
                password: '',
                permission: 'normal',
                allowed_actions: [],
                allowed_locations: [],
                active: true
            };
            this.showModal = true;
        },

        openEditModal(account) {
            this.editingAccount = account;
            this.formData = {
                username: account.username,
                display_name: account.display_name,
                password: '',
                permission: account.permission,
                allowed_actions: account.allowed_actions || [],
                allowed_locations: account.allowed_locations || [],
                active: account.active
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingAccount = null;
        },

        async saveAccount() {
            this.saving = true;

            try {
                const action = this.editingAccount ? 'update_account' : 'create_account';
                const payload = {
                    csrf_token: this.config.csrfToken,
                    form_id: this.config.formId,
                    ...this.formData
                };

                if (this.editingAccount) {
                    payload.account_id = this.editingAccount.id;
                }

                const response = await fetch(this.config.apiUrl + '?action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    if (this.editingAccount) {
                        // Update in list
                        const idx = this.accounts.findIndex(a => a.id === this.editingAccount.id);
                        if (idx !== -1) {
                            this.accounts[idx] = data.account;
                        }
                    } else {
                        // Add to list
                        this.accounts.push(data.account);
                    }
                    this.closeModal();
                    this.showToast(data.message || 'Account saved successfully', 'success');
                } else {
                    this.showToast(data.error || 'Failed to save account', 'error');
                }
            } catch (err) {
                console.error('Save error:', err);
                this.showToast('Failed to save account', 'error');
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(account) {
            this.deletingAccount = account;
            this.showDeleteModal = true;
        },

        async deleteAccount() {
            if (!this.deletingAccount) return;

            this.deleting = true;

            try {
                const response = await fetch(this.config.apiUrl + '?action=delete_account', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: this.config.csrfToken,
                        form_id: this.config.formId,
                        account_id: this.deletingAccount.id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.accounts = this.accounts.filter(a => a.id !== this.deletingAccount.id);
                    this.showDeleteModal = false;
                    this.deletingAccount = null;
                    this.showToast('Account deleted successfully', 'success');
                } else {
                    this.showToast(data.error || 'Failed to delete account', 'error');
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showToast('Failed to delete account', 'error');
            } finally {
                this.deleting = false;
            }
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-96 p-4 rounded-xl shadow-lg z-50 text-white font-bold text-center
                ${type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-gray-800'}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };
}
</script>
