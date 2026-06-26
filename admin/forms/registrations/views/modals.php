<!-- Add Registration Modal -->
<div id="addRegModal" class="hidden fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 overflow-y-auto"
    onclick="if(event.target === this) RegistrationsManager.closeModal('addRegModal')">
    <div class="min-h-screen flex items-center justify-center p-2 sm:p-4">
        <div
            class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-xl sm:rounded-2xl shadow-2xl overflow-hidden my-2 sm:my-4">
            <form id="addRegForm">
                <div class="bg-blue-600 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
                    <h3 class="text-lg sm:text-xl font-bold text-white">Add New Registration</h3>
                    <button type="button" onclick="RegistrationsManager.closeModal('addRegModal')"
                        class="text-white/80 hover:text-white p-1">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="addRegFields"
                    class="p-4 sm:p-6 space-y-3 sm:space-y-4 max-h-[65vh] sm:max-h-[60vh] overflow-y-auto">
                    <!-- Fields populated by JS -->
                </div>
                <div
                    class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 dark:bg-gray-900 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="RegistrationsManager.closeModal('addRegModal')"
                        class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold rounded-lg transition-colors text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors text-sm">
                        Create Registration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Registration Modal -->
<div id="editRegModal" class="hidden fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 overflow-y-auto"
    onclick="if(event.target === this) RegistrationsManager.closeModal('editRegModal')">
    <div class="min-h-screen flex items-center justify-center p-2 sm:p-4">
        <div
            class="bg-white dark:bg-gray-800 w-full max-w-3xl rounded-xl sm:rounded-2xl shadow-2xl overflow-hidden my-2 sm:my-4">
            <form id="editRegForm">
                <input type="hidden" name="id" id="editRegId">
                <div class="bg-blue-600 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
                    <h3 class="text-lg sm:text-xl font-bold text-white">Edit Registration</h3>
                    <button type="button" onclick="RegistrationsManager.closeModal('editRegModal')"
                        class="text-white/80 hover:text-white p-1">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="editRegFields" class="p-4 sm:p-6 space-y-4 max-h-[65vh] sm:max-h-[60vh] overflow-y-auto">
                    <!-- Fields populated by JS -->
                </div>
                <div
                    class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 dark:bg-gray-900 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="RegistrationsManager.closeModal('editRegModal')"
                        class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold rounded-lg transition-colors text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors text-sm">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Registration Modal (Read-only details) -->
<div id="viewRegModal" class="hidden fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 overflow-y-auto"
    onclick="if(event.target === this) RegistrationsManager.closeModal('viewRegModal')">
    <div class="min-h-screen flex items-center justify-center p-2 sm:p-4">
        <div
            class="bg-white dark:bg-gray-800 w-full max-w-3xl rounded-xl sm:rounded-2xl shadow-2xl overflow-hidden my-2 sm:my-4">
            <div class="bg-gray-700 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
                <h3 class="text-lg sm:text-xl font-bold text-white">Registration Details</h3>
                <button type="button" onclick="RegistrationsManager.closeModal('viewRegModal')"
                    class="text-white/80 hover:text-white p-1">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="viewRegContent" class="p-4 sm:p-6 max-h-[70vh] overflow-y-auto">
                <!-- Content populated by JS -->
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 dark:bg-gray-900 flex justify-end">
                <button type="button" onclick="RegistrationsManager.closeModal('viewRegModal')"
                    class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold rounded-lg transition-colors text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div id="deleteConfirmModal"
    class="hidden fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-xl shadow-2xl p-6">
        <div class="text-center">
            <div
                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Delete Registration?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">This action cannot be undone. The registration and
                all associated data will be permanently removed.</p>
            <div class="flex gap-3 justify-center">
                <button type="button" onclick="RegistrationsManager.closeModal('deleteConfirmModal')"
                    class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold rounded-lg transition-colors text-sm">
                    Cancel
                </button>
                <button type="button" id="confirmDeleteBtn"
                    class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-colors text-sm">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>