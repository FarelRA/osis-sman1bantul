<?php

/**
 * Orchestrator Account Repository
 * Manages orchestrator user accounts with permission levels:
 * - normal: Pre-configured locations and actions only
 * - high: Can select own location and action
 * - super: Full control, can manage other accounts
 */

class OrchestratorAccountRepository
{
    private string $basePath;

    // Permission levels
    public const PERMISSION_NORMAL = 'normal';
    public const PERMISSION_HIGH = 'high';
    public const PERMISSION_SUPER = 'super';

    // Available actions
    public const ACTION_CHECK_IN = 'check_in';
    public const ACTION_PAYMENT = 'payment';
    public const ACTION_MERCH = 'merch';
    public const ACTION_LOCATION = 'location';

    public function __construct()
    {
        $this->basePath = BASE_PATH . '/data/orchestrator';
        $this->ensureDirectories();
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectories(): void
    {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
    }

    /**
     * Get accounts file path for a form
     */
    private function getAccountsFilePath(string $formId): string
    {
        $path = $this->basePath . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $formId);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path . '/accounts.json';
    }

    /**
     * Load all accounts for a form
     */
    public function loadAccounts(string $formId): array
    {
        $file = $this->getAccountsFilePath($formId);
        if (!file_exists($file)) {
            return ['accounts' => []];
        }
        $data = json_decode(file_get_contents($file), true);
        return $data ?: ['accounts' => []];
    }

    /**
     * Save accounts for a form
     */
    private function saveAccounts(string $formId, array $data): bool
    {
        $file = $this->getAccountsFilePath($formId);
        return file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        ) !== false;
    }

    /**
     * Generate unique account ID
     */
    private function generateAccountId(): string
    {
        return 'ORC-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
    }

    /**
     * Hash password
     */
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Create a new orchestrator account
     * 
     * @param string $formId Form ID
     * @param array $accountData Account data with:
     *   - username: Unique username
     *   - password: Plain text password (will be hashed)
     *   - display_name: Display name for the operator
     *   - permission: Permission level (normal, high, super)
     *   - allowed_locations: Array of location labels (for normal permission)
     *   - allowed_actions: Array of action types (for normal permission)
     *   - active: Whether account is active
     * @return array|null The created account (without password) or null on failure
     */
    public function create(string $formId, array $accountData): ?array
    {
        $allData = $this->loadAccounts($formId);
        
        // Check if username already exists
        $username = strtolower(trim($accountData['username'] ?? ''));
        if (empty($username)) {
            return null;
        }

        foreach ($allData['accounts'] as $account) {
            if (strtolower($account['username']) === $username) {
                return null; // Username taken
            }
        }

        $account = [
            'id' => $this->generateAccountId(),
            'username' => $username,
            'password' => $this->hashPassword($accountData['password'] ?? ''),
            'display_name' => trim($accountData['display_name'] ?? $username),
            'permission' => $this->validatePermission($accountData['permission'] ?? self::PERMISSION_NORMAL),
            'allowed_locations' => $accountData['allowed_locations'] ?? [],
            'allowed_actions' => $this->validateActions($accountData['allowed_actions'] ?? []),
            'active' => $accountData['active'] ?? true,
            'created_at' => date('c'),
            'created_by' => $accountData['created_by'] ?? 'system',
            'last_login' => null,
            'login_count' => 0
        ];

        $allData['accounts'][] = $account;
        
        if ($this->saveAccounts($formId, $allData)) {
            // Return without password
            unset($account['password']);
            return $account;
        }

        return null;
    }

    /**
     * Find account by ID
     */
    public function find(string $formId, string $accountId): ?array
    {
        $allData = $this->loadAccounts($formId);
        
        foreach ($allData['accounts'] as $account) {
            if ($account['id'] === $accountId) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Find account by username (for login)
     */
    public function findByUsername(string $formId, string $username): ?array
    {
        $allData = $this->loadAccounts($formId);
        $username = strtolower(trim($username));
        
        foreach ($allData['accounts'] as $account) {
            if (strtolower($account['username']) === $username) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Update an account
     */
    public function update(string $formId, string $accountId, array $updates): ?array
    {
        $allData = $this->loadAccounts($formId);
        
        foreach ($allData['accounts'] as &$account) {
            if ($account['id'] === $accountId) {
                // Update allowed fields
                if (isset($updates['display_name'])) {
                    $account['display_name'] = trim($updates['display_name']);
                }
                if (isset($updates['permission'])) {
                    $account['permission'] = $this->validatePermission($updates['permission']);
                }
                if (isset($updates['allowed_locations'])) {
                    $account['allowed_locations'] = $updates['allowed_locations'];
                }
                if (isset($updates['allowed_actions'])) {
                    $account['allowed_actions'] = $this->validateActions($updates['allowed_actions']);
                }
                if (isset($updates['active'])) {
                    $account['active'] = (bool) $updates['active'];
                }
                if (isset($updates['password']) && !empty($updates['password'])) {
                    $account['password'] = $this->hashPassword($updates['password']);
                }
                
                $account['updated_at'] = date('c');
                $account['updated_by'] = $updates['updated_by'] ?? 'system';

                if ($this->saveAccounts($formId, $allData)) {
                    unset($account['password']);
                    return $account;
                }
                return null;
            }
        }

        return null;
    }

    /**
     * Delete an account
     */
    public function delete(string $formId, string $accountId): bool
    {
        $allData = $this->loadAccounts($formId);
        
        $filtered = array_filter($allData['accounts'], fn($a) => $a['id'] !== $accountId);
        
        if (count($filtered) < count($allData['accounts'])) {
            $allData['accounts'] = array_values($filtered);
            return $this->saveAccounts($formId, $allData);
        }

        return false;
    }

    /**
     * Get all accounts for a form (without passwords)
     */
    public function getAllForForm(string $formId): array
    {
        $allData = $this->loadAccounts($formId);
        
        return array_map(function($account) {
            unset($account['password']);
            return $account;
        }, $allData['accounts']);
    }

    /**
     * Authenticate an orchestrator account
     */
    public function authenticate(string $formId, string $username, string $password): ?array
    {
        $account = $this->findByUsername($formId, $username);
        
        if (!$account) {
            return null;
        }

        if (!$account['active']) {
            return null;
        }

        if (!$this->verifyPassword($password, $account['password'])) {
            return null;
        }

        // Update last login
        $this->recordLogin($formId, $account['id']);

        // Return account without password
        unset($account['password']);
        return $account;
    }

    /**
     * Record a login
     */
    public function recordLogin(string $formId, string $accountId): void
    {
        $allData = $this->loadAccounts($formId);
        
        foreach ($allData['accounts'] as &$account) {
            if ($account['id'] === $accountId) {
                $account['last_login'] = date('c');
                $account['login_count'] = ($account['login_count'] ?? 0) + 1;
                break;
            }
        }

        $this->saveAccounts($formId, $allData);
    }

    /**
     * Check if an account has permission for a specific action
     */
    public function canPerformAction(array $account, string $action): bool
    {
        // Super can do everything
        if ($account['permission'] === self::PERMISSION_SUPER) {
            return true;
        }

        // High can do everything except manage accounts
        if ($account['permission'] === self::PERMISSION_HIGH) {
            return true;
        }

        // Normal needs explicit permission
        return in_array($action, $account['allowed_actions'] ?? []);
    }

    /**
     * Check if an account can use a specific location
     */
    public function canUseLocation(array $account, string $location): bool
    {
        // Super can use any location
        if ($account['permission'] === self::PERMISSION_SUPER) {
            return true;
        }

        // High can use any location
        if ($account['permission'] === self::PERMISSION_HIGH) {
            return true;
        }

        // Normal needs explicit permission
        return in_array($location, $account['allowed_locations'] ?? []);
    }

    /**
     * Check if account can manage other accounts
     */
    public function canManageAccounts(array $account): bool
    {
        return $account['permission'] === self::PERMISSION_SUPER;
    }

    /**
     * Validate permission level
     */
    private function validatePermission(string $permission): string
    {
        $valid = [self::PERMISSION_NORMAL, self::PERMISSION_HIGH, self::PERMISSION_SUPER];
        return in_array($permission, $valid) ? $permission : self::PERMISSION_NORMAL;
    }

    /**
     * Validate action types
     */
    private function validateActions(array $actions): array
    {
        $valid = [self::ACTION_CHECK_IN, self::ACTION_PAYMENT, self::ACTION_MERCH, self::ACTION_LOCATION];
        return array_values(array_intersect($actions, $valid));
    }

    /**
     * Get permission level display info
     */
    public static function getPermissionInfo(string $permission): array
    {
        $info = [
            self::PERMISSION_NORMAL => [
                'label' => 'Normal',
                'description' => 'Can only use pre-assigned locations and actions',
                'color' => 'blue'
            ],
            self::PERMISSION_HIGH => [
                'label' => 'High',
                'description' => 'Can select any location and action',
                'color' => 'amber'
            ],
            self::PERMISSION_SUPER => [
                'label' => 'Super',
                'description' => 'Full control, can manage other accounts',
                'color' => 'purple'
            ]
        ];

        return $info[$permission] ?? $info[self::PERMISSION_NORMAL];
    }

    /**
     * Get action display info
     */
    public static function getActionInfo(string $action): array
    {
        $info = [
            self::ACTION_CHECK_IN => [
                'label' => 'Check-in',
                'icon' => 'check-circle',
                'description' => 'Record participant attendance'
            ],
            self::ACTION_PAYMENT => [
                'label' => 'Payment',
                'icon' => 'currency-dollar',
                'description' => 'Verify offline payments'
            ],
            self::ACTION_MERCH => [
                'label' => 'Merch',
                'icon' => 'shopping-bag',
                'description' => 'Distribute merchandise'
            ],
            self::ACTION_LOCATION => [
                'label' => 'Location',
                'icon' => 'map-pin',
                'description' => 'Track room entry/exit'
            ]
        ];

        return $info[$action] ?? ['label' => $action, 'icon' => 'question-mark-circle', 'description' => ''];
    }

    /**
     * Get all available actions
     */
    public static function getAllActions(): array
    {
        return [
            self::ACTION_CHECK_IN,
            self::ACTION_PAYMENT,
            self::ACTION_MERCH,
            self::ACTION_LOCATION
        ];
    }

    /**
     * Get all permission levels
     */
    public static function getAllPermissions(): array
    {
        return [
            self::PERMISSION_NORMAL,
            self::PERMISSION_HIGH,
            self::PERMISSION_SUPER
        ];
    }
}
