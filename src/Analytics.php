<?php
function trackVisit()
{
    $file = BASE_PATH . '/data/analytics.jsonl';

    $visit = [
        'timestamp' => date('Y-m-d H:i:s'),
        'page' => substr($_SERVER['REQUEST_URI'] ?? '/', 0, 500),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'ip' => filter_var($_SERVER['REMOTE_ADDR'] ?? 'unknown', FILTER_VALIDATE_IP) ?: 'unknown',
        'user_agent' => substr(preg_replace('/[^\x20-\x7E]/', '', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 300),
        'referrer' => substr(preg_replace('/[^\x20-\x7E]/', '', $_SERVER['HTTP_REFERER'] ?? 'direct'), 0, 500)
    ];

    file_put_contents($file, json_encode($visit, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function getAnalytics()
{
    $file = BASE_PATH . '/data/analytics.jsonl';
    if (!file_exists($file)) return [];

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $visits = [];
    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) $visits[] = $decoded;
    }
    return $visits;
}

function getAnalyticsStats($range = '7d')
{
    $visits = getAnalytics();

    if (empty($visits)) {
        return [
            'total_visits' => 0,
            'unique_visitors' => 0,
            'pages' => [],
            'recent_visits' => [],
            'visits_by_period' => []
        ];
    }

    // Parse range
    $now = time();
    $ranges = [
        '12h' => ['seconds' => 12 * 3600, 'format' => 'H:00', 'group' => 'Y-m-d H'],
        '24h' => ['seconds' => 24 * 3600, 'format' => 'H:00', 'group' => 'Y-m-d H'],
        '3d' => ['seconds' => 3 * 86400, 'format' => 'M d', 'group' => 'Y-m-d'],
        '7d' => ['seconds' => 7 * 86400, 'format' => 'M d', 'group' => 'Y-m-d'],
        '30d' => ['seconds' => 30 * 86400, 'format' => 'M d', 'group' => 'Y-m-d'],
        '1y' => ['seconds' => 365 * 86400, 'format' => 'M Y', 'group' => 'Y-m'],
        '2y' => ['seconds' => 730 * 86400, 'format' => 'M Y', 'group' => 'Y-m']
    ];

    $config = $ranges[$range] ?? $ranges['7d'];
    $cutoff = $now - $config['seconds'];

    // Filter visits by time range
    $filtered_visits = array_filter($visits, function ($visit) use ($cutoff) {
        return strtotime($visit['timestamp']) >= $cutoff;
    });

    // Calculate stats
    $unique_ips = array_unique(array_column($filtered_visits, 'ip'));
    $pages = [];
    $visits_by_period = [];

    foreach ($filtered_visits as $visit) {
        // Count page visits
        $page = $visit['page'];
        $pages[$page] = ($pages[$page] ?? 0) + 1;

        // Count visits by period
        $timestamp = strtotime($visit['timestamp']);
        $period = date($config['group'], $timestamp);
        $visits_by_period[$period] = ($visits_by_period[$period] ?? 0) + 1;
    }

    // Sort pages by visits
    arsort($pages);

    // Sort periods
    ksort($visits_by_period);

    // Format period labels
    $formatted_periods = [];
    foreach ($visits_by_period as $period => $count) {
        // For hourly data, append :00:00 to make it parseable
        if (strlen($period) == 13 && substr($period, 10, 1) == ' ') {
            $period .= ':00:00';
        } elseif (strlen($period) == 7) {
            // For monthly data (Y-m), append -01
            $period .= '-01';
        }
        $label = date($config['format'], strtotime($period));
        $formatted_periods[$label] = $count;
    }

    return [
        'total_visits' => count($filtered_visits),
        'unique_visitors' => count($unique_ips),
        'pages' => $pages,
        'recent_visits' => array_slice(array_reverse($filtered_visits), 0, 50),
        'visits_by_period' => $formatted_periods
    ];
}
