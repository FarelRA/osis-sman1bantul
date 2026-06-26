<?php

/**
 * Centralized configuration constants for the form registration system
 * All timeout and configuration defaults are defined here
 */
class FormConstants
{
    // Cookie settings
    public const COOKIE_DURATION = 5184000; // 60 days in seconds

    // Default timeouts (in seconds)
    public const DEFAULT_STEP_TIMEOUT = 300;      // 5 minutes
    public const DEFAULT_DOCUMENT_TIMEOUT = 3600; // 1 hour
    public const DEFAULT_PAYMENT_TIMEOUT = 7200;  // 2 hours

    // File upload settings
    public const MAX_FILE_SIZE = 2097152; // 2MB in bytes
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    // Registration statuses
    public const STATUS_PENDING_FORM = 'pending_form';
    public const STATUS_PENDING_DOCUMENT = 'pending_document';
    public const STATUS_PENDING_ONLINE = 'pending_online';
    public const STATUS_PENDING_OFFLINE = 'pending_offline';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    // Status display configuration
    public const STATUS_CONFIG = [
        'pending_form' => ['label' => 'Form', 'color' => 'bg-gray-500'],
        'pending_document' => ['label' => 'Document', 'color' => 'bg-blue-500'],
        'pending_online' => ['label' => 'Online Pay', 'color' => 'bg-yellow-500'],
        'pending_offline' => ['label' => 'Offline Pay', 'color' => 'bg-amber-500'],
        'verified' => ['label' => 'Verified', 'color' => 'bg-green-500'],
        'expired' => ['label' => 'Expired', 'color' => 'bg-red-400'],
        'rejected' => ['label' => 'Rejected', 'color' => 'bg-red-600'],
        'failed' => ['label' => 'Failed', 'color' => 'bg-red-800']
    ];

    // Default registration fee (in IDR)
    public const DEFAULT_REGISTRATION_FEE = 150000;

    // Default AI configuration
    public const DEFAULT_AI_API_URL = 'https://api.openai.com/v1';
    public const DEFAULT_AI_MODEL = 'gpt-4o';

    // Pagination
    public const DEFAULT_PER_PAGE = 20;
}
