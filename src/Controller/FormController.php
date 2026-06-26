<?php
require_once __DIR__ . '/../Core/CSRF.php';
require_once __DIR__ . '/../Core/Validator.php';

class FormController
{

    private function getForms()
    {
        $path = BASE_PATH . '/data/forms.json';
        // Support for new structure: {"forms": [], "settings": {}}
        $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        return $data['forms'] ?? $data;
    }

    private function getSubmissions($formId = null)
    {
        // If formId provided, get submissions for that specific form
        if ($formId) {
            $path = BASE_PATH . '/data/submissions/' . $formId . '.json';
            return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        }

        // Otherwise get all submissions from all form files
        $submissionsDir = BASE_PATH . '/data/submissions';
        if (!is_dir($submissionsDir)) {
            return [];
        }

        $allSubmissions = [];
        foreach (glob($submissionsDir . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                $allSubmissions = array_merge($allSubmissions, $data);
            }
        }
        return $allSubmissions;
    }

    private function getFormById($id)
    {
        $forms = $this->getForms();
        foreach ($forms as $form) {
            if ($form['id'] === $id)
                return $form;
        }
        return null;
    }

    // New: Find form by Context + Slug (Path-Specific)
    private function getFormByPath($type, $entitySlug, $formSlug)
    {
        $forms = $this->getForms();
        foreach ($forms as $form) {
            $contextType = $form['context_type'] ?? 'standalone';
            $contextId = $form['context_id'] ?? '';
            $slug = $form['slug'] ?? '';

            if ($contextType === $type && $contextId === $entitySlug && $slug === $formSlug) {
                return $form;
            }
        }
        return null;
    }

    // Fallback: Find standalone form by slug
    private function getStandaloneForm($slug)
    {
        $forms = $this->getForms();
        foreach ($forms as $form) {
            if (($form['context_type'] ?? 'standalone') === 'standalone' && $form['slug'] === $slug) {
                return $form;
            }
        }
        return null;
    }

    private function getAllFields($form)
    {
        $allFields = [];
        foreach ($form['steps'] as $step) {
            foreach ($step['fields'] as $field) {
                $allFields[] = $field;
            }
        }
        return $allFields;
    }

    // --- Entry Points ---

    // 1. Standalone Form: /form/{slug}
    public function show($slug)
    {
        $form = $this->getStandaloneForm($slug);

        // If not found as standalone, 404. Even if it exists as attached.
        if (!$form) {
            header('HTTP/1.0 404 Not Found');
            require BASE_PATH . '/views/404.php';
            exit;
        }

        // Quota Check
        if ($this->isQuotaFull($form)) {
            $title = 'Quota Full';
            $heading = 'Form Quota Reached';
            $message = 'Sorry, the quota for this form has been reached. Please check back later or contact the admin for more information.';
            $buttonText = 'Back to Home';
            $buttonLink = '/';
            $quota = $form['quota'] ?? null;
            require BASE_PATH . '/views/form-full.php';
            exit;
        }

        $this->renderForm($form);
    }

    public function submit($slug)
    {
        $form = $this->getStandaloneForm($slug);
        if (!$form) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }
        $this->processSubmission($form, '/form/' . $slug);
    }

    // 2. Attached Forms: /{type}/{entity_slug}/{form_slug}
    public function showAttached($type, $entitySlug, $formSlug)
    {
        // 1. Verify Entity Exists
        $entity = $this->getEntity($type, $entitySlug);
        if (!$entity) {
            header('HTTP/1.0 404 Not Found');
            require BASE_PATH . '/views/404.php';
            exit;
        }

        // 2. Find Form by Path
        $form = $this->getFormByPath($type, $entitySlug, $formSlug);
        if (!$form) {
            header('HTTP/1.0 404 Not Found');
            require BASE_PATH . '/views/404.php';
            exit;
        }

        // 3. Check for Advanced Registration - render directly if enabled
        if (!empty($form['registration_settings']['enabled'])) {
            require_once BASE_PATH . '/src/Controller/RegistrationController.php';
            // Pass the form ID and context path for proper routing
            (new RegistrationController())->showByFormId($form['id'], "/$type/$entitySlug/$formSlug");
            return;
        }

        // 4. Quota Check (Internal to Form)
        if ($this->isQuotaFull($form)) {
            $title = 'Registration Closed';
            $heading = 'Registration Closed';
            $message = "Sorry, the quota for this $type has been reached. Please check back later or contact the committee for more information.";
            $buttonText = 'Explore Other ' . ucfirst($type) . 's';
            $buttonLink = '/' . $type . 's';
            if ($type === 'community')
                $buttonLink = '/communities';
            $quota = $form['quota'] ?? null;
            require BASE_PATH . '/views/form-full.php';
            exit;
        }

        // Setup context
        $form['return_url'] = "/$type/$entitySlug";
        $form['submit_url'] = "/$type/$entitySlug/$formSlug/submit";
        $form['event_slug'] = $entitySlug;

        $this->renderForm($form);
    }

    public function submitAttached($type, $entitySlug, $formSlug)
    {
        $entity = $this->getEntity($type, $entitySlug);
        if (!$entity) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $form = $this->getFormByPath($type, $entitySlug, $formSlug);
        if (!$form) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $form['event_slug'] = $entitySlug;
        $this->processSubmission($form, "/$type/$entitySlug/$formSlug");
    }

    // --- Helpers ---

    private function getEntity($type, $slug)
    {
        $entityFile = BASE_PATH . "/data/{$type}s.json";
        if ($type === 'community')
            $entityFile = BASE_PATH . '/data/communities.json';
        if (!file_exists($entityFile))
            return null;

        $entities = json_decode(file_get_contents($entityFile), true);
        foreach ($entities as $e) {
            // Some files use 'id' or 'slug', standardized on slug
            if (($e['slug'] ?? '') === $slug)
                return $e;
        }
        return null;
    }

    // --- Core Logic ---

    private function renderForm($form)
    {
        $steps = $form['steps'];
        $success = $_SESSION['flash_success'] ?? null;
        $errors = $_SESSION['flash_errors'] ?? [];
        $old = $_SESSION['flash_old'] ?? [];

        unset($_SESSION['flash_success'], $_SESSION['flash_errors'], $_SESSION['flash_old']);

        $title = $form['title'];
        $submitUrl = $form['submit_url'] ?? ('/form/' . $form['slug'] . '/submit');
        $returnUrl = $form['return_url'] ?? '/';
        $description = $form['description'] ?? '';

        require BASE_PATH . '/views/form-wizard.php';
    }

    private function processSubmission($form, $redirectUrl)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: $redirectUrl");
            exit;
        }

        if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
            die("CSRF Token validation failed.");
        }

        $fields = $this->getAllFields($form);
        $validator = new Validator($_POST);

        // Get current submissions for duplicate checking (form-specific)
        $formId = $form['id'] ?? '';
        $submissions = $this->getSubmissions($formId);

        // File upload constants
        $allowedFileTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        foreach ($fields as $field) {
            $name = $field['name'];
            $label = $field['label'] ?? ucfirst($name);
            $type = $field['type'] ?? 'text';

            // Required validation
            if ($type === 'file' && !empty($field['required'])) {
                if (empty($_FILES[$name]['name'])) {
                    $validator->addError($name, "$label is required.");
                }
            } elseif (!empty($field['required'])) {
                $validator->required($name, $label);
            }

            // Type-specific validation
            if ($type === 'email') {
                $validator->email($name, $label);
                // Duplicate check for email
                $validator->unique($name, $submissions, $formId, $label);
            }

            if ($type === 'tel') {
                $validator->phone($name, $label);
                // Duplicate check for phone fields (whatsapp)
                if ($name === 'whatsapp') {
                    $validator->unique($name, $submissions, $formId, $label);
                }
            }

            if ($type === 'file') {
                $validator->fileType($name, $allowedFileTypes, $label);
                $validator->fileSize($name, $maxFileSize, $label);
            }
        }

        if (!$validator->passes()) {
            $_SESSION['flash_errors'] = $validator->errors();
            $_SESSION['flash_old'] = $_POST;
            header("Location: $redirectUrl");
            exit;
        }

        // Use file locking for atomic quota check and submission
        $submissionsDir = BASE_PATH . '/data/submissions';
        if (!is_dir($submissionsDir)) {
            mkdir($submissionsDir, 0755, true);
        }
        $submissionsFile = $submissionsDir . '/' . $formId . '.json';
        $fp = fopen($submissionsFile, 'c+');

        if (!$fp) {
            $_SESSION['flash_errors'] = ['general' => 'System error. Please try again later.'];
            header("Location: $redirectUrl");
            exit;
        }

        // Acquire exclusive lock
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            $_SESSION['flash_errors'] = ['general' => 'System is busy. Please try again.'];
            header("Location: $redirectUrl");
            exit;
        }

        try {
            // Re-read submissions with lock held
            $content = stream_get_contents($fp);
            $submissions = $content ? json_decode($content, true) : [];
            if (!is_array($submissions)) {
                $submissions = [];
            }

            // Check Quota with up-to-date data
            $quota = $form['quota'] ?? 0;
            if ($quota > 0) {
                $count = 0;
                foreach ($submissions as $sub) {
                    if (($sub['status'] ?? 'PENDING') !== 'FAILED') {
                        $count++;
                    }
                }
                if ($count >= $quota) {
                    flock($fp, LOCK_UN);
                    fclose($fp);

                    $title = 'Quota Reached';
                    $heading = 'Form Full';
                    $message = 'Unfortunately, the quota for this form was just filled. We cannot accept any more submissions at this time.';
                    $buttonLink = $redirectUrl;
                    $buttonText = 'Go Back';
                    require BASE_PATH . '/views/form-full.php';
                    exit;
                }
            }

            // --- Data Handling ---
            $regId = 'REG-' . strtoupper(substr(uniqid(), -6));
            $data = [
                'registration_id' => $regId,
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'PENDING',
                'form_id' => $formId,
                'context_type' => $form['context_type'] ?? 'standalone',
                'context_id' => $form['context_id'] ?? '',
                'slug' => $form['slug'] ?? ''
            ];

            foreach ($fields as $field) {
                $name = $field['name'];
                $type = $field['type'] ?? 'text';

                if ($type === 'file') {
                    $data[$name] = $this->handleFileUpload($name);
                } elseif ($type === 'tel') {
                    // Normalize phone numbers
                    $data[$name] = Validator::normalizePhone($_POST[$name] ?? '');
                } else {
                    $data[$name] = $_POST[$name] ?? '';
                }
            }

            // Append and save
            $submissions[] = $data;

            // Truncate and write
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        $_SESSION['flash_success'] = "Registration Successful! Your registration ID is: $regId";
        header("Location: $redirectUrl");
        exit;
    }

    private function handleFileUpload($fieldName)
    {
        if (empty($_FILES[$fieldName]['name']))
            return null;
        $targetDir = UPLOAD_PATH;
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = basename($_FILES[$fieldName]['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $ext;
        $targetFile = $targetDir . $newFileName;
        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetFile)) {
            return url('public/assets/uploads/' . $newFileName);
        }
        return null;
    }

    private function isQuotaFull($form)
    {
        $quota = $form['quota'] ?? 0;
        if ($quota <= 0)
            return false;

        $submissions = $this->getSubmissions();
        $formId = $form['id'];

        $count = 0;
        foreach ($submissions as $sub) {
            if (($sub['form_id'] ?? '') === $formId && ($sub['status'] ?? 'PENDING') !== 'FAILED') {
                $count++;
            }
        }

        return $count >= $quota;
    }
}