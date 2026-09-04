<?php
/**
 * Submissions API Endpoint
 * Handles all data operations with server-side pagination, search, filter, sort
 */
require_once __DIR__ . '/../../../src/Config.php';
require_once __DIR__ . '/../../../src/Core/CSRF.php';
require_once __DIR__ . '/../../../src/Core/FormConstants.php';
require_once __DIR__ . '/../../../src/Core/Cookie.php';

header('Content-Type: application/json');

// Auth check
$isFullAdmin = isset($_SESSION['admin_logged_in']);
$isFormsOnly = isset($_SESSION['forms_logged_in']);
if (!$isFullAdmin && !$isFormsOnly) {
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

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Get display fields from form config
$displayFields = getDisplayFields($activeForm);

// Submissions file helpers
function getSubmissionsFile($formId)
{
    return BASE_PATH . '/data/submissions/' . $formId . '.json';
}

function loadSubmissions($formId)
{
    $file = getSubmissionsFile($formId);
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveSubmissions($formId, $submissions)
{
    $file = getSubmissionsFile($formId);
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

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

// Helper: Get all text-like fields from form config
function getDisplayFields($activeForm)
{
    $fields = [];
    foreach ($activeForm['steps'] ?? [] as $step) {
        foreach ($step['fields'] ?? [] as $field) {
            $type = $field['type'] ?? 'text';
            if (in_array($type, ['text', 'email', 'tel', 'select', 'textarea'])) {
                $fields[] = $field['name'];
            }
        }
    }
    return $fields;
}

// Helper: Get lightweight submission data for list
function formatSubmission($sub, $formId, $displayFields)
{
    $status = strtoupper($sub['status'] ?? 'PENDING');

    // Flatten nested data if present
    $flat = $sub;
    if (!empty($sub['data']) && is_array($sub['data'])) {
        $flat = array_merge($sub, $sub['data']);
    }

    // First text field = display name, second = display info
    $displayName = 'Unknown';
    $displayInfo = '-';
    if (!empty($displayFields)) {
        $firstField = $displayFields[0];
        $displayName = $flat[$firstField] ?? 'Unknown';
        if (count($displayFields) > 1) {
            $secondField = $displayFields[1];
            $displayInfo = $flat[$secondField] ?? '-';
        }
    }

    // Find first file field value
    $filePath = null;
    foreach ($flat as $key => $val) {
        if (is_string($val) && preg_match('#/data/submissions/uploads/#', $val)) {
            $filePath = $val;
            break;
        }
    }

    return [
        'id' => $sub['registration_id'] ?? $sub['id'] ?? 'unknown',
        'status' => $status,
        'display_name' => $displayName,
        'display_info' => $displayInfo,
        'created_at' => $sub['timestamp'] ?? $sub['created_at'] ?? '',
        'form_id' => $sub['form_id'] ?? $formId,
        'context_type' => $sub['context_type'] ?? 'standalone',
        'slug' => $sub['slug'] ?? '',
        'has_file' => $filePath && file_exists($filePath),
        'file_url' => $filePath && file_exists($filePath)
            ? "/data/submissions/uploads/" . urlencode($formId) . "/" . urlencode($sub['registration_id'] ?? $sub['id'] ?? '') . "/" . basename($filePath)
            : null,
    ];
}

try {
    switch ($action) {
        case 'list':
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 50)));
            $search = trim($_GET['search'] ?? '');
            $statusFilter = strtolower($_GET['status'] ?? 'all');
            $sort = $_GET['sort'] ?? 'newest';

            $all = loadSubmissions($formId);

            // Filter by status
            if ($statusFilter !== 'all' && $statusFilter !== '') {
                $all = array_filter($all, function ($s) use ($statusFilter) {
                    return strtolower($s['status'] ?? 'pending') === $statusFilter;
                });
            }

            // Search - dynamically search all text fields
            if ($search !== '') {
                $searchLower = strtolower($search);
                $all = array_filter($all, function ($s) use ($searchLower, $displayFields) {
                    $flat = $s;
                    if (!empty($s['data']) && is_array($s['data'])) {
                        $flat = array_merge($s, $s['data']);
                    }
                    $id = strtolower($s['registration_id'] ?? $s['id'] ?? '');
                    if (str_contains($id, $searchLower)) return true;
                    foreach ($displayFields as $field) {
                        $val = strtolower($flat[$field] ?? '');
                        if (str_contains($val, $searchLower)) return true;
                    }
                    return false;
                });
            }

            // Sort
            $all = array_values($all);
            usort($all, function ($a, $b) use ($sort, $displayFields) {
                switch ($sort) {
                    case 'oldest':
                        return strtotime($a['timestamp'] ?? $a['created_at'] ?? '0') - strtotime($b['timestamp'] ?? $b['created_at'] ?? '0');
                    case 'name':
                        $aName = $displayFields ? ($a[$displayFields[0]] ?? '') : '';
                        $bName = $displayFields ? ($b[$displayFields[0]] ?? '') : '';
                        return strcasecmp($aName, $bName);
                    case 'name_desc':
                        $aName = $displayFields ? ($a[$displayFields[0]] ?? '') : '';
                        $bName = $displayFields ? ($b[$displayFields[0]] ?? '') : '';
                        return strcasecmp($bName, $aName);
                    case 'status':
                        return strcmp($a['status'] ?? '', $b['status'] ?? '');
                    case 'newest':
                    default:
                        return strtotime($b['timestamp'] ?? $b['created_at'] ?? '0') - strtotime($a['timestamp'] ?? $a['created_at'] ?? '0');
                }
            });

            $total = count($all);
            $totalPages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginated = array_slice($all, $offset, $perPage);

            respond([
                'success' => true,
                'submissions' => array_map(fn($s) => formatSubmission($s, $formId, $displayFields), $paginated),
                'displayFields' => $displayFields,
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
            $all = loadSubmissions($formId);
            $statusCounts = [];
            foreach ($all as $sub) {
                $s = strtoupper($sub['status'] ?? 'PENDING');
                $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
            }
            respond([
                'success' => true,
                'total' => count($all),
                'statusCounts' => $statusCounts,
                'timestamp' => time(),
            ]);
            break;

        case 'get':
            $subId = $_GET['id'] ?? '';
            if (!$subId) respond(['error' => 'Missing id'], 400);

            $all = loadSubmissions($formId);
            $found = null;
            foreach ($all as $s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    $found = $s;
                    break;
                }
            }
            if (!$found) respond(['error' => 'Submission not found'], 404);

            // Flatten for easy editing
            $flat = $found;
            if (!empty($found['data']) && is_array($found['data'])) {
                $flat = array_merge($found, $found['data']);
            }
            unset($flat['data']);

            // Convert full server paths to relative paths for display
            $uploadBase = BASE_PATH . '/data/submissions/uploads/' . $formId . '/';
            foreach ($flat as $key => $val) {
                if (is_string($val) && strpos($val, $uploadBase) === 0) {
                    $flat[$key] = 'uploads/' . substr($val, strlen($uploadBase));
                }
            }

            respond([
                'success' => true,
                'submission' => $flat,
            ]);
            break;

        case 'approve':
            verifyCsrf();
            $subId = $_POST['id'] ?? '';
            if (!$subId) respond(['error' => 'Missing id'], 400);

            $all = loadSubmissions($formId);
            $found = false;
            foreach ($all as &$s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    $oldStatus = strtoupper($s['status'] ?? 'PENDING');
                    $s['status'] = 'VERIFIED';
                    $s['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            unset($s);

            if (!$found) respond(['error' => 'Submission not found'], 404);
            saveSubmissions($formId, $all);

            $updated = null;
            foreach ($all as $s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    $updated = $s;
                    break;
                }
            }

            respond([
                'success' => true,
                'submission' => formatSubmission($updated, $formId, $displayFields),
                'total' => count($all),
            ]);
            break;

        case 'reject':
            verifyCsrf();
            $subId = $_POST['id'] ?? '';
            if (!$subId) respond(['error' => 'Missing id'], 400);

            $all = loadSubmissions($formId);
            $found = false;
            foreach ($all as &$s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    $s['status'] = 'FAILED';
                    $s['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            unset($s);

            if (!$found) respond(['error' => 'Submission not found'], 404);
            saveSubmissions($formId, $all);

            $updated = null;
            foreach ($all as $s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    $updated = $s;
                    break;
                }
            }

            respond([
                'success' => true,
                'submission' => formatSubmission($updated, $formId, $displayFields),
                'total' => count($all),
            ]);
            break;

        case 'delete':
            verifyCsrf();
            $subId = $_POST['id'] ?? '';
            if (!$subId) respond(['error' => 'Missing id'], 400);

            $all = loadSubmissions($formId);
            $found = false;
            foreach ($all as $i => $s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    unset($all[$i]);
                    $found = true;
                    break;
                }
            }

            if (!$found) respond(['error' => 'Submission not found'], 404);

            $all = array_values($all);
            saveSubmissions($formId, $all);

            respond([
                'success' => true,
                'deleted_id' => $subId,
                'total' => count($all),
            ]);
            break;

        case 'edit':
            verifyCsrf();
            $subId = $_POST['id'] ?? '';
            if (!$subId) respond(['error' => 'Missing id'], 400);

            $all = loadSubmissions($formId);
            $found = false;
            foreach ($all as &$s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'data_') === 0) {
                            $fieldName = substr($key, 5);
                            $s[$fieldName] = trim($value);
                        }
                    }
                    $newStatus = $_POST['status'] ?? '';
                    if ($newStatus && strtoupper($newStatus) !== strtoupper($s['status'] ?? '')) {
                        $s['status'] = strtoupper($newStatus);
                    }
                    $s['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            unset($s);

            if (!$found) respond(['error' => 'Submission not found'], 404);
            saveSubmissions($formId, $all);

            $updated = null;
            foreach ($all as $s) {
                if (($s['registration_id'] ?? $s['id'] ?? '') === $subId) {
                    $updated = $s;
                    break;
                }
            }

            respond([
                'success' => true,
                'submission' => formatSubmission($updated, $formId, $displayFields),
                'total' => count($all),
            ]);
            break;

        case 'export_csv':
            verifyCsrf();
            $all = loadSubmissions($formId);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="submissions-' . $formId . '-' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Collect all data keys from all submissions
            $allDataKeys = [];
            foreach ($all as $sub) {
                $flat = $sub;
                if (!empty($sub['data']) && is_array($sub['data'])) {
                    $flat = array_merge($sub, $sub['data']);
                }
                foreach (array_keys($flat) as $key) {
                    if (!in_array($key, $allDataKeys) && !in_array($key, ['registration_id', 'timestamp', 'form_id', 'context_type', 'context_id', 'slug', 'updated_at', 'data'])) {
                        $allDataKeys[] = $key;
                    }
                }
            }

            // Headers
            $headers = ['Registration ID', 'Status', 'Created At', 'Updated At'];
            foreach ($allDataKeys as $key) {
                $headers[] = ucwords(str_replace('_', ' ', $key));
            }
            fputcsv($output, $headers);

            // Data rows
            foreach ($all as $sub) {
                $flat = $sub;
                if (!empty($sub['data']) && is_array($sub['data'])) {
                    $flat = array_merge($sub, $sub['data']);
                }
                $row = [
                    $sub['registration_id'] ?? $sub['id'] ?? '',
                    strtoupper($sub['status'] ?? 'PENDING'),
                    $sub['timestamp'] ?? $sub['created_at'] ?? '',
                    $sub['updated_at'] ?? '',
                ];

                foreach ($allDataKeys as $key) {
                    $val = $flat[$key] ?? '';
                    $row[] = is_array($val) ? json_encode($val) : $val;
                }

                fputcsv($output, $row);
            }

            fclose($output);
            exit;

        case 'file':
            // Serve file through API (restricted path)
            $relPath = $_GET['path'] ?? '';
            if (!$relPath) {
                http_response_code(400);
                echo 'Missing path';
                exit;
            }

            // Reconstruct full path from relative path
            $fullPath = BASE_PATH . '/data/submissions/uploads/' . $formId . '/' . ltrim($relPath, 'uploads/');

            // Security: only allow files from submissions/uploads for this form
            $allowedBase = realpath(BASE_PATH . '/data/submissions/uploads/' . $formId);
            if (!$allowedBase) {
                http_response_code(404);
                echo 'Not found';
                exit;
            }

            $realPath = realpath($fullPath);
            if ($realPath === false || strpos($realPath, $allowedBase) !== 0) {
                http_response_code(403);
                echo 'Forbidden';
                exit;
            }

            if (!file_exists($realPath) || !is_file($realPath)) {
                http_response_code(404);
                echo 'Not found';
                exit;
            }

            // Detect MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $realPath);
            finfo_close($finfo);

            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($realPath));
            header('Content-Disposition: inline; filename="' . basename($realPath) . '"');
            header('Cache-Control: private, max-age=3600');
            readfile($realPath);
            exit;

        default:
            respond(['error' => 'Unknown action'], 400);
    }
} catch (Exception $e) {
    error_log("Submissions API error: " . $e->getMessage());
    respond(['error' => 'Server error: ' . $e->getMessage()], 500);
}
