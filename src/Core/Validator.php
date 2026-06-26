<?php

class Validator
{
    private $errors = [];
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function addError($field, $message)
    {
        $this->errors[$field] = $message;
    }

    public function required($field, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email($field, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function min($field, $length, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = "$label must be at least $length characters.";
        }
        return $this;
    }

    public function max($field, $length, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = "$label must not exceed $length characters.";
        }
        return $this;
    }

    /**
     * Validate Indonesian phone number format (08xx or 628xx)
     */
    public function phone($field, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (!empty($this->data[$field])) {
            $phone = preg_replace('/[^0-9]/', '', $this->data[$field]);
            // Must start with 08 or 62 and be 10-15 digits
            if (!preg_match('/^(0|62)8[0-9]{8,12}$/', $phone)) {
                $this->errors[$field] = "$label must be a valid Indonesian phone number (e.g., 08123456789 or 6281234567890).";
            }
        }
        return $this;
    }

    /**
     * Normalize phone number to 62xxx format
     */
    public static function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }

    /**
     * Check if a field value is unique (not already submitted)
     */
    public function unique($field, $submissions, $formId, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (empty($this->data[$field])) {
            return $this;
        }

        $value = $this->data[$field];

        // Normalize phone numbers for comparison
        if ($field === 'whatsapp' || $field === 'phone' || $field === 'parent_whatsapp') {
            $value = self::normalizePhone($value);
        }

        foreach ($submissions as $sub) {
            // Only check submissions for the same form that aren't FAILED
            if (($sub['form_id'] ?? '') !== $formId)
                continue;
            if (($sub['status'] ?? 'PENDING') === 'FAILED')
                continue;

            $existingValue = $sub[$field] ?? '';

            // Normalize phone numbers in existing submissions too
            if ($field === 'whatsapp' || $field === 'phone' || $field === 'parent_whatsapp') {
                $existingValue = self::normalizePhone($existingValue);
            }

            if (strtolower($value) === strtolower($existingValue)) {
                $this->errors[$field] = "$label has already been registered. Please use a different $label.";
                break;
            }
        }
        return $this;
    }

    /**
     * Validate file type (extension)
     */
    public function fileType($field, $allowedTypes, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (empty($_FILES[$field]['name'])) {
            return $this;
        }

        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            $this->errors[$field] = "$label must be one of: " . implode(', ', $allowedTypes) . ".";
        }
        return $this;
    }

    /**
     * Validate file size (in bytes)
     */
    public function fileSize($field, $maxSize, $label = null)
    {
        $label = $label ?? ucfirst($field);
        if (empty($_FILES[$field]['name'])) {
            return $this;
        }

        if ($_FILES[$field]['size'] > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            $this->errors[$field] = "$label must be smaller than {$maxMB}MB.";
        }
        return $this;
    }

    public function passes()
    {
        return empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }
}