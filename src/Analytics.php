<?php
// Analytics - memory-safe implementation.
//
// The previous version used file() which loads the entire analytics.jsonl
// into memory at once, exhausting the 128M limit once the log grew large
// (Fatal error in Analytics.php line 26). All readers below stream the
// file line-by-line so memory stays flat regardless of log size.

if (!defined('ANALYTICS_MAX_BYTES')) define('ANALYTICS_MAX_BYTES', 20 * 1024 * 1024); // rotate past ~20MB
if (!defined('ANALYTICS_MAX_LINES')) define('ANALYTICS_MAX_LINES', 100000);           // keep last ~100k visits
if (!defined('ANALYTICS_RECENT_LIMIT')) define('ANALYTICS_RECENT_LIMIT', 50);
if (!defined('ANALYTICS_MAX_PAGES')) define('ANALYTICS_MAX_PAGES', 2000);             // cap distinct page keys

function analyticsFile(): string
{
    return BASE_PATH . '/data/analytics.jsonl';
}

function trackVisit()
{
    // Only track web GET requests; skip POST autosaves, admin, bots, static assets.
    // (Under `php -S` the SAPI is cli-server, so this still tracks in prod;
    // pure CLI has no REQUEST_URI and is skipped below.)
    if (!isset($_SERVER['REQUEST_URI'])) return;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    // Don't pollute stats with dashboard traffic.
    if (str_starts_with($path, '/dash') || str_starts_with($path, '/internal/console')) return;

    // Skip static assets (in case a front controller serves them).
    if (preg_match('/\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|map|txt|xml)$/i', $path)) return;

    // Skip common bots/crawlers to slow log growth.
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua !== '' && preg_match('/bot|crawl|spider|slurp|mediapartners|baidu|yandex|sogou|semrush|ahrefs|mj12|dotbot|petal|gptbot|ccbot|anthropic|python-requests|curl|wget|uptime|monitor|pingdom/i', $ua)) return;

    $file = analyticsFile();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) return;
    }

    $visit = [
        'timestamp' => date('Y-m-d H:i:s'),
        'page' => substr($uri, 0, 500),
        'method' => 'GET',
        'ip' => filter_var($_SERVER['REMOTE_ADDR'] ?? 'unknown', FILTER_VALIDATE_IP) ?: 'unknown',
        'user_agent' => substr(preg_replace('/[^\x20-\x7E]/', '', $ua ?: 'unknown'), 0, 300),
        'referrer' => substr(preg_replace('/[^\x20-\x7E]/', '', $_SERVER['HTTP_REFERER'] ?? 'direct'), 0, 500)
    ];

    @file_put_contents($file, json_encode($visit, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

    // Opportunistic rotation: cheap size check, prune only ~1% of requests
    // so tracking stays fast while the log stays bounded.
    try {
        clearstatcache(true, $file);
        $size = @filesize($file);
        if ($size !== false && $size > ANALYTICS_MAX_BYTES && random_int(1, 100) === 1) {
            pruneAnalytics();
        }
    } catch (Throwable $e) {
        // never break the request because of analytics
    }
}

/**
 * Trim the log to the most recent ANALYTICS_MAX_LINES lines.
 * Fully streaming (two passes, O(1) memory) + atomic rename, so concurrent
 * readers never see a partial file.
 */
function pruneAnalytics(): void
{
    $file = analyticsFile();
    if (!file_exists($file)) return;

    // Pass 1: count lines (streaming).
    $total = 0;
    $fp = @fopen($file, 'rb');
    if (!$fp) return;
    while (!feof($fp)) {
        $line = fgets($fp);
        if ($line === false) break;
        if (rtrim($line, "\r\n") !== '') $total++;
    }
    fclose($fp);

    if ($total <= ANALYTICS_MAX_LINES) return;
    $skip = $total - ANALYTICS_MAX_LINES;

    // Pass 2: copy the tail to a temp file (streaming).
    $in = @fopen($file, 'rb');
    if (!$in) return;
    $tmp = $file . '.tmp.' . getmypid();
    $out = @fopen($tmp, 'wb');
    if (!$out) {
        fclose($in);
        return;
    }
    if (function_exists('flock')) @flock($out, LOCK_EX);
    $seen = 0;
    while (!feof($in)) {
        $line = fgets($in);
        if ($line === false) break;
        if (rtrim($line, "\r\n") === '') continue;
        if ($seen < $skip) {
            $seen++;
            continue;
        }
        fwrite($out, rtrim($line, "\r\n") . "\n");
    }
    fclose($in);
    if (function_exists('flock')) @flock($out, LOCK_UN);
    fclose($out);
    @rename($tmp, $file);
    @unlink($tmp); // no-op if rename succeeded
}

/**
 * Read the last $limit lines of $file without loading the whole file.
 * Returns lines oldest-first (file order), without trailing newlines.
 * Only buffers ~$limit lines from the tail, so memory stays flat.
 */
function analyticsTailLines(string $file, int $limit): array
{
    if ($limit <= 0) return [];
    $fp = @fopen($file, 'rb');
    if (!$fp) return [];

    $stat = @fstat($fp);
    $size = $stat['size'] ?? 0;
    if ($size <= 0) {
        fclose($fp);
        return [];
    }

    // Small files: single streaming pass, keep only the tail.
    if ($size <= 256 * 1024) {
        $lines = [];
        while (!feof($fp)) {
            $line = fgets($fp);
            if ($line === false) break;
            $line = rtrim($line, "\r\n");
            if ($line === '') continue;
            $lines[] = $line;
            if (count($lines) > $limit) array_shift($lines);
        }
        fclose($fp);
        return $lines;
    }

    // Large files: accumulate chunks from the end until we hold >$limit newlines.
    $chunkSize = 65536;
    $pos = $size;
    $data = '';
    $newlines = 0;
    while ($pos > 0 && $newlines <= $limit) {
        $read = $chunkSize < $pos ? $chunkSize : $pos;
        $pos -= $read;
        if (fseek($fp, $pos) !== 0) break;
        $chunk = fread($fp, $read);
        if ($chunk === false || $chunk === '') break;
        $data = $chunk . $data;
        $newlines = substr_count($data, "\n");
    }
    fclose($fp);

    $parts = explode("\n", $data);
    // Drop trailing empty element when the file ends with a newline.
    while (!empty($parts) && rtrim(end($parts), "\r") === '') array_pop($parts);
    // If we stopped before the file head, the first element is a partial
    // (mid-line) cut — drop it.
    if ($pos > 0 && !empty($parts)) array_shift($parts);

    $lines = [];
    foreach ($parts as $p) {
        $p = rtrim($p, "\r");
        if ($p === '') continue;
        $lines[] = $p;
    }
    if (count($lines) > $limit) $lines = array_slice($lines, -$limit);
    // Skip garbage/partial lines that are not valid JSON.
    $lines = array_values(array_filter($lines, function ($l) {
        $d = json_decode($l, true);
        return is_array($d);
    }));
    return $lines;
}

/**
 * Backward-compatible reader. Previously returned ALL visits (which OOM'd).
 * Now returns at most $limit most-recent visits, oldest-first.
 */
function getAnalytics($limit = 5000)
{
    $file = analyticsFile();
    if (!file_exists($file)) return [];

    $limit = (int) $limit;
    if ($limit <= 0) return [];

    $lines = analyticsTailLines($file, $limit);
    $visits = [];
    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) $visits[] = $decoded;
    }
    return $visits;
}

/**
 * Memory-efficient count of today's visits (streams the file, O(1) memory).
 */
function countVisitsToday(): int
{
    $file = analyticsFile();
    if (!file_exists($file)) return 0;

    $today = date('Y-m-d');
    $count = 0;
    $fp = @fopen($file, 'rb');
    if (!$fp) return 0;
    while (!feof($fp)) {
        $line = fgets($fp);
        if ($line === false || $line === '') continue;
        $decoded = json_decode($line, true);
        if (!is_array($decoded) || !isset($decoded['timestamp'])) continue;
        if (substr($decoded['timestamp'], 0, 10) === $today) $count++;
    }
    fclose($fp);
    return $count;
}

function getAnalyticsStats($range = '7d')
{
    $empty = [
        'total_visits' => 0,
        'unique_visitors' => 0,
        'pages' => [],
        'recent_visits' => [],
        'visits_by_period' => []
    ];

    $file = analyticsFile();
    if (!file_exists($file)) return $empty;

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
    // String comparison works for 'Y-m-d H:i:s' (lexicographic == chronological).
    $cutoffStr = date('Y-m-d H:i:s', $now - $config['seconds']);
    $isHourly = $config['group'] === 'Y-m-d H';
    $isMonthly = $config['group'] === 'Y-m';

    // Single streaming pass: aggregate without storing every visit.
    $total = 0;
    $uniqueIps = [];
    $pages = [];
    $pagesOther = 0;
    $visitsByPeriod = [];
    $recent = []; // rolling buffer of last 50 matching visits (oldest-first)

    $fp = @fopen($file, 'rb');
    if (!$fp) return $empty;

    while (!feof($fp)) {
        $line = fgets($fp);
        if ($line === false || $line === '') continue;
        // Fast pre-filter: timestamp JSON field starts with '"timestamp":"YYYY-...'
        // Skip full decode for lines clearly older than cutoff.
        $ts = null;
        if (preg_match('/"timestamp"\s*:\s*"([^"]+)"/', $line, $m)) {
            $ts = $m[1];
            if ($ts < $cutoffStr) continue;
        }
        $visit = json_decode($line, true);
        if (!is_array($visit) || !isset($visit['timestamp'])) continue;
        $timestamp = $visit['timestamp'];
        if (!is_string($timestamp) || $timestamp < $cutoffStr) continue;

        $total++;

        $ip = $visit['ip'] ?? 'unknown';
        if (is_string($ip) && $ip !== '') $uniqueIps[$ip] = true;

        $page = isset($visit['page']) && is_string($visit['page']) ? $visit['page'] : '/';
        if (!isset($pages[$page])) {
            if (count($pages) < ANALYTICS_MAX_PAGES) {
                $pages[$page] = 0;
            } else {
                $pagesOther++;
                $page = null; // counted under _other below
            }
        }
        if ($page !== null) $pages[$page]++;

        // Group key from timestamp prefix — no strtotime needed.
        if ($isHourly) {
            $period = substr($timestamp, 0, 13); // 'Y-m-d H'
        } elseif ($isMonthly) {
            $period = substr($timestamp, 0, 7);  // 'Y-m'
        } else {
            $period = substr($timestamp, 0, 10); // 'Y-m-d'
        }
        if (strlen($period) >= 7) $visitsByPeriod[$period] = ($visitsByPeriod[$period] ?? 0) + 1;

        $recent[] = $visit;
        if (count($recent) > ANALYTICS_RECENT_LIMIT) array_shift($recent);
    }
    fclose($fp);

    if ($total === 0) return $empty;

    if ($pagesOther > 0) $pages['_other'] = $pagesOther;

    // Sort pages by visits
    arsort($pages);

    // Sort periods
    ksort($visitsByPeriod);

    // Format period labels
    $formattedPeriods = [];
    foreach ($visitsByPeriod as $period => $count) {
        if ($isHourly) {
            $labelTs = strtotime($period . ':00:00');
        } elseif ($isMonthly) {
            $labelTs = strtotime($period . '-01');
        } else {
            $labelTs = strtotime($period);
        }
        $label = $labelTs !== false ? date($config['format'], $labelTs) : (string) $period;
        // Merge duplicate labels (e.g. hourly 'H:00' collisions across days are
        // already distinct keys here; daily/monthly labels are unique).
        if (isset($formattedPeriods[$label])) {
            $formattedPeriods[$label] += $count;
        } else {
            $formattedPeriods[$label] = $count;
        }
    }

    return [
        'total_visits' => $total,
        'unique_visitors' => count($uniqueIps),
        'pages' => $pages,
        'recent_visits' => array_reverse($recent),
        'visits_by_period' => $formattedPeriods
    ];
}
