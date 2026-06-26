<?php
/**
 * Registrations API Endpoint
 * Handles all data operations with server-side pagination, search, filter, sort
 */
session_start();
require_once __DIR__ . '/../../../src/Config.php';
require_once __DIR__ . '/../../../src/Core/CSRF.php';
require_once __DIR__ . '/../../../src/Core/FormConstants.php';
require_once __DIR__ . '/../../../src/Core/AssignmentService.php';
require_once __DIR__ . '/../../../src/Core/Cookie.php';
require_once __DIR__ . '/../../../src/Repository/RegistrationRepository.php';

header('Content-Type: application/json');

// Auth check
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isRegistrationsOnly = isset($_SESSION['forms_logged_in']);
if (!$isFullAdmin && !$isRegistrationsOnly) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$formId = $_GET['form_id'] ?? $_POST['form_id'] ?? null;
if (!$formId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing form_id']);
    exit;
}

// Load form config
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
if (!$activeForm) {
    http_response_code(404);
    echo json_encode(['error' => 'Form not found']);
    exit;
}

$repo = new RegistrationRepository();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$statusConfig = FormConstants::STATUS_CONFIG;

// Helper: Send JSON response
function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Helper: Verify CSRF for POST requests
function verifyCsrf()
{
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        respond(['error' => 'Invalid CSRF token'], 403);
    }
}

// Helper: Get lightweight registration data for list
function formatRegistration($reg, $formId, $statusConfig, $duplicateFields = [])
{
    $status = $reg['status'] ?? 'pending_form';
    $data = $reg['data'] ?? [];
    $studentIdPhoto = $data['student_id_photo'] ?? null;
    $paymentProof = $data['payment_proof'] ?? null;

    $result = [
        'id' => $reg['id'],
        'status' => $status,
        'status_label' => $statusConfig[$status]['label'] ?? ucfirst($status),
        'status_color' => $statusConfig[$status]['color'] ?? 'bg-gray-500',
        'full_name' => $data['full_name'] ?? 'Unknown',
        'school_origin' => $data['school_origin'] ?? '-',
        'assigned_class' => $data['assigned_class'] ?? null,
        'assigned_gate' => $data['assigned_gate'] ?? null,
        'created_at' => $reg['created_at'] ?? '',
        'last_activity' => $reg['last_activity'] ?? '',
        'has_student_id' => $studentIdPhoto && file_exists($studentIdPhoto),
        'has_payment' => $paymentProof && file_exists($paymentProof),
        'student_id_url' => $studentIdPhoto && file_exists($studentIdPhoto)
            ? "/data/registrations/uploads/" . urlencode($formId) . "/" . urlencode($reg['id']) . "/" . basename($studentIdPhoto)
            : null,
        'payment_url' => $paymentProof && file_exists($paymentProof)
            ? "/data/registrations/uploads/" . urlencode($formId) . "/" . urlencode($reg['id']) . "/" . basename($paymentProof)
            : null,
    ];
    if (!empty($duplicateFields)) {
        $result['duplicate_fields'] = $duplicateFields;
    }
    return $result;
}

// Helper: Detect duplicates and return map of regId => duplicate fields
function detectDuplicates($registrations)
{
    $excludedStatuses = ['expired', 'failed', 'rejected'];
    $fields = ['full_name', 'email', 'whatsapp'];
    $valueMaps = array_fill_keys($fields, []);

    // Build value -> [regIds] maps (only for non-excluded statuses)
    foreach ($registrations as $reg) {
        if (in_array($reg['status'] ?? '', $excludedStatuses))
            continue;
        $id = $reg['id'];
        $data = $reg['data'] ?? [];
        foreach ($fields as $field) {
            $val = strtolower(trim($data[$field] ?? ''));
            if ($val !== '') {
                $valueMaps[$field][$val][] = $id;
            }
        }
    }

    // Find which regIds have duplicates
    $duplicateMap = [];
    foreach ($fields as $field) {
        foreach ($valueMaps[$field] as $val => $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $duplicateMap[$id][] = $field;
                }
            }
        }
    }

    return $duplicateMap;
}

// Helper: Get full registration data for edit
function formatFullRegistration($reg, $formId, $statusConfig)
{
    $base = formatRegistration($reg, $formId, $statusConfig);
    $base['data'] = $reg['data'] ?? [];
    $base['ai_verification'] = $reg['ai_verification'] ?? [];
    $base['current_step'] = $reg['current_step'] ?? 0;
    $base['status_history'] = $reg['status_history'] ?? [];
    return $base;
}

try {
    switch ($action) {
        case 'list':
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? FormConstants::DEFAULT_PER_PAGE)));
            $search = trim($_GET['search'] ?? '');
            $statusFilter = $_GET['status'] ?? 'all';
            $sort = $_GET['sort'] ?? 'newest';

            // Get all registrations (file-based, so we load all then filter/sort/paginate)
            $all = $repo->getAllForForm($formId);
            $duplicateMap = [];

            // Handle duplicate filter
            if ($statusFilter === 'duplicate') {
                $duplicateMap = detectDuplicates($all);
                $all = array_filter($all, fn($r) => isset($duplicateMap[$r['id']]));
            } elseif ($statusFilter !== 'all' && $statusFilter !== '') {
                $all = array_filter($all, fn($r) => ($r['status'] ?? '') === $statusFilter);
            }

            // Search by name or reg ID
            if ($search !== '') {
                $searchLower = strtolower($search);
                $all = array_filter($all, function ($r) use ($searchLower) {
                    $name = strtolower($r['data']['full_name'] ?? '');
                    $id = strtolower($r['id'] ?? '');
                    $school = strtolower($r['data']['school_origin'] ?? '');
                    return str_contains($name, $searchLower)
                        || str_contains($id, $searchLower)
                        || str_contains($school, $searchLower);
                });
            }

            // Sort - fixed for duplicate filter (first by time, then by name)
            $all = array_values($all);
            if ($statusFilter === 'duplicate') {
                usort($all, fn($a, $b) => strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0'));
                usort($all, fn($a, $b) => strcasecmp($a['data']['full_name'] ?? '', $b['data']['full_name'] ?? ''));
            } else {
                usort($all, function ($a, $b) use ($sort) {
                    switch ($sort) {
                        case 'oldest':
                            return strtotime($a['created_at'] ?? '0') - strtotime($b['created_at'] ?? '0');
                        case 'name':
                            return strcasecmp($a['data']['full_name'] ?? '', $b['data']['full_name'] ?? '');
                        case 'name_desc':
                            return strcasecmp($b['data']['full_name'] ?? '', $a['data']['full_name'] ?? '');
                        case 'status':
                            return strcmp($a['status'] ?? '', $b['status'] ?? '');
                        case 'class':
                            $classA = $a['data']['assigned_class'] ?? 'zzz';
                            $classB = $b['data']['assigned_class'] ?? 'zzz';
                            return strcasecmp($classA, $classB);
                        case 'newest':
                        default:
                            return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
                    }
                });
            }

            $total = count($all);
            $totalPages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginated = array_slice($all, $offset, $perPage);

            respond([
                'success' => true,
                'registrations' => array_map(fn($r) => formatRegistration($r, $formId, $statusConfig, $duplicateMap[$r['id']] ?? []), $paginated),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_more' => $page < $totalPages,
                ],
                'timestamp' => time(),
            ]);
            break;

        case 'stats':
            $all = $repo->getAllForForm($formId);
            $statusCounts = [];
            foreach ($all as $reg) {
                $s = $reg['status'] ?? 'pending_form';
                $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
            }
            $duplicateMap = detectDuplicates($all);
            respond([
                'success' => true,
                'total' => count($all),
                'statusCounts' => $statusCounts,
                'duplicateCount' => count($duplicateMap),
                'timestamp' => time(),
            ]);
            break;

        case 'get':
            $regId = $_GET['id'] ?? '';
            if (!$regId)
                respond(['error' => 'Missing id'], 400);

            $reg = $repo->find($formId, $regId);
            if (!$reg)
                respond(['error' => 'Registration not found'], 404);

            respond([
                'success' => true,
                'registration' => formatFullRegistration($reg, $formId, $statusConfig),
            ]);
            break;

        case 'approve':
            verifyCsrf();
            $regId = $_POST['id'] ?? '';
            if (!$regId)
                respond(['error' => 'Missing id'], 400);

            $reg = $repo->find($formId, $regId);
            if (!$reg)
                respond(['error' => 'Registration not found'], 404);

            if ($reg['status'] === FormConstants::STATUS_VERIFIED) {
                respond(['error' => 'Already verified'], 400);
            }

            $repo->updateStatus($formId, $regId, FormConstants::STATUS_VERIFIED);

            // Auto-assign class and gate
            $assignmentSettings = $activeForm['registration_settings']['assignment_settings'] ?? [];
            if (!empty($assignmentSettings['enabled'])) {
                $assignmentService = new AssignmentService($repo);
                $assignmentService->assignClassAndGate($formId, $regId, $assignmentSettings, $reg['data'] ?? []);
            }

            $updated = $repo->find($formId, $regId);
            respond([
                'success' => true,
                'registration' => formatRegistration($updated, $formId, $statusConfig),
                'statusCounts' => $repo->countByStatus($formId),
            ]);
            break;

        case 'reject':
            verifyCsrf();
            $regId = $_POST['id'] ?? '';
            if (!$regId)
                respond(['error' => 'Missing id'], 400);

            $reg = $repo->find($formId, $regId);
            if (!$reg)
                respond(['error' => 'Registration not found'], 404);

            if (in_array($reg['status'], [FormConstants::STATUS_REJECTED, FormConstants::STATUS_EXPIRED])) {
                respond(['error' => 'Already rejected/expired'], 400);
            }

            $repo->updateStatus($formId, $regId, FormConstants::STATUS_REJECTED);
            $updated = $repo->find($formId, $regId);

            respond([
                'success' => true,
                'registration' => formatRegistration($updated, $formId, $statusConfig),
                'statusCounts' => $repo->countByStatus($formId),
            ]);
            break;

        case 'delete':
            verifyCsrf();
            $regId = $_POST['id'] ?? '';
            if (!$regId)
                respond(['error' => 'Missing id'], 400);

            if (!$repo->find($formId, $regId)) {
                respond(['error' => 'Registration not found'], 404);
            }

            $repo->delete($formId, $regId);

            respond([
                'success' => true,
                'deleted_id' => $regId,
                'statusCounts' => $repo->countByStatus($formId),
                'total' => count($repo->getAllForForm($formId)),
            ]);
            break;

        case 'edit':
            verifyCsrf();
            $regId = $_POST['id'] ?? '';
            if (!$regId)
                respond(['error' => 'Missing id'], 400);

            $reg = $repo->find($formId, $regId);
            if (!$reg)
                respond(['error' => 'Registration not found'], 404);

            $excludedFields = ['student_id_photo', 'payment_proof', 'document_attempts', 'payment_attempts'];
            $newData = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'data_') === 0) {
                    $fieldName = substr($key, 5);
                    if (!in_array($fieldName, $excludedFields)) {
                        $newData[$fieldName] = trim($value);
                    }
                }
            }

            if (!empty($newData)) {
                $repo->updateData($formId, $regId, $newData);
            }

            $newStatus = $_POST['status'] ?? '';
            if ($newStatus && $newStatus !== $reg['status']) {
                $repo->updateStatus($formId, $regId, $newStatus);
            }

            $updated = $repo->find($formId, $regId);
            respond([
                'success' => true,
                'registration' => formatRegistration($updated, $formId, $statusConfig),
                'statusCounts' => $repo->countByStatus($formId),
            ]);
            break;

        case 'add':
            verifyCsrf();
            $newRegId = 'REG-' . strtoupper(bin2hex(random_bytes(6)));
            $repo->findOrCreate($formId, $newRegId, true);

            $newData = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'data_') === 0) {
                    $fieldName = substr($key, 5);
                    $newData[$fieldName] = trim($value);
                }
            }

            if (!empty($newData)) {
                $repo->updateData($formId, $newRegId, $newData);
            }

            $newStatus = $_POST['status'] ?? FormConstants::STATUS_PENDING_FORM;
            if ($newStatus !== FormConstants::STATUS_PENDING_FORM) {
                $repo->updateStatus($formId, $newRegId, $newStatus);
            }

            // Auto-assign if verified
            if ($newStatus === FormConstants::STATUS_VERIFIED) {
                $assignmentSettings = $activeForm['registration_settings']['assignment_settings'] ?? [];
                if (!empty($assignmentSettings['enabled'])) {
                    $assignmentService = new AssignmentService($repo);
                    $assignmentService->assignClassAndGate($formId, $newRegId, $assignmentSettings, $newData);
                }
            }

            $created = $repo->find($formId, $newRegId);
            respond([
                'success' => true,
                'registration' => formatRegistration($created, $formId, $statusConfig),
                'statusCounts' => $repo->countByStatus($formId),
                'total' => count($repo->getAllForForm($formId)),
            ]);
            break;

        case 'export_csv':
            verifyCsrf();
            $all = $repo->getAllForForm($formId);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="registrations-' . $formId . '-' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Collect all data keys
            $allDataKeys = [];
            foreach ($all as $reg) {
                if (!empty($reg['data'])) {
                    foreach (array_keys($reg['data']) as $key) {
                        if (!in_array($key, $allDataKeys))
                            $allDataKeys[] = $key;
                    }
                }
            }

            // Headers
            $headers = ['Registration ID', 'Form ID', 'Status', 'Current Step', 'Created At', 'Last Activity'];
            foreach ($allDataKeys as $key) {
                $headers[] = ucwords(str_replace('_', ' ', $key));
            }
            $headers = array_merge($headers, [
                'AI Student ID Valid',
                'AI Student ID Confidence',
                'AI Student ID Detected Name',
                'AI Student ID Issues',
                'AI Payment Valid',
                'AI Payment Confidence',
                'AI Payment Detected Amount',
                'AI Payment Issues',
                'Status History'
            ]);
            fputcsv($output, $headers);

            // Data rows
            foreach ($all as $reg) {
                $data = $reg['data'] ?? [];
                $ai = $reg['ai_verification'] ?? [];
                $studentId = $ai['student_id']['result'] ?? [];
                $payment = $ai['payment']['result'] ?? [];

                $row = [
                    $reg['id'] ?? '',
                    $reg['form_id'] ?? '',
                    $reg['status'] ?? '',
                    $reg['current_step'] ?? '',
                    $reg['created_at'] ?? '',
                    $reg['last_activity'] ?? ''
                ];

                foreach ($allDataKeys as $key) {
                    $val = $data[$key] ?? '';
                    $row[] = is_array($val) ? json_encode($val) : $val;
                }

                $row[] = isset($studentId['valid']) ? ($studentId['valid'] ? 'Yes' : 'No') : '';
                $row[] = $studentId['confidence'] ?? '';
                $row[] = $studentId['detected_name'] ?? '';
                $row[] = !empty($studentId['issues']) ? implode('; ', $studentId['issues']) : '';
                $row[] = isset($payment['valid']) ? ($payment['valid'] ? 'Yes' : 'No') : '';
                $row[] = $payment['confidence'] ?? '';
                $row[] = $payment['detected_amount'] ?? '';
                $row[] = !empty($payment['issues']) ? implode('; ', $payment['issues']) : '';

                $history = '';
                foreach ($reg['status_history'] ?? [] as $h) {
                    $history .= ($h['from'] ?? 'start') . '->' . ($h['to'] ?? '') . ' (' . ($h['timestamp'] ?? '') . ') | ';
                }
                $row[] = rtrim($history, ' | ');

                fputcsv($output, $row);
            }

            fclose($output);
            exit;

        case 'generate_share_link':
            $regId = $_GET['id'] ?? '';
            if (!$regId)
                respond(['error' => 'Missing id'], 400);

            $reg = $repo->find($formId, $regId);
            if (!$reg)
                respond(['error' => 'Registration not found'], 404);

            $token = Cookie::generateRestoreToken($formId, $regId);
            $restoreUrl = url('restore?token=' . $token);

            respond([
                'success' => true,
                'url' => $restoreUrl,
            ]);
            break;

        default:
            respond(['error' => 'Unknown action'], 400);
    }
} catch (Exception $e) {
    error_log("Registrations API error: " . $e->getMessage());
    respond(['error' => 'Server error: ' . $e->getMessage()], 500);
}
