<?php

/**
 * Registration Repository
 * Handles storage and retrieval of registration data
 */
class RegistrationRepository
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = BASE_PATH . '/data/registrations';
        $this->ensureDirectories();
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectories(): void
    {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
        if (!is_dir($this->basePath . '/uploads')) {
            mkdir($this->basePath . '/uploads', 0755, true);
        }
    }

    /**
     * Get path for a form's registrations directory
     */
    private function getFormPath(string $formId): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $formId);
        $path = $this->basePath . '/' . $safeId;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    /**
     * Get path for a specific registration file
     */
    private function getFilePath(string $formId, string $regId): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $regId);
        return $this->getFormPath($formId) . '/' . $safeId . '.json';
    }

    /**
     * Find or create a registration
     */
    /**
     * Find or create a registration
     * @param bool $persist If false, new registrations are not saved to disk (for first step validation)
     */
    public function findOrCreate(string $formId, string $regId, bool $persist = true): array
    {
        $existing = $this->find($formId, $regId);

        if ($existing) {
            return $existing;
        }

        // Create new registration
        $registration = [
            'id' => $regId,
            'form_id' => $formId,
            'status' => 'pending_form',
            'current_step' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s'),
            'data' => [],
            'ai_verification' => [],
            'status_history' => [
                [
                    'to' => 'pending_form',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]
        ];

        // Only persist if requested (allows first-step-validation before saving)
        if ($persist) {
            $this->save($formId, $registration);
        }

        return $registration;
    }

    /**
     * Find a registration by ID
     */
    public function find(string $formId, string $regId): ?array
    {
        $path = $this->getFilePath($formId, $regId);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    /**
     * Save registration data with file locking
     */
    public function save(string $formId, array $registration): bool
    {
        $regId = $registration['id'];
        $path = $this->getFilePath($formId, $regId);

        $registration['last_activity'] = date('Y-m-d H:i:s');

        $fp = fopen($path, 'c+');
        if (!$fp)
            return false;

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }

        try {
            $jsonData = json_encode($registration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($jsonData === false) {
                error_log("Failed to encode registration data for {$formId}/{$regId}: " . json_last_error_msg());
                return false;
            }

            ftruncate($fp, 0);
            rewind($fp);
            if (fwrite($fp, $jsonData) === false) {
                return false;
            }
            fflush($fp);
            return true;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Update specific fields in registration data
     */
    public function updateData(string $formId, string $regId, array $data): bool
    {
        $registration = $this->find($formId, $regId);

        if (!$registration) {
            return false;
        }

        $registration['data'] = array_merge($registration['data'] ?? [], $data);
        return $this->save($formId, $registration);
    }

    /**
     * Update registration status (simplified - no special timer handling)
     */
    public function updateStatus(string $formId, string $regId, string $newStatus): bool
    {
        $registration = $this->find($formId, $regId);

        if (!$registration) {
            return false;
        }

        $oldStatus = $registration['status'];
        $now = date('Y-m-d H:i:s');

        // Simple status update - reset timer on status change
        $registration['status'] = $newStatus;
        $registration['last_activity'] = $now;
        $registration['status_history'][] = [
            'from' => $oldStatus,
            'to' => $newStatus,
            'timestamp' => $now
        ];

        return $this->save($formId, $registration);
    }

    /**
     * Store AI verification result
     */
    public function storeAIVerification(string $formId, string $regId, string $type, array $result): bool
    {
        $registration = $this->find($formId, $regId);

        if (!$registration) {
            return false;
        }

        $registration['ai_verification'][$type] = [
            'result' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->save($formId, $registration);
    }

    /**
     * Get all registrations for a form
     */
    public function getAllForForm(string $formId): array
    {
        $path = $this->getFormPath($formId);
        $registrations = [];

        foreach (glob($path . '/*.json') as $file) {
            $content = file_get_contents($file);
            $reg = json_decode($content, true);
            if ($reg) {
                $registrations[] = $reg;
            }
        }

        // Sort by created_at descending
        usort($registrations, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $registrations;
    }

    /**
     * Get registrations by status
     */
    public function getByStatus(string $formId, string $status): array
    {
        $all = $this->getAllForForm($formId);
        return array_filter($all, fn($r) => $r['status'] === $status);
    }

    /**
     * Get upload path for a registration
     */
    public function getUploadPath(string $formId, string $regId): string
    {
        $path = $this->basePath . '/uploads/' . $formId . '/' . $regId;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    /**
     * Handle file upload for a registration
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public function handleUpload(string $formId, string $regId, string $fieldName): array
    {
        if (empty($_FILES[$fieldName]['name'])) {
            return ['success' => false, 'path' => null, 'error' => 'No file selected'];
        }

        // Check for PHP upload errors
        $uploadError = $_FILES[$fieldName]['error'] ?? UPLOAD_ERR_OK;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File is too large. Server limit is ' . ini_get('upload_max_filesize') . '. Please reduce file size or contact admin.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Server misconfiguration: missing temp folder',
                UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write file',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension'
            ];
            $errorMessage = $errorMessages[$uploadError] ?? 'Unknown upload error';
            error_log("handleUpload: PHP error $uploadError - $errorMessage");
            return ['success' => false, 'path' => null, 'error' => $errorMessage];
        }

        // Validate file size (2MB limit to match PHP default)
        if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'path' => null, 'error' => 'File is too large. Maximum size is 5MB.'];
        }

        // Validate MIME type (more secure than extension check)
        if (empty($_FILES[$fieldName]['tmp_name'])) {
            return ['success' => false, 'path' => null, 'error' => 'Temporary file not found'];
        }

        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES[$fieldName]['tmp_name']);
        } catch (Throwable $e) {
            error_log("finfo error: " . $e->getMessage());
            return ['success' => false, 'path' => null, 'error' => 'Could not verify file type'];
        }
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf'
        ];

        if (!isset($allowedMimes[$mimeType])) {
            return ['success' => false, 'path' => null, 'error' => 'Invalid file type. Please upload JPG, PNG, WebP, or PDF.'];
        }

        $uploadPath = $this->getUploadPath($formId, $regId);
        $ext = $allowedMimes[$mimeType]; // Use extension based on actual MIME type

        $newFileName = $fieldName . '_' . time() . '.' . $ext;
        $targetPath = $uploadPath . '/' . $newFileName;

        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
            return ['success' => true, 'path' => $targetPath, 'error' => null];
        }

        return ['success' => false, 'path' => null, 'error' => 'Failed to save file'];
    }

    /**
     * Delete a registration
     */
    public function delete(string $formId, string $regId): bool
    {
        $path = $this->getFilePath($formId, $regId);

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Count registrations by status for a form
     */
    public function countByStatus(string $formId): array
    {
        $all = $this->getAllForForm($formId);
        $counts = [];

        foreach ($all as $reg) {
            $status = $reg['status'];
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Count registrations by a specific data field (e.g., assigned_class, assigned_gate)
     * Only counts verified registrations
     */
    public function countByAssignment(string $formId, string $field): array
    {
        $all = $this->getAllForForm($formId);
        $counts = [];

        foreach ($all as $reg) {
            // Only count verified registrations
            if (($reg['status'] ?? '') !== 'verified') {
                continue;
            }

            $value = $reg['data'][$field] ?? null;
            if ($value) {
                $counts[$value] = ($counts[$value] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * Count registrations for quota (excludes rejected, expired, failed)
     */
    public function countForQuota(string $formId): int
    {
        $excludedStatuses = ['rejected', 'expired', 'failed'];
        $all = $this->getAllForForm($formId);
        $count = 0;

        foreach ($all as $reg) {
            $status = $reg['status'] ?? '';
            if (!in_array($status, $excludedStatuses)) {
                $count++;
            }
        }

        return $count;
    }
}
