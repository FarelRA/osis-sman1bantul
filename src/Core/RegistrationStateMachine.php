<?php

/**
 * Registration State Machine - Dynamic
 * Reads step configuration from form's steps array
 * Uses simple per-step timeout configuration
 */
class RegistrationStateMachine
{
    // Default timeouts (in seconds)
    public const DEFAULT_STEP_TIMEOUT = 900;      // 15 minutes
    public const DEFAULT_DOCUMENT_TIMEOUT = 3600; // 1 hour
    public const DEFAULT_PAYMENT_TIMEOUT = 7200;  // 2 hours

    private array $form;
    private array $steps;

    public function __construct(array $form)
    {
        $this->form = $form;
        $this->steps = $form['steps'] ?? [];
    }

    /**
     * Get total number of steps (form steps + verification + payment + complete)
     */
    public function getTotalSteps(): int
    {
        $count = count($this->steps);

        // Add document verification step if enabled
        if ($this->hasDocumentVerification()) {
            $count++;
        }

        // Add payment step if enabled  
        if ($this->hasPayment()) {
            $count++;
        }

        // Add completion step
        $count++;

        return $count;
    }

    /**
     * Find step index by type
     * @param string $type One of: 'form', 'document_verification', 'payment', 'complete'
     * @param int $formStepIndex For 'form' type, which form step (0-indexed within form steps)
     * @return int|null Step index or null if not found
     */
    public function findStepIndexByType(string $type, int $formStepIndex = 0): ?int
    {
        for ($i = 0; $i < $this->getTotalSteps(); $i++) {
            $config = $this->getStepConfig($i);
            if ($config && $config['type'] === $type) {
                // For form type, check if this is the right form step
                if ($type === 'form') {
                    if ($i === $formStepIndex) {
                        return $i;
                    }
                } else {
                    // For non-form types (document_verification, payment, complete), return first match
                    return $i;
                }
            }
        }
        return null;
    }

    /**
     * Get the index of the step that comes after a given type
     * Useful for advancing to the next logical step
     */
    public function getNextStepAfterType(string $currentType): ?int
    {
        $currentIndex = $this->findStepIndexByType($currentType);
        if ($currentIndex !== null && $currentIndex + 1 < $this->getTotalSteps()) {
            return $currentIndex + 1;
        }
        return null;
    }

    /**
     * Check if form has document verification enabled
     */
    public function hasDocumentVerification(): bool
    {
        return !empty($this->form['registration_settings']['document_verification']);
    }

    /**
     * Check if form has payment enabled
     */
    public function hasPayment(): bool
    {
        return !empty($this->form['registration_settings']['payment_enabled']);
    }

    /**
     * Get timeout for any step by index (uses per-step config)
     */
    public function getStepTimeout(int $stepIndex): int
    {
        $stepConfig = $this->getStepConfig($stepIndex);
        $stepType = $stepConfig['type'] ?? 'form';
        $settings = $this->form['registration_settings'] ?? [];

        // Check for per-step configuration first
        $stepTimeouts = $settings['step_timeouts'] ?? [];
        if (isset($stepTimeouts[$stepIndex])) {
            return (int) $stepTimeouts[$stepIndex];
        }

        // Step 0 (first form step) has no timeout
        if ($stepIndex === 0) {
            return 0;
        }

        // Use type-specific defaults
        return match ($stepType) {
            'document_verification' => (int) ($settings['document_timeout'] ?? self::DEFAULT_DOCUMENT_TIMEOUT),
            'payment' => (int) ($settings['payment_timeout'] ?? self::DEFAULT_PAYMENT_TIMEOUT),
            'complete' => 0, // No timeout on completion step
            default => (int) ($settings['default_timeout'] ?? self::DEFAULT_STEP_TIMEOUT)
        };
    }

    /**
     * Check if registration has expired based on current step
     */
    public function isExpired(array $registration): bool
    {
        $status = $registration['status'] ?? '';

        // Offline payment uses deadline datetime instead of timeout
        if ($status === 'pending_offline') {
            $settings = $this->form['registration_settings'] ?? [];
            $deadline = $settings['offline_payment_deadline']
                ?? date('Y-m-d H:i', strtotime(($registration['created_at'] ?? 'now') . ' +15 days'));
            // Expired only if current time > deadline
            return time() > strtotime($deadline);
        }

        $lastActivity = $registration['last_activity'] ?? $registration['created_at'] ?? null;
        if (!$lastActivity) {
            return false;
        }

        $currentStep = $registration['current_step'] ?? 0;
        $timeout = $this->getStepTimeout($currentStep);

        if ($timeout === 0) {
            return false; // No timeout for this step
        }

        return (time() - strtotime($lastActivity)) > $timeout;
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTime(array $registration): ?int
    {
        $status = $registration['status'] ?? '';

        // Offline payment: no countdown timer (uses deadline date)
        if ($status === 'pending_offline') {
            return null;
        }

        $lastActivity = $registration['last_activity'] ?? $registration['created_at'] ?? null;
        if (!$lastActivity) {
            return null;
        }

        $currentStep = $registration['current_step'] ?? 0;
        $timeout = $this->getStepTimeout($currentStep);

        if ($timeout === 0) {
            return null; // No timeout for this step
        }

        return max(0, $timeout - (time() - strtotime($lastActivity)));
    }

    /**
     * Get step configuration by index
     */
    public function getStepConfig(int $stepIndex): ?array
    {
        // Regular form steps
        if ($stepIndex < count($this->steps)) {
            return [
                'type' => 'form',
                'step' => $this->steps[$stepIndex],
                'index' => $stepIndex
            ];
        }

        $offset = count($this->steps);

        // Document verification step
        if ($this->hasDocumentVerification()) {
            if ($stepIndex === $offset) {
                return [
                    'type' => 'document_verification',
                    'title' => $this->form['registration_settings']['document_verification_title'] ?? 'Document Verification',
                    'index' => $stepIndex
                ];
            }
            $offset++;
        }

        // Payment step
        if ($this->hasPayment()) {
            if ($stepIndex === $offset) {
                return [
                    'type' => 'payment',
                    'title' => $this->form['registration_settings']['payment_title'] ?? 'Payment',
                    'index' => $stepIndex
                ];
            }
            $offset++;
        }

        // Completion step
        if ($stepIndex === $offset) {
            return [
                'type' => 'complete',
                'title' => 'Registration Complete',
                'index' => $stepIndex
            ];
        }

        return null;
    }

    /**
     * Get current step type
     */
    public function getCurrentStepType(int $stepIndex): string
    {
        $config = $this->getStepConfig($stepIndex);
        return $config['type'] ?? 'unknown';
    }

    /**
     * Check if can advance to next step
     */
    public function canAdvance(array $registration): bool
    {
        $currentStep = $registration['current_step'] ?? 0;
        return $currentStep < $this->getTotalSteps() - 1;
    }

    /**
     * Get fields for current step
     */
    public function getStepFields(int $stepIndex): array
    {
        if ($stepIndex < count($this->steps)) {
            return $this->steps[$stepIndex]['fields'] ?? [];
        }
        return [];
    }

    /**
     * Get all form steps (not including special steps)
     */
    public function getFormSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get registration settings
     */
    public function getSettings(): array
    {
        return $this->form['registration_settings'] ?? [];
    }
}
