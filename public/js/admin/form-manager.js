/**
 * Form Manager - Alpine.js Component for Admin Form Builder
 * Extracted from admin/forms.php for better maintainability
 */
function formManager() {
    const defaultRegistrationSettings = {
        enabled: false,
        document_verification: false,
        document_verification_title: 'Verify Your Identity',
        ai_verification: false,
        payment_enabled: false,
        registration_fee: 150000,
        bank_accounts: [],
        offline_payment_location: '',
        offline_payment_hours: '',
        completion_message: 'Your registration is complete!',
        whatsapp_group_link: '',
        event_pdf_url: '',
        default_timeout: 900,
        document_timeout: 3600,
        payment_timeout: 7200
    };

    return {
        isEditing: false,
        form: {
            id: null,
            original_id: null,
            title: '',
            slug: '',
            description: '',
            quota: 0,
            context_type: 'standalone',
            context_id: '',
            steps: [],
            registration_settings: { ...defaultRegistrationSettings }
        },
        emptyForm: {
            id: null,
            original_id: null,
            title: '',
            slug: '',
            description: '',
            quota: 0,
            context_type: 'standalone',
            context_id: '',
            steps: [{ title: 'Step 1', fields: [] }],
            registration_settings: { ...defaultRegistrationSettings }
        },

        openEditor() {
            this.form = JSON.parse(JSON.stringify(this.emptyForm));
            this.isEditing = true;
        },

        editForm(formData) {
            this.form = JSON.parse(JSON.stringify(formData));
            this.form.original_id = formData.id;
            if (!this.form.steps) this.form.steps = [];
            if (!this.form.context_type) this.form.context_type = 'standalone';
            if (!this.form.registration_settings) {
                this.form.registration_settings = { ...defaultRegistrationSettings };
            } else {
                // Ensure bank_accounts array exists (Migration from legacy)
                if (!this.form.registration_settings.bank_accounts) {
                    this.form.registration_settings.bank_accounts = [];
                    if (this.form.registration_settings.bank_name || this.form.registration_settings.bank_account) {
                        this.form.registration_settings.bank_accounts.push({
                            bank_name: this.form.registration_settings.bank_name || '',
                            account_number: this.form.registration_settings.bank_account || '',
                            account_holder: this.form.registration_settings.account_holder || ''
                        });
                    }
                }
                this.form.registration_settings = { ...defaultRegistrationSettings, ...this.form.registration_settings };
            }
            this.isEditing = true;
        },

        addStep() {
            this.form.steps.push({ title: 'New Step', fields: [] });
        },

        removeStep(index) {
            if (confirm('Remove this step?')) {
                this.form.steps.splice(index, 1);
            }
        },

        addField(stepIndex) {
            this.form.steps[stepIndex].fields.push({
                name: '',
                label: 'New Field',
                placeholder: '',
                type: 'text',
                required: false
            });
        },

        removeField(stepIndex, fieldIndex) {
            this.form.steps[stepIndex].fields.splice(fieldIndex, 1);
        },

        generateName(field) {
            if (!field.name || field.name === field.label.toLowerCase().replace(/[^a-z0-9]/g, '_')) {
                field.name = field.label.toLowerCase().replace(/[^a-z0-9]/g, '_');
            }
        },

        getUrlPrefix() {
            if (this.form.context_type === 'standalone') return '/form/';
            if (this.form.context_type === 'event') return '/event/';
            return '/' + this.form.context_type + '/';
        },

        saveForm() {
            if (!this.form.title || !this.form.slug) {
                alert('All basic configuration fields are required.');
                return;
            }
            document.getElementById('formDataInput').value = JSON.stringify(this.form);
            document.getElementById('saveForm').submit();
        }
    }
}
