<?php

/**
 * Orchestrator Repository
 * Manages event-day orchestration logs including:
 * - Payment verification
 * - Check-in/attendance tracking
 * - Merchandise distribution
 * - Location tracking
 */

class OrchestratorRepository
{
    private string $basePath;
    private string $uploadsPath;

    public function __construct()
    {
        $this->basePath = BASE_PATH . '/data/orchestrator';
        $this->uploadsPath = BASE_PATH . '/data/registrations/uploads';
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
     * Get form-specific orchestrator path
     */
    private function getFormPath(string $formId): string
    {
        $path = $this->basePath . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $formId);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * Get events file path for a form
     */
    private function getEventsFilePath(string $formId): string
    {
        return $this->getFormPath($formId) . '/events.json';
    }

    /**
     * Get locations config file path
     */
    private function getLocationsFilePath(string $formId): string
    {
        return $this->getFormPath($formId) . '/locations.json';
    }

    /**
     * Load events for a form
     */
    private function loadEvents(string $formId): array
    {
        $file = $this->getEventsFilePath($formId);
        if (!file_exists($file)) {
            return ['events' => []];
        }
        $data = json_decode(file_get_contents($file), true);
        return $data ?: ['events' => []];
    }

    /**
     * Save events for a form
     */
    private function saveEvents(string $formId, array $data): bool
    {
        $file = $this->getEventsFilePath($formId);
        return file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        ) !== false;
    }

    /**
     * Generate unique event ID
     */
    private function generateEventId(): string
    {
        return 'EVT-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
    }

    /**
     * Log an orchestrator event
     * 
     * @param string $formId Form ID
     * @param string $regId Registration ID
     * @param string $eventType Event type (payment_verified, payment_denied, check_in, merch_given, room_enter, room_exit)
     * @param string $operator Operator/committee member name
     * @param string|null $location Location/room name
     * @param array $data Additional event data
     * @return array The created event
     */
    public function logEvent(
        string $formId,
        string $regId,
        string $eventType,
        string $operator,
        ?string $location = null,
        array $data = []
    ): array {
        $allData = $this->loadEvents($formId);

        $event = [
            'id' => $this->generateEventId(),
            'timestamp' => date('c'),
            'type' => $eventType,
            'reg_id' => $regId,
            'operator' => $operator,
            'location' => $location,
            'data' => $data
        ];

        // Add to beginning for newest first
        array_unshift($allData['events'], $event);

        $this->saveEvents($formId, $allData);

        return $event;
    }

    /**
     * Get events for a form with optional filters
     * 
     * @param string $formId Form ID
     * @param array $filters Optional filters: type, reg_id, location, operator, since (timestamp), limit, offset
     * @return array Events matching filters
     */
    public function getEventsByForm(string $formId, array $filters = []): array
    {
        $allData = $this->loadEvents($formId);
        $events = $allData['events'] ?? [];

        // Apply filters
        if (!empty($filters['type'])) {
            $types = is_array($filters['type']) ? $filters['type'] : [$filters['type']];
            $events = array_filter($events, fn($e) => in_array($e['type'], $types));
        }

        if (!empty($filters['reg_id'])) {
            $events = array_filter($events, fn($e) => $e['reg_id'] === $filters['reg_id']);
        }

        if (!empty($filters['location'])) {
            $events = array_filter($events, fn($e) => $e['location'] === $filters['location']);
        }

        if (!empty($filters['operator'])) {
            $events = array_filter($events, fn($e) => $e['operator'] === $filters['operator']);
        }

        if (!empty($filters['since'])) {
            $sinceTime = strtotime($filters['since']);
            $events = array_filter($events, fn($e) => strtotime($e['timestamp']) >= $sinceTime);
        }

        // Re-index array
        $events = array_values($events);

        // Pagination
        $total = count($events);
        $offset = $filters['offset'] ?? 0;
        $limit = $filters['limit'] ?? PHP_INT_MAX;

        return [
            'events' => array_slice($events, $offset, $limit),
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit
        ];
    }

    /**
     * Get all events for a specific participant
     */
    public function getParticipantEvents(string $formId, string $regId): array
    {
        return $this->getEventsByForm($formId, ['reg_id' => $regId, 'limit' => PHP_INT_MAX]);
    }

    /**
     * Get current location counts (how many participants in each room)
     * Includes: room_enter, room_exit, check_in, merch_given events with locations
     * Automatically handles location changes: room_enter after room_enter = moved to new location
     */
    public function getLocationSummary(string $formId): array
    {
        $events = $this->loadEvents($formId)['events'] ?? [];

        // Track current location per registration
        $currentLocations = [];

        // Process in reverse chronological order (newest first already)
        // We want to find most recent location per participant
        $processed = [];

        foreach ($events as $event) {
            $regId = $event['reg_id'];

            // Skip if we already found a more recent event for this participant
            if (isset($processed[$regId])) {
                continue;
            }

            // Handle room tracking - room_enter always sets current location (implicit exit from previous)
            if ($event['type'] === 'room_enter') {
                $currentLocations[$regId] = $event['location'];
                $processed[$regId] = true;
            } elseif ($event['type'] === 'room_exit') {
                // Participant left, not in any room currently
                $currentLocations[$regId] = null;
                $processed[$regId] = true;
            }
            // Also track check_in and merch_given if they have a location
            elseif (in_array($event['type'], ['check_in', 'merch_given']) && !empty($event['location'])) {
                $currentLocations[$regId] = $event['location'];
                $processed[$regId] = true;
            }
        }

        // Count by location
        $counts = [];
        foreach ($currentLocations as $location) {
            if ($location !== null) {
                $counts[$location] = ($counts[$location] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * Get summary statistics for dashboard
     */
    public function getStats(string $formId): array
    {
        $events = $this->loadEvents($formId)['events'] ?? [];

        // Group events by registration for unique counts
        $checkedIn = [];
        $merchGiven = [];
        $paymentsVerified = [];
        $paymentsDenied = [];

        foreach ($events as $event) {
            $regId = $event['reg_id'];

            switch ($event['type']) {
                case 'check_in':
                    $checkedIn[$regId] = true;
                    break;
                case 'merch_given':
                    $merchGiven[$regId] = true;
                    break;
                case 'payment_verified':
                    $paymentsVerified[$regId] = true;
                    break;
                case 'payment_denied':
                    $paymentsDenied[$regId] = true;
                    break;
            }
        }

        return [
            'total_events' => count($events),
            'checked_in' => count($checkedIn),
            'merch_distributed' => count($merchGiven),
            'payments_verified' => count($paymentsVerified),
            'payments_denied' => count($paymentsDenied),
            'locations' => $this->getLocationSummary($formId),
            'last_updated' => date('c')
        ];
    }

    /**
     * Get configured locations for a form
     */
    public function getLocations(string $formId): array
    {
        $file = $this->getLocationsFilePath($formId);
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return $data['locations'] ?? [];
    }

    /**
     * Save locations configuration
     */
    public function saveLocations(string $formId, array $locations): bool
    {
        $file = $this->getLocationsFilePath($formId);
        return file_put_contents(
            $file,
            json_encode(['locations' => $locations], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        ) !== false;
    }

    /**
     * Check if a participant has already checked in
     */
    public function hasCheckedIn(string $formId, string $regId): bool
    {
        $events = $this->getParticipantEvents($formId, $regId)['events'] ?? [];
        foreach ($events as $event) {
            if ($event['type'] === 'check_in') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if participant has collected merch
     */
    public function hasCollectedMerch(string $formId, string $regId): bool
    {
        $events = $this->getParticipantEvents($formId, $regId)['events'] ?? [];
        foreach ($events as $event) {
            if ($event['type'] === 'merch_given') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get participant's current location
     * Returns null if not in any location
     */
    public function getCurrentLocation(string $formId, string $regId): ?string
    {
        try {
            $events = $this->getParticipantEvents($formId, $regId)['events'] ?? [];
            
            // Find most recent location event
            foreach ($events as $event) {
                if ($event['type'] === 'room_enter') {
                    return $event['location'] ?? null;
                } elseif ($event['type'] === 'room_exit') {
                    return null;
                } elseif (in_array($event['type'], ['check_in', 'merch_given']) && !empty($event['location'])) {
                    return $event['location'];
                }
            }
            
            return null;
        } catch (Exception $e) {
            error_log("getCurrentLocation error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent events (for real-time dashboard polling)
     */
    public function getRecentEvents(string $formId, int $limit = PHP_INT_MAX): array
    {
        $result = $this->getEventsByForm($formId, ['limit' => $limit]);
        return $result['events'];
    }
}
