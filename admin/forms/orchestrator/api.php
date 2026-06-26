<?php
/**
 * Orchestrator API Endpoints
 * Handles all orchestrator AJAX requests
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/Config.php';
require_once __DIR__ . '/../../../src/Core/CSRF.php';
require_once __DIR__ . '/../../../src/Core/AssignmentService.php';
require_once __DIR__ . '/../../../src/Repository/RegistrationRepository.php';
require_once __DIR__ . '/../../../src/Repository/OrchestratorRepository.php';
require_once __DIR__ . '/../../../src/Repository/OrchestratorAccountRepository.php';

// Auth check - now includes orchestrator accounts
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isFormsAdmin = isset($_SESSION['forms_logged_in']);
$isOrchestratorUser = isset($_SESSION['orchestrator_account']);

// Get current orchestrator account if logged in
$orchestratorAccount = $_SESSION['orchestrator_account'] ?? null;

if (!$isFullAdmin && !$isFormsAdmin && !$isOrchestratorUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Admin/forms accounts can ONLY access account management endpoints
$action = $_GET['action'] ?? '';
$accountManagementActions = ['create_account', 'update_account', 'delete_account', 'get_accounts'];

if (($isFullAdmin || $isFormsAdmin) && !$isOrchestratorUser) {
    if (!in_array($action, $accountManagementActions)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin accounts can only manage orchestrator accounts']);
        exit;
    }
}

$regRepo = new RegistrationRepository();
$orchRepo = new OrchestratorRepository();
$accountRepo = new OrchestratorAccountRepository();

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// CSRF validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($input['csrf_token']) || !CSRF::verify($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

$formId = $input['form_id'] ?? $_GET['form_id'] ?? '';
$regId = $input['reg_id'] ?? '';

// Get operator info - only from orchestrator account
if ($orchestratorAccount) {
    $operatorName = $orchestratorAccount['display_name'] ?? 'Unknown';
    $operatorLocation = $_SESSION['orchestrator_location'] ?? null;
} else {
    $operatorName = 'Admin';
    $operatorLocation = null;
}

// Helper function to check if current user can perform an action
function canPerformAction(string $action): bool {
    global $orchestratorAccount, $accountRepo;
    
    // Only orchestrator accounts can perform actions
    if (!$orchestratorAccount) {
        return false;
    }
    
    // Check orchestrator account permissions
    return $accountRepo->canPerformAction($orchestratorAccount, $action);
}

// Helper function to check if current user can use a location
function canUseLocation(string $location): bool {
    global $orchestratorAccount, $accountRepo;
    
    if (!$orchestratorAccount) {
        return false;
    }
    
    return $accountRepo->canUseLocation($orchestratorAccount, $location);
}

// Helper function to check if current user can manage accounts
function canManageAccounts(): bool {
    global $isFullAdmin, $isFormsAdmin, $orchestratorAccount, $accountRepo;
    
    // Forms admin and full admin can manage accounts
    if ($isFullAdmin || $isFormsAdmin) {
        return true;
    }
    
    if ($orchestratorAccount) {
        return $accountRepo->canManageAccounts($orchestratorAccount);
    }
    
    return false;
}

// ===== API Endpoints =====

switch ($action) {

    // ===== Operator Management =====

    case 'set_operator':
        $_SESSION['orchestrator_operator'] = trim($input['operator_name'] ?? '');
        $_SESSION['orchestrator_location'] = trim($input['operator_location'] ?? '');
        echo json_encode(['success' => true]);
        break;

    case 'clear_operator':
        unset($_SESSION['orchestrator_operator']);
        unset($_SESSION['orchestrator_location']);
        echo json_encode(['success' => true]);
        break;

    // ===== Registration Lookup =====

    case 'lookup':
        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        try {
            $registration = $regRepo->find($formId, $regId);

            if (!$registration) {
                echo json_encode(['success' => false, 'error' => 'Registration not found: ' . $regId]);
                exit;
            }

            // Get orchestrator status
            $orchestratorStatus = [
                'checked_in' => $orchRepo->hasCheckedIn($formId, $regId),
                'merch_collected' => $orchRepo->hasCollectedMerch($formId, $regId),
                'current_location' => $orchRepo->getCurrentLocation($formId, $regId)
            ];

            // Load participant data from CSV
            $participantData = null;
            $csvPath = __DIR__ . '/../../../data/orchestrator/' . $formId . '/participants.csv';
            if (file_exists($csvPath)) {
                $fullName = $registration['data']['full_name'] ?? '';
                if ($fullName && ($handle = fopen($csvPath, 'r')) !== false) {
                    fgetcsv($handle); // Skip header
                    while (($row = fgetcsv($handle)) !== false) {
                        if (isset($row[1]) && strcasecmp(trim($row[1]), trim($fullName)) === 0) {
                            $participantData = [
                                'participant_number' => $row[0] ?? '-',
                                'desk_number' => $row[4] ?? '-'
                            ];
                            break;
                        }
                    }
                    fclose($handle);
                }
            }

            echo json_encode([
                'success' => true,
                'registration' => $registration,
                'orchestrator_status' => $orchestratorStatus,
                'participant_data' => $participantData
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        }
        break;

    // ===== Check-in =====

    case 'check_in':
        // Permission check
        if (!canPerformAction('check_in')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You do not have permission to perform check-ins']);
            exit;
        }

        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        $registration = $regRepo->find($formId, $regId);
        if (!$registration) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }

        // Check if already checked in
        if ($orchRepo->hasCheckedIn($formId, $regId)) {
            echo json_encode(['success' => false, 'error' => 'Already checked in']);
            exit;
        }

        // Log check-in event
        $orchRepo->logEvent($formId, $regId, 'check_in', $operatorName, $operatorLocation);

        echo json_encode([
            'success' => true,
            'message' => 'Checked in successfully!',
            'participant_name' => $registration['data']['full_name'] ?? $regId
        ]);
        break;

    // ===== Payment Verification =====

    case 'verify_payment':
        // Permission check
        if (!canPerformAction('payment')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You do not have permission to verify payments']);
            exit;
        }

        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        $registration = $regRepo->find($formId, $regId);
        if (!$registration) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }

        // Log payment verification
        $orchRepo->logEvent($formId, $regId, 'payment_verified', $operatorName, $operatorLocation, [
            'previous_status' => $registration['status']
        ]);

        // Update registration status to verified
        $regRepo->updateStatus($formId, $regId, 'verified');

        // Advance current_step to the completion step (like the normal payment flow does)
        // This ensures the participant sees the completion page instead of the payment step
        $registration = $regRepo->find($formId, $regId);

        // Auto-assign class and gate (same as admin approval)
        $formsFile = BASE_PATH . '/data/forms.json';
        $formData = file_exists($formsFile) ? json_decode(file_get_contents($formsFile), true) : [];
        $forms = $formData['forms'] ?? $formData;
        $activeForm = null;
        foreach ($forms as $f) {
            if ($f['id'] === $formId) {
                $activeForm = $f;
                break;
            }
        }

        // Advance current_step to completion step using explicit type lookup
        if ($activeForm) {
            require_once __DIR__ . '/../../../src/Core/RegistrationStateMachine.php';
            $stateMachine = new RegistrationStateMachine($activeForm);
            $completeStep = $stateMachine->findStepIndexByType('complete');
            $registration['current_step'] = $completeStep ?? ($registration['current_step'] + 1);
            $regRepo->save($formId, $registration);
        }

        $assignedClass = null;
        $assignedGate = null;
        if ($activeForm) {
            $assignmentSettings = $activeForm['registration_settings']['assignment_settings'] ?? [];
            if (!empty($assignmentSettings['enabled'])) {
                $assignmentService = new AssignmentService($regRepo);
                $result = $assignmentService->assignClassAndGate($formId, $regId, $assignmentSettings, $registration['data'] ?? []);
                $assignedClass = $result['class'] ?? null;
                $assignedGate = $result['gate'] ?? null;
            }
        }

        $message = 'Payment verified successfully!';
        if ($assignedClass) {
            $message .= " Assigned to class: {$assignedClass}";
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'participant_name' => $registration['data']['full_name'] ?? $regId,
            'assigned_class' => $assignedClass,
            'assigned_gate' => $assignedGate,
            'new_status' => 'verified'
        ]);
        break;

    case 'deny_payment':
        // Permission check
        if (!canPerformAction('payment')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You do not have permission to deny payments']);
            exit;
        }

        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        $registration = $regRepo->find($formId, $regId);
        if (!$registration) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }

        $reason = $input['reason'] ?? 'Denied by committee';

        // Log denial
        $orchRepo->logEvent($formId, $regId, 'payment_denied', $operatorName, $operatorLocation, [
            'reason' => $reason,
            'previous_status' => $registration['status']
        ]);

        // Update registration status to rejected
        $regRepo->updateStatus($formId, $regId, 'rejected');

        echo json_encode([
            'success' => true,
            'message' => 'Payment denied',
            'participant_name' => $registration['data']['full_name'] ?? $regId,
            'new_status' => 'rejected'
        ]);
        break;

    // ===== Merch Distribution =====

    case 'give_merch':
        // Permission check
        if (!canPerformAction('merch')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You do not have permission to distribute merch']);
            exit;
        }

        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        $registration = $regRepo->find($formId, $regId);
        if (!$registration) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }

        // Check if already collected
        if ($orchRepo->hasCollectedMerch($formId, $regId)) {
            echo json_encode(['success' => false, 'error' => 'Merch already collected']);
            exit;
        }

        // Log merch distribution
        $orchRepo->logEvent($formId, $regId, 'merch_given', $operatorName, $operatorLocation, [
            'items' => $input['items'] ?? ['default']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Merch marked as given!',
            'participant_name' => $registration['data']['full_name'] ?? $regId
        ]);
        break;

    // ===== Location Tracking =====

    case 'log_location':
        // Permission check
        if (!canPerformAction('location')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You do not have permission to log locations']);
            exit;
        }

        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        $registration = $regRepo->find($formId, $regId);
        if (!$registration) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }

        $direction = $input['direction'] ?? 'enter'; // 'enter' or 'exit'
        $eventType = ($direction === 'exit') ? 'room_exit' : 'room_enter';

        // Check if participant is moving from another location
        $previousLocation = null;
        if ($direction === 'enter') {
            $previousLocation = $orchRepo->getCurrentLocation($formId, $regId);
            
            // If already in a location and entering a new one, log implicit exit from previous
            if ($previousLocation && $previousLocation !== $operatorLocation) {
                $orchRepo->logEvent($formId, $regId, 'room_exit', $operatorName, $previousLocation, [
                    'implicit' => true,
                    'moved_to' => $operatorLocation
                ]);
            }
        }

        // Log location event
        $orchRepo->logEvent($formId, $regId, $eventType, $operatorName, $operatorLocation, [
            'previous_location' => $previousLocation
        ]);

        $msg = ($direction === 'exit') ? 'Exit logged!' : 'Entry logged!';
        if ($previousLocation && $direction === 'enter' && $previousLocation !== $operatorLocation) {
            $msg = "Moved from {$previousLocation} to {$operatorLocation}";
        }

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'participant_name' => $registration['data']['full_name'] ?? $regId,
            'previous_location' => $previousLocation
        ]);
        break;

    // ===== Dashboard Data =====

    case 'get_stats':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $stats = $orchRepo->getStats($formId);

        // Also get total registrations for context
        $registrations = $regRepo->getAllForForm($formId);
        $stats['total_registrations'] = count($registrations);
        $stats['verified_registrations'] = count(array_filter($registrations, fn($r) => $r['status'] === 'verified'));

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        break;

    case 'get_events':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $filters = [
            'type' => $_GET['type'] ?? null,
            'limit' => intval($_GET['limit'] ?? PHP_INT_MAX),
            'offset' => intval($_GET['offset'] ?? 0),
            'since' => $_GET['since'] ?? null
        ];

        $result = $orchRepo->getEventsByForm($formId, $filters);

        // Enrich events with participant names
        foreach ($result['events'] as &$event) {
            $reg = $regRepo->find($formId, $event['reg_id']);
            $event['participant_name'] = $reg['data']['full_name'] ?? $event['reg_id'];
        }

        echo json_encode([
            'success' => true,
            'events' => $result['events'],
            'total' => $result['total'],
            'timestamp' => date('c')
        ]);
        break;

    case 'get_locations':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $locations = $orchRepo->getLocationSummary($formId);

        echo json_encode([
            'success' => true,
            'locations' => $locations
        ]);
        break;

    case 'get_participants_at_location':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $location = $input['location'] ?? $_GET['location'] ?? '';
        if (!$location) {
            echo json_encode(['success' => false, 'error' => 'Missing location']);
            exit;
        }

        // Get current locations mapping (regId => location)
        $events = $orchRepo->getEventsByForm($formId, ['limit' => PHP_INT_MAX])['events'] ?? [];
        $currentLocations = [];
        $processed = [];

        foreach ($events as $event) {
            $regId = $event['reg_id'];
            if (isset($processed[$regId]))
                continue;

            // room_enter always sets current location (implicit exit from previous)
            if ($event['type'] === 'room_enter') {
                $currentLocations[$regId] = $event['location'];
                $processed[$regId] = true;
            } elseif ($event['type'] === 'room_exit') {
                $currentLocations[$regId] = null;
                $processed[$regId] = true;
            } elseif (in_array($event['type'], ['check_in', 'merch_given']) && !empty($event['location'])) {
                $currentLocations[$regId] = $event['location'];
                $processed[$regId] = true;
            }
        }

        // Find participants at the requested location
        $participantsAtLocation = [];
        foreach ($currentLocations as $regId => $loc) {
            if ($loc === $location) {
                $reg = $regRepo->find($formId, $regId);
                if ($reg) {
                    $participantsAtLocation[] = [
                        'id' => $regId,
                        'name' => $reg['data']['full_name'] ?? 'Unknown',
                        'school' => $reg['data']['school_origin'] ?? '-',
                        'status' => $reg['status'] ?? 'unknown',
                        'checked_in' => $orchRepo->hasCheckedIn($formId, $regId),
                        'merch_collected' => $orchRepo->hasCollectedMerch($formId, $regId)
                    ];
                }
            }
        }

        echo json_encode([
            'success' => true,
            'location' => $location,
            'participants' => $participantsAtLocation,
            'count' => count($participantsAtLocation)
        ]);
        break;

    // ===== Participant Search =====

    case 'search_participants':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $query = strtolower(trim($input['query'] ?? $_GET['query'] ?? ''));
        if (strlen($query) < 2) {
            echo json_encode(['success' => false, 'error' => 'Query too short (min 2 chars)']);
            exit;
        }

        $registrations = $regRepo->getAllForForm($formId);
        $results = [];

        foreach ($registrations as $reg) {
            // Only include verified participants
            if (($reg['status'] ?? '') !== 'verified') {
                continue;
            }

            $searchable = strtolower(
                ($reg['id'] ?? '') . ' ' .
                ($reg['data']['full_name'] ?? '') . ' ' .
                ($reg['data']['school_origin'] ?? '') . ' ' .
                ($reg['data']['email'] ?? '')
            );

            if (strpos($searchable, $query) !== false) {
                // Get orchestrator status
                $checkedIn = $orchRepo->hasCheckedIn($formId, $reg['id']);
                $merchCollected = $orchRepo->hasCollectedMerch($formId, $reg['id']);

                $results[] = [
                    'id' => $reg['id'],
                    'name' => $reg['data']['full_name'] ?? 'Unknown',
                    'school' => $reg['data']['school_origin'] ?? '-',
                    'status' => $reg['status'] ?? 'unknown',
                    'checked_in' => $checkedIn,
                    'merch_collected' => $merchCollected
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'results' => $results,
            'count' => count($results)
        ]);
        break;

    case 'get_participant':
        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        $registration = $regRepo->find($formId, $regId);
        if (!$registration) {
            echo json_encode(['success' => false, 'error' => 'Not found']);
            exit;
        }

        $events = $orchRepo->getParticipantEvents($formId, $regId);

        echo json_encode([
            'success' => true,
            'registration' => $registration,
            'events' => $events['events'] ?? [],
            'checked_in' => $orchRepo->hasCheckedIn($formId, $regId),
            'merch_collected' => $orchRepo->hasCollectedMerch($formId, $regId)
        ]);
        break;

    // ===== Export =====

    case 'export_events':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $result = $orchRepo->getEventsByForm($formId, ['limit' => PHP_INT_MAX]);
        $events = $result['events'];

        // Enrich with participant names
        foreach ($events as &$event) {
            $reg = $regRepo->find($formId, $event['reg_id']);
            $event['participant_name'] = $reg['data']['full_name'] ?? $event['reg_id'];
        }

        // Generate CSV
        $csv = "ID,Timestamp,Type,Registration ID,Participant Name,Operator,Location\n";
        foreach ($events as $event) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $event['id'],
                $event['timestamp'],
                $event['type'],
                $event['reg_id'],
                str_replace('"', '""', $event['participant_name']),
                str_replace('"', '""', $event['operator']),
                str_replace('"', '""', $event['location'] ?? '')
            );
        }

        echo json_encode([
            'success' => true,
            'csv' => $csv,
            'filename' => 'orchestrator_events_' . $formId . '_' . date('Ymd_His') . '.csv'
        ]);
        break;

    case 'export_attendees':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $registrations = $regRepo->getAllForForm($formId);
        $attendees = [];

        foreach ($registrations as $reg) {
            if ($orchRepo->hasCheckedIn($formId, $reg['id'])) {
                $attendees[] = [
                    'id' => $reg['id'],
                    'name' => $reg['data']['full_name'] ?? 'Unknown',
                    'school' => $reg['data']['school_origin'] ?? '-',
                    'merch' => $orchRepo->hasCollectedMerch($formId, $reg['id']) ? 'Yes' : 'No'
                ];
            }
        }

        // Generate CSV
        $csv = "Registration ID,Name,School,Merch Collected\n";
        foreach ($attendees as $a) {
            $csv .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                $a['id'],
                str_replace('"', '""', $a['name']),
                str_replace('"', '""', $a['school']),
                $a['merch']
            );
        }

        echo json_encode([
            'success' => true,
            'csv' => $csv,
            'count' => count($attendees),
            'filename' => 'checked_in_attendees_' . $formId . '_' . date('Ymd_His') . '.csv'
        ]);
        break;

    // ===== Broadcast =====

    case 'broadcast':
        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $message = trim($input['message'] ?? '');
        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Message required']);
            exit;
        }

        // Log as a special broadcast event
        $orchRepo->logEvent($formId, 'BROADCAST', 'broadcast', $operatorName, null, [
            'message' => $message
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Broadcast sent successfully'
        ]);
        break;

    // ===== Account Management (Forms Admin Only) =====

    case 'create_account':
        if (!canManageAccounts()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission denied']);
            exit;
        }

        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $accountData = [
            'username' => $input['username'] ?? '',
            'password' => $input['password'] ?? '',
            'display_name' => $input['display_name'] ?? '',
            'permission' => $input['permission'] ?? 'normal',
            'allowed_locations' => $input['allowed_locations'] ?? [],
            'allowed_actions' => $input['allowed_actions'] ?? [],
            'active' => $input['active'] ?? true,
            'created_by' => $operatorName
        ];

        if (empty($accountData['username']) || empty($accountData['password'])) {
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            exit;
        }

        $account = $accountRepo->create($formId, $accountData);

        if ($account) {
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully',
                'account' => $account
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create account. Username may already exist.']);
        }
        break;

    case 'update_account':
        if (!canManageAccounts()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission denied']);
            exit;
        }

        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $accountId = $input['account_id'] ?? '';
        if (!$accountId) {
            echo json_encode(['success' => false, 'error' => 'Missing account_id']);
            exit;
        }

        $updates = [
            'display_name' => $input['display_name'] ?? null,
            'permission' => $input['permission'] ?? null,
            'allowed_locations' => $input['allowed_locations'] ?? null,
            'allowed_actions' => $input['allowed_actions'] ?? null,
            'active' => $input['active'] ?? null,
            'updated_by' => $operatorName
        ];

        // Only include password if provided
        if (!empty($input['password'])) {
            $updates['password'] = $input['password'];
        }

        // Filter out null values
        $updates = array_filter($updates, fn($v) => $v !== null);

        $account = $accountRepo->update($formId, $accountId, $updates);

        if ($account) {
            echo json_encode([
                'success' => true,
                'message' => 'Account updated successfully',
                'account' => $account
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update account']);
        }
        break;

    case 'delete_account':
        if (!canManageAccounts()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission denied']);
            exit;
        }

        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $accountId = $input['account_id'] ?? '';
        if (!$accountId) {
            echo json_encode(['success' => false, 'error' => 'Missing account_id']);
            exit;
        }

        if ($accountRepo->delete($formId, $accountId)) {
            echo json_encode([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete account']);
        }
        break;

    case 'get_accounts':
        if (!canManageAccounts()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission denied']);
            exit;
        }

        if (!$formId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id']);
            exit;
        }

        $accounts = $accountRepo->getAllForForm($formId);

        echo json_encode([
            'success' => true,
            'accounts' => $accounts
        ]);
        break;

    // ===== Orchestrator Login =====

    case 'orchestrator_login':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (!$formId || !$username || !$password) {
            echo json_encode(['success' => false, 'error' => 'Missing credentials']);
            exit;
        }

        $account = $accountRepo->authenticate($formId, $username, $password);

        if ($account) {
            $_SESSION['orchestrator_account'] = $account;
            $_SESSION['orchestrator_form_id'] = $formId;
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'account' => $account
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        }
        break;

    case 'orchestrator_logout':
        unset($_SESSION['orchestrator_account']);
        unset($_SESSION['orchestrator_form_id']);
        unset($_SESSION['orchestrator_location']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
        break;

    case 'get_current_account':
        if ($orchestratorAccount) {
            echo json_encode([
                'success' => true,
                'account' => $orchestratorAccount,
                'location' => $operatorLocation
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Not logged in as orchestrator'
            ]);
        }
        break;

    case 'set_location':
        $location = $input['location'] ?? '';
        
        if (!canUseLocation($location)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You are not allowed to use this location']);
            exit;
        }

        $_SESSION['orchestrator_location'] = $location;
        
        echo json_encode([
            'success' => true,
            'message' => 'Location set successfully',
            'location' => $location
        ]);
        break;

    // ===== Get Full Participant Info =====

    case 'get_full_participant_info':
        if (!$formId || !$regId) {
            echo json_encode(['success' => false, 'error' => 'Missing form_id or reg_id']);
            exit;
        }

        $registration = $regRepo->find($formId, $regId);
        if (!$registration) {
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }

        // Get all orchestrator events for this participant
        $events = $orchRepo->getParticipantEvents($formId, $regId);

        // Get current location
        $allEvents = $events['events'] ?? [];
        $currentLocation = null;
        foreach ($allEvents as $event) {
            if ($event['type'] === 'room_enter') {
                $currentLocation = $event['location'];
                break;
            } elseif ($event['type'] === 'room_exit') {
                $currentLocation = null;
                break;
            } elseif (in_array($event['type'], ['check_in', 'merch_given']) && !empty($event['location'])) {
                $currentLocation = $event['location'];
                break;
            }
        }

        // Build comprehensive info
        $info = [
            'registration' => $registration,
            'orchestrator_status' => [
                'checked_in' => $orchRepo->hasCheckedIn($formId, $regId),
                'merch_collected' => $orchRepo->hasCollectedMerch($formId, $regId),
                'current_location' => $currentLocation
            ],
            'events' => $allEvents,
            'event_summary' => [
                'total_events' => count($allEvents),
                'check_ins' => count(array_filter($allEvents, fn($e) => $e['type'] === 'check_in')),
                'payments' => count(array_filter($allEvents, fn($e) => in_array($e['type'], ['payment_verified', 'payment_denied']))),
                'merch' => count(array_filter($allEvents, fn($e) => $e['type'] === 'merch_given')),
                'location_logs' => count(array_filter($allEvents, fn($e) => in_array($e['type'], ['room_enter', 'room_exit'])))
            ]
        ];

        echo json_encode([
            'success' => true,
            'info' => $info
        ]);
        break;

    // ===== Get Allowed Actions & Locations for Current User =====

    case 'get_permissions':
        $permissions = [
            'is_admin' => $isFullAdmin || $isFormsAdmin,
            'permission_level' => 'normal',
            'allowed_actions' => [],
            'allowed_locations' => [],
            'can_select_location' => false,
            'can_manage_accounts' => canManageAccounts()
        ];

        if ($isFullAdmin || $isFormsAdmin) {
            $permissions['permission_level'] = 'admin';
            $permissions['allowed_actions'] = OrchestratorAccountRepository::getAllActions();
            $permissions['allowed_locations'] = []; // Empty means all
            $permissions['can_select_location'] = true;
        } elseif ($orchestratorAccount) {
            $permissions['permission_level'] = $orchestratorAccount['permission'];
            
            if ($orchestratorAccount['permission'] === 'super' || $orchestratorAccount['permission'] === 'high') {
                $permissions['allowed_actions'] = OrchestratorAccountRepository::getAllActions();
                $permissions['allowed_locations'] = [];
                $permissions['can_select_location'] = true;
            } else {
                $permissions['allowed_actions'] = $orchestratorAccount['allowed_actions'] ?? [];
                $permissions['allowed_locations'] = $orchestratorAccount['allowed_locations'] ?? [];
                $permissions['can_select_location'] = false;
            }
        }

        echo json_encode([
            'success' => true,
            'permissions' => $permissions
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}
