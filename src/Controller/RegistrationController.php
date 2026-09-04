<?php

require_once __DIR__ . '/../Core/Cookie.php';
require_once __DIR__ . '/../Core/CSRF.php';
require_once __DIR__ . '/../Core/Validator.php';
require_once __DIR__ . '/../Core/RegistrationStateMachine.php';
require_once __DIR__ . '/../Core/AIClient.php';
require_once __DIR__ . '/../Core/AssignmentService.php';
require_once __DIR__ . '/../Repository/RegistrationRepository.php';

/**
 * Registration Controller - Dynamic
 * Uses form configuration from forms.json for all steps and fields
 */
class RegistrationController
{
    private RegistrationRepository $repo;
    private array $forms = [];
    private array $settings = [];

    public function __construct()
    {
        $this->repo = new RegistrationRepository();
        $this->loadForms();
        $this->loadSettings();
    }

    private function loadForms(): void
    {
        $path = BASE_PATH . '/data/forms.json';
        $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $this->forms = $data['forms'] ?? $data;
    }

    private function loadSettings(): void
    {
        $settingsPath = BASE_PATH . '/data/settings.json';
        $formsPath = BASE_PATH . '/data/forms.json';

        // Try legacy settings.json first
        if (file_exists($settingsPath)) {
            $this->settings = json_decode(file_get_contents($settingsPath), true);
        } else {
            // Try new structure in forms.json
            $data = file_exists($formsPath) ? json_decode(file_get_contents($formsPath), true) : [];
            $this->settings = $data['settings'] ?? [];
        }
    }

    private function getFormBySlug(string $slug): ?array
    {
        foreach ($this->forms as $form) {
            if (($form['slug'] ?? '') === $slug || ($form['id'] ?? '') === $slug) {
                return $form;
            }
        }
        return null;
    }

    private function getFormById(string $id): ?array
    {
        foreach ($this->forms as $form) {
            if (($form['id'] ?? '') === $id) {
                return $form;
            }
        }
        return null;
    }

    /**
     * Show registration by form ID (for attached forms)
     */
    public function showByFormId(string $formId, string $basePath): void
    {
        $form = $this->getFormById($formId);

        if (!$form) {
            header('HTTP/1.0 404 Not Found');
            require BASE_PATH . '/views/404.php';
            exit;
        }

        // Store basePath in session for proper routing
        $_SESSION['registration_base_path'] = $basePath;
        $_SESSION['registration_form_id'] = $formId;

        $this->renderRegistration($form, $basePath);
    }

    /**
     * Main entry point - show registration page (standalone /register/{slug})
     */
    public function show(string $formSlug): void
    {
        $form = $this->getFormBySlug($formSlug);

        if (!$form) {
            header('HTTP/1.0 404 Not Found');
            require BASE_PATH . '/views/404.php';
            exit;
        }

        // Check if advanced registration is enabled for this form
        if (empty($form['registration_settings']['enabled'])) {
            // Fall back to simple form wizard
            require_once __DIR__ . '/FormController.php';
            (new FormController())->show($formSlug);
            return;
        }

        // Store basePath in session
        $basePath = '/register/' . ($form['slug'] ?? $formSlug);
        $_SESSION['registration_base_path'] = $basePath;
        $_SESSION['registration_form_id'] = $form['id'];

        $this->renderRegistration($form, $basePath);
    }

    /**
     * Core registration rendering logic
     */
    private function renderRegistration(array $form, string $basePath): void
    {
        // Handle restart parameter
        if (isset($_GET['restart'])) {
            Cookie::clear($form['id']);
        }

        $formId = $form['id'];
        $regId = Cookie::getOrCreate($formId);
        // Don't persist new registration until first step passes
        $registration = $this->repo->findOrCreate($formId, $regId, false);

        $stateMachine = new RegistrationStateMachine($form);

        // Opportunistic cleanup: expire ALL stale registrations for this form
        // MUST run BEFORE quota check so expired registrations don't count
        $this->expireStaleRegistrations($formId, $stateMachine);

        // Check quota BEFORE processing (only for new registrations at step 0)
        $quota = (int) ($form['quota'] ?? 0);
        if ($quota > 0 && ($registration['current_step'] ?? 0) === 0) {
            $currentCount = $this->repo->countForQuota($formId);
            if ($currentCount >= $quota) {
                $title = 'Registration Full';
                $heading = 'Registration Closed';
                $message = 'Sorry, the registration quota has been reached. Please check back later or contact the admin for more information.';
                $buttonText = 'Back to Home';
                $buttonLink = '/';
                // Pass form and quota for richer display
                require BASE_PATH . '/views/form-full.php';
                exit;
            }
        }

        // Check for expiration of current user's registration
        if ($stateMachine->isExpired($registration)) {
            // Use updateStatus to properly track status history
            $this->repo->updateStatus($formId, $registration['id'], 'expired');
            // Reload registration to get updated data
            $registration = $this->repo->find($formId, $registration['id']);
        }

        // Get current step info
        $currentStep = $registration['current_step'] ?? 0;
        $stepConfig = $stateMachine->getStepConfig($currentStep);
        $remainingTime = $stateMachine->getRemainingTime($registration);

        // Prepare view data
        $viewData = [
            'form' => $form,
            'registration' => $registration,
            'stepConfig' => $stepConfig,
            'currentStep' => $currentStep,
            'totalSteps' => $stateMachine->getTotalSteps(),
            'remainingTime' => $remainingTime,
            'settings' => array_merge($this->settings, $form['registration_settings'] ?? []),
            'csrfToken' => CSRF::generate(),
            'formSlug' => $form['id'], // Use ID for unique identification
            'basePath' => $basePath,   // Full path for form actions
            'stateMachine' => $stateMachine
        ];

        // Check if expired
        if (($registration['status'] ?? '') === 'expired') {
            $this->render('expired.php', $viewData);
            return;
        }

        // Check if rejected
        if (($registration['status'] ?? '') === 'rejected') {
            $this->render('rejected.php', $viewData);
            return;
        }

        // Check if failed
        if (($registration['status'] ?? '') === 'failed') {
            $this->render('failed.php', $viewData);
            return;
        }

        // Render appropriate view based on step type
        $stepType = $stepConfig['type'] ?? 'form';

        switch ($stepType) {
            case 'form':
                $this->render('step-form.php', $viewData);
                break;
            case 'document_verification':
                $this->render('step-document.php', $viewData);
                break;
            case 'payment':
                $this->render('step-payment.php', $viewData);
                break;
            case 'complete':
                $this->render('step-complete.php', $viewData);
                break;
            default:
                $this->render('step-form.php', $viewData);
        }
    }

    /**
     * Handle step submission
     */
    public function saveStep(string $formSlug, int $step): void
    {
        $form = $this->getFormBySlug($formSlug);
        if (!$form) {
            header('Location: /register/' . $formSlug);
            exit;
        }
        $this->processSaveStep($form, $step, '/register/' . $formSlug);
    }

    /**
     * Handle step submission by form ID (for attached forms)
     */
    public function saveStepByFormId(string $formId, int $step, string $basePath): void
    {
        $form = $this->getFormById($formId);
        if (!$form) {
            header('Location: ' . $basePath);
            exit;
        }
        $this->processSaveStep($form, $step, $basePath);
    }

    /**
     * Unified step submission processing (eliminates duplication)
     */
    private function processSaveStep(array $form, int $step, string $redirectPath): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirectPath);
            exit;
        }

        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_errors'] = ['general' => 'Invalid CSRF token'];
            header('Location: ' . $redirectPath);
            exit;
        }

        $formId = $form['id'];
        $regId = Cookie::get($formId);

        if (!$regId) {
            header('Location: ' . $redirectPath);
            exit;
        }

        $registration = $this->repo->find($formId, $regId);

        // For step 0, registration may not exist yet - create in memory
        if (!$registration && $step === 0) {
            $registration = $this->repo->findOrCreate($formId, $regId, false);
        } elseif (!$registration) {
            header('Location: ' . $redirectPath);
            exit;
        }

        $stateMachine = new RegistrationStateMachine($form);

        // Check expiration
        if ($stateMachine->isExpired($registration)) {
            $registration['status'] = 'expired';
            $this->repo->save($formId, $registration);
            header('Location: ' . $redirectPath);
            exit;
        }

        // Get step config
        $stepConfig = $stateMachine->getStepConfig($step);
        $stepType = $stepConfig['type'] ?? 'form';

        // Validate and save based on step type
        $errors = [];

        if ($stepType === 'form') {
            $errors = $this->validateFormStep($stepConfig['step']['fields'] ?? [], $_POST);

            if (empty($errors)) {
                // Save field data
                $data = [];
                foreach ($stepConfig['step']['fields'] ?? [] as $field) {
                    $name = $field['name'];
                    $value = $_POST[$name] ?? '';

                    // Normalize phone numbers
                    if ($field['type'] === 'tel') {
                        $value = Validator::normalizePhone($value);
                    }

                    $data[$name] = $value;
                }

                // For first step (step 0), create the registration for the first time
                if ($step === 0) {
                    // ATOMIC QUOTA CHECK with file locking to prevent race conditions
                    $quota = (int) ($form['quota'] ?? 0);
                    if ($quota > 0) {
                        $lockFile = BASE_PATH . '/data/registrations/' . $formId . '/.quota.lock';
                        $lockDir = dirname($lockFile);
                        if (!is_dir($lockDir)) {
                            mkdir($lockDir, 0755, true);
                        }

                        $fp = fopen($lockFile, 'c+');
                        if (!$fp || !flock($fp, LOCK_EX)) {
                            if ($fp)
                                fclose($fp);
                            $_SESSION['flash_errors'] = ['general' => 'System is busy. Please try again.'];
                            header('Location: ' . $redirectPath);
                            exit;
                        }

                        try {
                            // Expire stale registrations before quota recheck
                            $this->expireStaleRegistrations($formId, $stateMachine);

                            // Re-check quota with lock held
                            $currentCount = $this->repo->countForQuota($formId);
                            if ($currentCount >= $quota) {
                                flock($fp, LOCK_UN);
                                fclose($fp);

                                // Show form-full page
                                $title = 'Registration Full';
                                $heading = 'Registration Closed';
                                $message = 'Sorry, the registration quota was just filled. We cannot accept any more registrations at this time.';
                                $buttonText = 'Back to Home';
                                $buttonLink = '/';
                                require BASE_PATH . '/views/form-full.php';
                                exit;
                            }

                            // Quota OK - save registration
                            $registration['data'] = $data;
                            // Find next step after form step 0 (could be form 1, document_verification, payment, or complete)
                            $nextStep = $stateMachine->getStepConfig(1);
                            $registration['current_step'] = $nextStep ? 1 : $stateMachine->findStepIndexByType('complete') ?? 1;
                            $this->repo->save($formId, $registration);

                        } finally {
                            flock($fp, LOCK_UN);
                            fclose($fp);
                        }
                    } else {
                        // No quota limit - save normally
                        $registration['data'] = $data;
                        // Find next step after form step 0 (could be form 1, document_verification, payment, or complete)
                        $nextStep = $stateMachine->getStepConfig(1);
                        $registration['current_step'] = $nextStep ? 1 : $stateMachine->findStepIndexByType('complete') ?? 1;
                        $this->repo->save($formId, $registration);
                    }

                    // Check if next step is document verification - set status
                    $nextStepConfig = $stateMachine->getStepConfig(1);
                    if (($nextStepConfig['type'] ?? '') === 'document_verification') {
                        $this->repo->updateStatus($formId, $regId, 'pending_document');
                    }
                } else {
                    $this->repo->updateData($formId, $regId, $data);
                    // Advance to next step (explicit lookup)
                    $registration = $this->repo->find($formId, $regId);
                    $nextStepIndex = $step + 1;
                    // Validate that next step exists, otherwise go to complete
                    $registration['current_step'] = $stateMachine->getStepConfig($nextStepIndex)
                        ? $nextStepIndex
                        : ($stateMachine->findStepIndexByType('complete') ?? $nextStepIndex);
                    $this->repo->save($formId, $registration);

                    // Check if next step is document verification - set status
                    $nextStepConfig = $stateMachine->getStepConfig($step + 1);
                    if (($nextStepConfig['type'] ?? '') === 'document_verification') {
                        $this->repo->updateStatus($formId, $regId, 'pending_document');
                    }
                }
            }
        } elseif ($stepType === 'payment') {
            $paymentMethod = $_POST['payment_method'] ?? '';
            $currentStatus = $registration['status'] ?? '';

            // Don't allow status changes if already in a terminal/verified state
            // This prevents verified registrations from being overwritten when user revisits
            if (in_array($currentStatus, ['verified', 'rejected', 'failed', 'expired'])) {
                header('Location: ' . $redirectPath);
                exit;
            }

            if (empty($paymentMethod)) {
                $errors['payment_method'] = 'Please select a payment method';
            } else {
                // Validate that selected payment method is enabled
                $regSettings = $form['registration_settings'] ?? [];
                $onlineEnabled = $regSettings['online_payment_enabled'] ?? true;
                $offlineEnabled = $regSettings['offline_payment_enabled'] ?? true;

                // Auto-disable offline if deadline passed
                $offlineDeadline = $regSettings['offline_payment_deadline'] ?? null;
                if ($offlineDeadline && time() > strtotime($offlineDeadline)) {
                    $offlineEnabled = false;
                }

                if ($paymentMethod === 'Online' && !$onlineEnabled) {
                    $errors['payment_method'] = 'Online payment is not available';
                } elseif ($paymentMethod === 'Offline' && !$offlineEnabled) {
                    $errors['payment_method'] = 'Offline payment is no longer available';
                } else {
                    $this->repo->updateData($formId, $regId, ['payment_method' => $paymentMethod]);

                    if ($paymentMethod === 'Offline') {
                        // Mark as pending offline payment - stay on payment step
                        $this->repo->updateStatus($formId, $regId, 'pending_offline');
                    } else {
                        // Online payment - waiting for payment upload
                        $this->repo->updateStatus($formId, $regId, 'pending_online');
                    }
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old'] = $_POST;
        }

        header('Location: ' . $redirectPath);
        exit;
    }

    /**
     * Handle file uploads by form ID (for attached forms)
     */
    public function uploadByFormId(string $formId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $form = $this->getFormById($formId);
        if (!$form) {
            $this->jsonResponse(['success' => false, 'error' => 'Form not found']);
            return;
        }

        $regId = Cookie::get($formId);

        if (!$regId) {
            $this->jsonResponse(['success' => false, 'error' => 'Registration not found']);
            return;
        }

        $registration = $this->repo->find($formId, $regId);
        $fieldName = $_POST['field'] ?? '';
        $stateMachine = new RegistrationStateMachine($form);

        // Check if registration is expired before allowing upload
        if ($stateMachine->isExpired($registration)) {
            $registration['status'] = 'expired';
            $this->repo->save($formId, $registration);
            $this->jsonResponse(['success' => false, 'error' => 'Registration has expired']);
            return;
        }

        $currentStep = $registration['current_step'] ?? 0;
        $stepConfig = $stateMachine->getStepConfig($currentStep);
        $stepType = $stepConfig['type'] ?? 'form';

        // Handle document verification
        if ($stepType === 'document_verification' && $fieldName === 'student_id_photo') {
            $this->handleDocumentUpload($form, $formId, $regId, $registration, $stateMachine);
        }
        // Handle payment proof
        elseif ($stepType === 'payment' && $fieldName === 'payment_proof') {
            $this->handlePaymentUpload($form, $formId, $regId, $registration, $stateMachine);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid upload context']);
        }
    }

    /**
     * Handle file uploads (document, payment proof)
     */
    public function upload(string $formSlug): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $form = $this->getFormBySlug($formSlug);
        if (!$form) {
            $this->jsonResponse(['success' => false, 'error' => 'Form not found']);
            return;
        }

        $formId = $form['id'];
        $regId = Cookie::get($formId);

        if (!$regId) {
            $this->jsonResponse(['success' => false, 'error' => 'Registration not found']);
            return;
        }

        $registration = $this->repo->find($formId, $regId);
        $fieldName = $_POST['field'] ?? '';
        $stateMachine = new RegistrationStateMachine($form);

        // Check if registration is expired before allowing upload
        if ($stateMachine->isExpired($registration)) {
            $registration['status'] = 'expired';
            $this->repo->save($formId, $registration);
            $this->jsonResponse(['success' => false, 'error' => 'Registration has expired']);
            return;
        }

        $currentStep = $registration['current_step'] ?? 0;
        $stepConfig = $stateMachine->getStepConfig($currentStep);
        $stepType = $stepConfig['type'] ?? 'form';

        // Handle document verification
        if ($stepType === 'document_verification' && $fieldName === 'student_id_photo') {
            $this->handleDocumentUpload($form, $formId, $regId, $registration, $stateMachine);
        }
        // Handle payment proof
        elseif ($stepType === 'payment' && $fieldName === 'payment_proof') {
            $this->handlePaymentUpload($form, $formId, $regId, $registration, $stateMachine);
        } else {
            error_log("upload: Invalid context - stepType=$stepType, fieldName=$fieldName");
            $this->jsonResponse(['success' => false, 'error' => 'Invalid upload context']);
        }
    }

    private function handleDocumentUpload(array $form, string $formId, string $regId, array $registration, RegistrationStateMachine $stateMachine): void
    {
        $uploadResult = $this->repo->handleUpload($formId, $regId, 'student_id_photo');

        if (!$uploadResult['success']) {
            error_log("handleDocumentUpload: File upload failed - " . ($uploadResult['error'] ?? 'Unknown error'));
            $this->jsonResponse(['success' => false, 'error' => $uploadResult['error'] ?? 'Upload failed']);
            return;
        }

        $filePath = $uploadResult['path'];
        $this->repo->updateData($formId, $regId, ['student_id_photo' => $filePath]);

        // Run AI verification if configured
        $regSettings = $form['registration_settings'] ?? [];

        if (!empty($this->settings['ai_api_key']) && !empty($regSettings['ai_verification'])) {
            $extraParams = !empty($this->settings['ai_extra_params']) ? json_decode($this->settings['ai_extra_params'], true) : [];
            $gemini = new AIClient(
                $this->settings['ai_api_key'],
                $this->settings['ai_api_url'] ?? 'https://api.openai.com/v1',
                $this->settings['ai_model'] ?? 'gpt-4o',
                $extraParams ?: []
            );
            $userData = $registration['data'] ?? [];
            $result = $gemini->verifyStudentId($filePath, $userData);

            // Refresh registration and update all fields atomically
            $registration = $this->repo->find($formId, $regId);

            // Store AI verification result directly
            $registration['ai_verification']['student_id'] = [
                'result' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            if ($result['valid'] ?? false) {
                // Reset attempts on success and advance to next step (payment or complete)
                $paymentStep = $stateMachine->findStepIndexByType('payment');
                $completeStep = $stateMachine->findStepIndexByType('complete');
                $registration['current_step'] = $paymentStep ?? $completeStep ?? ($registration['current_step'] + 1);
                $registration['last_activity'] = date('Y-m-d H:i:s');
                $registration['data']['document_attempts'] = 0;
                $this->repo->save($formId, $registration);

                $this->jsonResponse([
                    'success' => true,
                    'verified' => true,
                    'message' => 'Document verified successfully',
                    'result' => $result
                ]);
            } else {
                // Increment attempt counter
                $attempts = ($registration['data']['document_attempts'] ?? 0) + 1;
                $registration['data']['document_attempts'] = $attempts;
                $this->repo->save($formId, $registration);

                // Check if max attempts reached (2 failures = failed)
                if ($attempts >= 2) {
                    $this->repo->updateStatus($formId, $regId, 'failed');
                    $this->jsonResponse([
                        'success' => true,
                        'verified' => false,
                        'failed' => true,
                        'message' => 'Maximum verification attempts reached. Registration failed.',
                        'result' => $result
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => true,
                        'verified' => false,
                        'attemptsRemaining' => 2 - $attempts,
                        'message' => 'Verification failed. You have ' . (2 - $attempts) . ' attempt(s) remaining.',
                        'result' => $result
                    ]);
                }
            }
        } else {
            // No AI - auto advance to next step (payment or complete)
            $registration = $this->repo->find($formId, $regId);
            $paymentStep = $stateMachine->findStepIndexByType('payment');
            $completeStep = $stateMachine->findStepIndexByType('complete');
            $registration['current_step'] = $paymentStep ?? $completeStep ?? ($registration['current_step'] + 1);
            $registration['last_activity'] = date('Y-m-d H:i:s');
            $this->repo->save($formId, $registration);

            $this->jsonResponse([
                'success' => true,
                'verified' => true,
                'message' => 'Document uploaded'
            ]);
        }
    }

    private function handlePaymentUpload(array $form, string $formId, string $regId, array $registration, RegistrationStateMachine $stateMachine): void
    {
        $uploadResult = $this->repo->handleUpload($formId, $regId, 'payment_proof');

        if (!$uploadResult['success']) {
            $this->jsonResponse(['success' => false, 'error' => $uploadResult['error'] ?? 'Upload failed']);
            return;
        }

        $filePath = $uploadResult['path'];
        $this->repo->updateData($formId, $regId, ['payment_proof' => $filePath]);

        $regSettings = $form['registration_settings'] ?? [];

        // Run AI verification if configured
        if (!empty($this->settings['ai_api_key']) && !empty($regSettings['ai_verification'])) {
            $extraParams = !empty($this->settings['ai_extra_params']) ? json_decode($this->settings['ai_extra_params'], true) : [];
            $gemini = new AIClient(
                $this->settings['ai_api_key'],
                $this->settings['ai_api_url'] ?? 'https://api.openai.com/v1',
                $this->settings['ai_model'] ?? 'gpt-4o',
                $extraParams ?: []
            );

            $paymentInfo = [
                'amount' => $regSettings['registration_fee'] ?? $this->settings['registration_fee'] ?? 0,
                'bank_accounts' => $regSettings['bank_accounts'] ?? $this->settings['bank_accounts'] ?? []
            ];

            $result = $gemini->verifyPaymentScreenshot($filePath, $paymentInfo);

            // Refresh registration and update all fields atomically
            $registration = $this->repo->find($formId, $regId);

            // Store AI verification result directly
            $registration['ai_verification']['payment'] = [
                'result' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            if ($result['valid'] ?? false) {
                // Reset attempts on success - advance to completion step
                $registration['data']['payment_attempts'] = 0;
                $completeStep = $stateMachine->findStepIndexByType('complete');
                $registration['current_step'] = $completeStep ?? ($registration['current_step'] + 1);
                $this->repo->save($formId, $registration);
                $this->repo->updateStatus($formId, $regId, 'verified');

                // Auto-assign class and gate (same as admin approval)
                $assignmentSettings = $regSettings['assignment_settings'] ?? [];
                if (!empty($assignmentSettings['enabled'])) {
                    $assignmentService = new AssignmentService($this->repo);
                    $assignmentService->assignClassAndGate(
                        $formId,
                        $regId,
                        $assignmentSettings,
                        $registration['data'] ?? []
                    );
                }

                $this->jsonResponse([
                    'success' => true,
                    'verified' => true,
                    'message' => 'Payment verified successfully'
                ]);
            } else {
                // Increment attempt counter
                $attempts = ($registration['data']['payment_attempts'] ?? 0) + 1;
                $registration['data']['payment_attempts'] = $attempts;
                $this->repo->save($formId, $registration);

                if ($attempts >= 2) {
                    $this->repo->updateStatus($formId, $regId, 'failed');
                    $this->jsonResponse([
                        'success' => true,
                        'verified' => false,
                        'failed' => true,
                        'message' => 'Maximum verification attempts reached. Registration failed.'
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => true,
                        'verified' => false,
                        'attemptsRemaining' => 2 - $attempts,
                        'message' => 'Payment verification failed. You have ' . (2 - $attempts) . ' attempt(s) remaining.'
                    ]);
                }
            }
        } else {
            // No AI - mark as verified and advance to completion step
            $this->repo->updateStatus($formId, $regId, 'verified'); // Transition out of pending_online
            $registration = $this->repo->find($formId, $regId);
            $completeStep = $stateMachine->findStepIndexByType('complete');
            $registration['current_step'] = $completeStep ?? ($registration['current_step'] + 1);
            $this->repo->save($formId, $registration);

            // Auto-assign class and gate (same as admin approval)
            $assignmentSettings = $regSettings['assignment_settings'] ?? [];
            if (!empty($assignmentSettings['enabled'])) {
                $assignmentService = new AssignmentService($this->repo);
                $assignmentService->assignClassAndGate(
                    $formId,
                    $regId,
                    $assignmentSettings,
                    $registration['data'] ?? []
                );
            }

            $this->jsonResponse([
                'success' => true,
                'verified' => true,
                'message' => 'Payment uploaded'
            ]);
        }
    }

    /**
     * Auto-save partial form data (AJAX)
     */
    public function autoSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false]);
            return;
        }

        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $formSlug = $_POST['form_id'] ?? '';
        $form = $this->getFormBySlug($formSlug);

        if (!$form) {
            $this->jsonResponse(['success' => false]);
            return;
        }

        $formId = $form['id'];
        $regId = Cookie::get($formId);

        if (!$regId) {
            $this->jsonResponse(['success' => false]);
            return;
        }

        $registration = $this->repo->find($formId, $regId);

        // Don't auto-save if registration hasn't been persisted yet (first step not passed)
        if (!$registration) {
            $this->jsonResponse(['success' => false, 'message' => 'Registration not yet created']);
            return;
        }

        // Get all fields from all form steps
        $allFields = [];
        foreach ($form['steps'] ?? [] as $step) {
            foreach ($step['fields'] ?? [] as $field) {
                $allFields[] = $field['name'];
            }
        }

        $updates = [];
        foreach ($allFields as $fieldName) {
            if (isset($_POST[$fieldName]) && $_POST[$fieldName] !== '') {
                $updates[$fieldName] = $_POST[$fieldName];
            }
        }

        if (!empty($updates)) {
            // Only save data, do NOT update last_activity (no timer reset)
            $this->repo->updateData($formId, $regId, $updates);
        }

        // Return success without refreshing timer
        $this->jsonResponse([
            'success' => true
        ]);
    }

    /**
     * Check verification status (polling endpoint)
     */
    public function checkStatus(string $formSlug): void
    {
        $form = $this->getFormBySlug($formSlug);

        if (!$form) {
            $this->jsonResponse(['success' => false]);
            return;
        }

        $formId = $form['id'];
        $regId = Cookie::get($formId);

        if (!$regId) {
            $this->jsonResponse(['success' => false]);
            return;
        }

        $registration = $this->repo->find($formId, $regId);

        if (!$registration) {
            $this->jsonResponse(['success' => false]);
            return;
        }

        $stateMachine = new RegistrationStateMachine($form);
        $remaining = $stateMachine->getRemainingTime($registration);
        $isExpired = $stateMachine->isExpired($registration);

        $this->jsonResponse([
            'success' => true,
            'status' => $registration['status'] ?? 'pending',
            'currentStep' => $registration['current_step'] ?? 0,
            'remainingTime' => $remaining,
            'isExpired' => $isExpired
        ]);
    }

    private function validateFormStep(array $fields, array $data): array
    {
        $validator = new Validator($data);

        foreach ($fields as $field) {
            $name = $field['name'];
            $label = $field['label'] ?? $name;
            $type = $field['type'] ?? 'text';
            $required = $field['required'] ?? false;

            if ($required) {
                $validator->required($name, $label);
            }

            if ($type === 'email' && !empty($data[$name])) {
                $validator->email($name, $label);
            }

            if ($type === 'tel' && !empty($data[$name])) {
                $validator->phone($name, $label);
            }
        }

        return $validator->errors();
    }

    private function render(string $viewFile, array $data): void
    {
        extract($data);
        require BASE_PATH . '/views/registration/' . $viewFile;
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Expire all stale registrations for a form (opportunistic cleanup)
     * Called whenever any user visits the registration page
     */
    private function expireStaleRegistrations(string $formId, RegistrationStateMachine $stateMachine): void
    {
        $allRegistrations = $this->repo->getAllForForm($formId);

        foreach ($allRegistrations as $reg) {
            // Skip already-terminal statuses
            $status = $reg['status'] ?? '';
            if (in_array($status, ['expired', 'verified', 'rejected', 'failed'])) {
                continue;
            }

            // Check if this registration has expired
            if ($stateMachine->isExpired($reg)) {
                // Use updateStatus to properly track status history
                $this->repo->updateStatus($formId, $reg['id'], 'expired');
            }
        }
    }
}
