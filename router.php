<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$blockedPrefixes = [
    '/.git', '/.env', '/data/', '/src/', '/sessions/', '/views/',
    '/vendor/', '/node_modules/', '/router.php',
];
foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($uri, $prefix)) {
        http_response_code(403);
        header('X-Content-Type-Options: nosniff');
        echo 'Forbidden';
        exit;
    }
}

if (str_ends_with($uri, '.php') && $uri !== '/index.php' && !str_starts_with($uri, '/dash/')) {
    http_response_code(403);
    header('X-Content-Type-Options: nosniff');
    echo 'Forbidden';
    exit;
}

$ext = pathinfo($uri, PATHINFO_EXTENSION);
if ($ext === 'json' && !str_starts_with($uri, '/public/')) {
    http_response_code(403);
    header('X-Content-Type-Options: nosniff');
    echo 'Forbidden';
    exit;
}

if (str_contains($uri, '..') || str_contains($uri, "\0")) {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

return false;
