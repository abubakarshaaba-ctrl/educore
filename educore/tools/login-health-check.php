<?php
/**
 * login-health-check.php — Guard against the failure classes that broke login.
 *
 * Run from CLI:      php educore/tools/login-health-check.php
 * Or deploy to web:  copy to public_html/ and open ?token=<deploy token>
 *
 * Checks:
 *   1. BOM in every request-critical PHP file (BOM before headers silently
 *      kills ALL Set-Cookie / security headers → 419 login loop).
 *   2. BOM in compiled Blade views (the actual 2026-07-04 culprit).
 *   3. .htaccess still has LiteSpeed CacheDisable for /login.
 *   4. Live GET /login returns a Set-Cookie header (when run with --live).
 *
 * Exit code 0 = healthy, 1 = problems found.
 */

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    if (!isset($_GET['token']) || $_GET['token'] !== getenv('EDUCORE_DEPLOY_TOKEN')) {
        http_response_code(403);
        exit('Forbidden — set EDUCORE_DEPLOY_TOKEN or run from CLI.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// Resolve public_html root whether run from tools/, educore/, or public_html/
$root = __DIR__;
while ($root !== dirname($root) && !file_exists($root . '/educore/bootstrap/app.php')) {
    $root = dirname($root);
}
if (!file_exists($root . '/educore/bootstrap/app.php')) {
    fwrite(STDERR, "Cannot locate public_html root (educore/bootstrap/app.php not found).\n");
    exit(1);
}

$bom = "\xEF\xBB\xBF";
$problems = [];
$checked = 0;

$hasBom = function (string $file) use ($bom): bool {
    $fh = fopen($file, 'rb');
    $first = fread($fh, 3);
    fclose($fh);
    return $first === $bom;
};

// ── 1. Request-critical PHP files ──────────────────────────────────────────
$critical = [
    $root . '/index.php',
    $root . '/educore/.env',
    $root . '/educore/bootstrap/app.php',
    $root . '/educore/bootstrap/providers.php',
    $root . '/educore/routes/web.php',
];
foreach (glob($root . '/educore/config/*.php') ?: [] as $f) {
    $critical[] = $f;
}
foreach (glob($root . '/educore/app/Http/Middleware/*.php') ?: [] as $f) {
    $critical[] = $f;
}
foreach (glob($root . '/educore/app/Http/Controllers/Auth/*.php') ?: [] as $f) {
    $critical[] = $f;
}
foreach (glob($root . '/educore/resources/views/auth/*.blade.php') ?: [] as $f) {
    $critical[] = $f;
}
foreach (glob($root . '/educore/resources/views/layouts/*.blade.php') ?: [] as $f) {
    $critical[] = $f;
}

foreach ($critical as $file) {
    if (!file_exists($file)) continue;
    $checked++;
    if ($hasBom($file)) {
        $problems[] = "BOM in source file: {$file}";
    }
}

// ── 2. Compiled Blade views ─────────────────────────────────────────────────
foreach (glob($root . '/educore/storage/framework/views/*.php') ?: [] as $file) {
    $checked++;
    if ($hasBom($file)) {
        $problems[] = "BOM in compiled view (delete it, it will regenerate): {$file}";
    }
}

// ── 3. .htaccess CacheDisable guard ─────────────────────────────────────────
$htaccess = @file_get_contents($root . '/.htaccess') ?: '';
if (!str_contains($htaccess, 'CacheDisable public /login')) {
    $problems[] = ".htaccess is missing 'CacheDisable public /login' — LiteSpeed may cache the login page and strip Set-Cookie";
}

// ── 4. Live header check (opt-in: --live or ?live=1) ───────────────────────
$live = $isCli ? in_array('--live', $argv ?? [], true) : isset($_GET['live']);
if ($live && function_exists('curl_init')) {
    $ch = curl_init('https://educoreng.online/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = (string) curl_exec($ch);
    curl_close($ch);

    if ($resp === '') {
        $problems[] = 'Live check: could not reach https://educoreng.online/login';
    } elseif (stripos($resp, 'set-cookie:') === false) {
        $problems[] = 'Live check: GET /login has NO Set-Cookie header — output-before-headers bug is back (clear compiled views, scan for BOMs)';
    }
}

// ── Report ──────────────────────────────────────────────────────────────────
echo "=== EDUCORE LOGIN HEALTH CHECK ===\n\n";
echo "Files checked: {$checked}\n";
echo $live ? "Live header check: yes\n" : "Live header check: skipped (pass --live / ?live=1)\n";
echo "\n";

if ($problems) {
    echo "PROBLEMS FOUND (" . count($problems) . "):\n";
    foreach ($problems as $p) {
        echo "  ✗ {$p}\n";
    }
    echo "\nFix BOMs by stripping the first 3 bytes (EF BB BF) or re-saving the file as UTF-8 WITHOUT BOM.\n";
    echo "Never edit PHP/Blade files with the cPanel editor — it adds BOMs.\n";
    exit(1);
}

echo "✓ All clear — no BOMs, cache guard present.\n";
exit(0);
