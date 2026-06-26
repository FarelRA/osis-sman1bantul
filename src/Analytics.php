<?php
function trackVisit()
{
    $analytics_file = BASE_PATH . '/data/analytics.json';

    // Load existing data
    $analytics = file_exists($analytics_file) ? json_decode(file_get_contents($analytics_file), true) : [];

    // Get visitor info
    $visit = [
        'timestamp' => date('Y-m-d H:i:s'),
        'page' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? 'direct'
    ];

    // Add to analytics
    $analytics[] = $visit;

    // Keep only last 10000 visits to prevent file from getting too large
    if (count($analytics) > 10000) {
        $analytics = array_slice($analytics, -10000);
    }

    // Save
    file_put_contents($analytics_file, json_encode($analytics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getAnalytics()
{
    $analytics_file = BASE_PATH . '/data/analytics.json';
    return file_exists($analytics_file) ? json_decode(file_get_contents($analytics_file), true) : [];
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
