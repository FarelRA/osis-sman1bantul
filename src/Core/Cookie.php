<?php

/**
 * Cookie Management for Registration System
 * Handles persistent user identification across sessions
 */
class Cookie
{
    const REG_COOKIE_PREFIX = 'osis_reg_';
    const COOKIE_DURATION = 86400 * 60; // 2 months (60 days)

    /**
     * Get or create a registration ID for a specific form
     */
    public static function getOrCreate(string $formId): string
    {
        $cookieName = self::REG_COOKIE_PREFIX . $formId;

        if (isset($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }

        $regId = self::generateRegId();
        self::set($formId, $regId);
        return $regId;
    }

    /**
     * Get existing registration ID for a form
     */
    public static function get(string $formId): ?string
    {
        $cookieName = self::REG_COOKIE_PREFIX . $formId;
        return $_COOKIE[$cookieName] ?? null;
    }

    /**
     * Set registration cookie
     */
    public static function set(string $formId, string $regId): void
    {
        $cookieName = self::REG_COOKIE_PREFIX . $formId;
        $expires = time() + self::COOKIE_DURATION;

        setcookie($cookieName, $regId, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Also set in $_COOKIE for immediate access
        $_COOKIE[$cookieName] = $regId;
    }

    /**
     * Clear registration cookie
     */
    public static function clear(string $formId): void
    {
        $cookieName = self::REG_COOKIE_PREFIX . $formId;

        setcookie($cookieName, '', [
            'expires' => time() - 3600,
            'path' => '/'
        ]);

        unset($_COOKIE[$cookieName]);
    }

    /**
     * Generate unique registration ID
     */
    private static function generateRegId(): string
    {
        return 'REG-' . strtoupper(bin2hex(random_bytes(6)));
    }

    /**
     * Get secret key for HMAC signing
     */
    private static function getSecretKey(): string
    {
        // Use a derived key from BASE_PATH - consistent but not exposed
        return hash('sha256', BASE_PATH . '_osis_restore_secret');
    }

    /**
     * Generate a secure restore token for sharing
     */
    public static function generateRestoreToken(string $formId, string $regId): string
    {
        $payload = [
            'f' => $formId,
            'r' => $regId,
            't' => time()
        ];
        $data = json_encode($payload);
        $hmac = hash_hmac('sha256', $data, self::getSecretKey());
        $payload['h'] = substr($hmac, 0, 16); // Short hash for URL friendliness
        
        return rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    }

    /**
     * Validate restore token and extract formId/regId
     * @return array|null ['formId' => ..., 'regId' => ...] or null if invalid
     */
    public static function validateRestoreToken(string $token): ?array
    {
        try {
            // Decode base64url
            $decoded = base64_decode(strtr($token, '-_', '+/'));
            if (!$decoded) return null;
            
            $payload = json_decode($decoded, true);
            if (!$payload || !isset($payload['f'], $payload['r'], $payload['t'], $payload['h'])) {
                return null;
            }
            
            // Verify HMAC
            $checkPayload = ['f' => $payload['f'], 'r' => $payload['r'], 't' => $payload['t']];
            $expectedHmac = substr(hash_hmac('sha256', json_encode($checkPayload), self::getSecretKey()), 0, 16);
            
            if (!hash_equals($expectedHmac, $payload['h'])) {
                return null;
            }
            
            // Token is valid for 30 days
            if (time() - $payload['t'] > 86400 * 30) {
                return null;
            }
            
            return [
                'formId' => $payload['f'],
                'regId' => $payload['r']
            ];
        } catch (Exception $e) {
            return null;
        }
    }
}
