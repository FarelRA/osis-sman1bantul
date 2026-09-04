<?php
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Router.php';

$router = new Router();

$router->get('/', function () {
    require BASE_PATH . '/views/home.php';
});

$router->get('/about', function () {
    require BASE_PATH . '/views/about.php';
});

$router->get('/contact', function () {
    require BASE_PATH . '/views/contact.php';
});

$router->get('/sekbid', function () {
    require BASE_PATH . '/views/sekbid.php';
});

$router->get('/twibbon', function () {
    require BASE_PATH . '/views/twibbon.php';
});

$router->get('/blogs', function () {
    require BASE_PATH . '/views/blogs.php';
});

$router->get('/blog/{slug}', function ($slug) {
    $blogs = json_decode(file_get_contents(BASE_PATH . '/data/blogs.json'), true);
    $blog = null;
    foreach ($blogs as $b) {
        if ($b['slug'] === $slug) {
            $blog = $b;
            break;
        }
    }
    if (!$blog) {
        header('HTTP/1.0 404 Not Found');
        require BASE_PATH . '/views/404.php';
        exit;
    }
    require BASE_PATH . '/views/blog-detail.php';
});

$router->get('/events', function () {
    require BASE_PATH . '/views/events.php';
});

$router->get('/event/{slug}', function ($slug) {
    $events = json_decode(file_get_contents(BASE_PATH . '/data/events.json'), true);
    $event = null;
    foreach ($events as $e) {
        if ($e['slug'] === $slug) {
            $event = $e;
            break;
        }
    }
    if (!$event) {
        header('HTTP/1.0 404 Not Found');
        require BASE_PATH . '/views/404.php';
        exit;
    }
    require BASE_PATH . '/views/event-detail.php';
});

$router->get('/clubs', function () {
    require BASE_PATH . '/views/clubs.php';
});

$router->get('/club/{slug}', function ($slug) {
    $clubs = json_decode(file_get_contents(BASE_PATH . '/data/clubs.json'), true);
    $club = null;
    foreach ($clubs as $org) {
        if ($org['slug'] === $slug) {
            $club = $org;
            break;
        }
    }
    if (!$club) {
        header('HTTP/1.0 404 Not Found');
        require BASE_PATH . '/views/404.php';
        exit;
    }
    require BASE_PATH . '/views/club-detail.php';
});

$router->get('/communities', function () {
    require BASE_PATH . '/views/communities.php';
});

$router->get('/community/{slug}', function ($slug) {
    $communities = json_decode(file_get_contents(BASE_PATH . '/data/communities.json'), true);
    $community = null;
    foreach ($communities as $c) {
        if ($c['slug'] === $slug) {
            $community = $c;
            break;
        }
    }
    if (!$community) {
        header('HTTP/1.0 404 Not Found');
        require BASE_PATH . '/views/404.php';
        exit;
    }
    require BASE_PATH . '/views/community-detail.php';
});

// --- Form System Routes ---

// 1. Attached Forms (Event/Club/Community)
// Matches: /event/sintesa-2026/daftar
$router->get('/{type}/{entity_slug}/{form_slug}', function ($type, $entity_slug, $form_slug) {
    if (in_array($type, ['event', 'club', 'community'])) {
        require_once BASE_PATH . '/src/Controller/FormController.php';
        (new FormController())->showAttached($type, $entity_slug, $form_slug);
    } else {
        http_response_code(404);
        require BASE_PATH . '/views/404.php';
    }
});

// Advanced Registration routes for attached forms
$router->post('/{type}/{entity_slug}/{form_slug}/step/{step}', function ($type, $entity_slug, $form_slug, $step) {
    if (in_array($type, ['event', 'club', 'community'])) {
        require_once BASE_PATH . '/src/Controller/RegistrationController.php';
        // Use session to get form ID
        $formId = $_SESSION['registration_form_id'] ?? null;
        if ($formId) {
            (new RegistrationController())->saveStepByFormId($formId, (int) $step, "/$type/$entity_slug/$form_slug");
        }
    }
});

$router->post('/{type}/{entity_slug}/{form_slug}/upload', function ($type, $entity_slug, $form_slug) {
    if (in_array($type, ['event', 'club', 'community'])) {
        require_once BASE_PATH . '/src/Controller/RegistrationController.php';
        $formId = $_SESSION['registration_form_id'] ?? null;
        if ($formId) {
            (new RegistrationController())->uploadByFormId($formId);
        }
    }
});

$router->post('/{type}/{entity_slug}/{form_slug}/submit', function ($type, $entity_slug, $form_slug) {
    if (in_array($type, ['event', 'club', 'community'])) {
        require_once BASE_PATH . '/src/Controller/FormController.php';
        (new FormController())->submitAttached($type, $entity_slug, $form_slug);
    }
});

// 2. Standalone Forms
// Matches: /form/sintesa-2026-daftar
$router->get('/form/{slug}', function ($slug) {
    require_once BASE_PATH . '/src/Controller/FormController.php';
    (new FormController())->show($slug);
});

$router->post('/form/{slug}/submit', function ($slug) {
    require_once BASE_PATH . '/src/Controller/FormController.php';
    (new FormController())->submit($slug);
});

// --- Advanced Registration System Routes ---

// Registration Flow
$router->get('/register/{form_slug}', function ($form_slug) {
    require_once BASE_PATH . '/src/Controller/RegistrationController.php';
    (new RegistrationController())->show($form_slug);
});

$router->post('/register/{form_slug}/step/{step}', function ($form_slug, $step) {
    require_once BASE_PATH . '/src/Controller/RegistrationController.php';
    (new RegistrationController())->saveStep($form_slug, (int) $step);
});

$router->post('/register/{form_slug}/upload', function ($form_slug) {
    require_once BASE_PATH . '/src/Controller/RegistrationController.php';
    (new RegistrationController())->upload($form_slug);
});

$router->get('/register/{form_slug}/status', function ($form_slug) {
    require_once BASE_PATH . '/src/Controller/RegistrationController.php';
    (new RegistrationController())->checkStatus($form_slug);
});

// API Endpoints
$router->post('/api/registration/save', function () {
    require_once BASE_PATH . '/src/Controller/RegistrationController.php';
    (new RegistrationController())->autoSave();
});

// Cookie Restore (for shared registration links)
$router->get('/restore', function () {
    require BASE_PATH . '/views/restore-registration.php';
});

// --- Internal Apps Generic Router ---

$router->get('/internal/{app_slug}', function ($app_slug) {
    $slug = basename($app_slug);
    $slug = preg_replace('/[^\w\-]/', '', $slug);
    if ($slug === '' || $slug !== $app_slug) {
        http_response_code(404);
        require BASE_PATH . '/views/404.php';
        return;
    }
    $indexPath = BASE_PATH . '/views/internal/' . $slug . '/index.php';
    if (file_exists($indexPath)) {
        require $indexPath;
    } else {
        http_response_code(404);
        require BASE_PATH . '/views/404.php';
    }
});

$router->post('/internal/{app_slug}', function ($app_slug) {
    $slug = basename($app_slug);
    $slug = preg_replace('/[^\w\-]/', '', $slug);
    if ($slug === '' || $slug !== $app_slug) {
        http_response_code(404);
        require BASE_PATH . '/views/404.php';
        return;
    }
    $indexPath = BASE_PATH . '/views/internal/' . $slug . '/index.php';
    if (file_exists($indexPath)) {
        require $indexPath;
    } else {
        http_response_code(404);
        require BASE_PATH . '/views/404.php';
    }
});

$router->run();
