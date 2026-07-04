<?php
/**
 * SMS Enterprise — Local Audit Script
 * Run from project root: php audit.php
 * Checks syntax, missing views, broken routes, inline model calls
 */

$base    = __DIR__;
$errors  = [];
$warnings = [];
$ok      = 0;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       SMS Enterprise — Project Audit                     ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ── 1. PHP SYNTAX CHECK — All Controllers ────────────────────────────
echo "[ 1/6 ] Checking PHP syntax in Controllers...\n";

$controllers = glob($base . '/app/Http/Controllers/*.php');
sort($controllers);

foreach ($controllers as $file) {
    $name   = basename($file);
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    if (str_contains($output, 'error') || str_contains($output, 'Error')) {
        $errors[] = "[SYNTAX] {$name}: " . trim($output);
        echo "   ❌ {$name}\n";
        echo "      " . trim($output) . "\n";
    } else {
        $ok++;
        echo "   ✅ {$name}\n";
    }
}

// ── 2. PHP SYNTAX CHECK — Models ─────────────────────────────────────
echo "\n[ 2/6 ] Checking PHP syntax in Models...\n";

$models = glob($base . '/app/Models/*.php');
sort($models);

foreach ($models as $file) {
    $name   = basename($file);
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    if (str_contains($output, 'error') || str_contains($output, 'Error')) {
        $errors[] = "[SYNTAX] Model/{$name}: " . trim($output);
        echo "   ❌ {$name}: " . trim($output) . "\n";
    } else {
        $ok++;
    }
}
echo "   ✅ All models checked\n";

// ── 3. VIEWS REFERENCED IN CONTROLLERS (check exist) ─────────────────
echo "\n[ 3/6 ] Checking views referenced in controllers...\n";

$viewBase  = $base . '/resources/views';
$allViews  = [];
$rii       = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewBase));
foreach ($rii as $f) {
    if ($f->getExtension() === 'php') {
        $rel = str_replace($viewBase . DIRECTORY_SEPARATOR, '', $f->getPathname());
        $rel = str_replace([DIRECTORY_SEPARATOR, '.blade.php'], ['.', ''], $rel);
        $allViews[] = $rel;
    }
}

$missingViews = [];
foreach (glob($base . '/app/Http/Controllers/*.php') as $file) {
    $ctrl    = basename($file);
    $content = file_get_contents($file);
    preg_match_all("/view\('([^']+)'/", $content, $m);
    foreach ($m[1] as $view) {
        if (!in_array($view, $allViews)) {
            $missingViews[] = "[MISSING VIEW] {$ctrl} → view('{$view}')";
            echo "   ❌ {$ctrl} → view('{$view}')\n";
        }
    }
}
if (empty($missingViews)) {
    echo "   ✅ All referenced views exist\n";
}

// ── 4. INLINE MODEL CALLS IN VIEWS ───────────────────────────────────
echo "\n[ 4/6 ] Checking for inline model calls in views...\n";

$inlineIssues = [];
$rii2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewBase));
foreach ($rii2 as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') continue;
    $content = file_get_contents($f->getPathname());
    $lines   = explode("\n", $content);
    $rel     = str_replace($viewBase . DIRECTORY_SEPARATOR, '', $f->getPathname());
    foreach ($lines as $i => $line) {
        if (str_contains($line, 'App\\Models\\') && str_contains($line, '::')) {
            $inlineIssues[] = "[INLINE MODEL] {$rel}:L" . ($i+1) . ": " . trim(substr($line, 0, 90));
            echo "   ❌ {$rel}:L" . ($i+1) . "\n";
            echo "      " . trim(substr($line, 0, 80)) . "\n";
        }
    }
}
if (empty($inlineIssues)) {
    echo "   ✅ No inline model calls in views\n";
}

// ── 5. TENANT is_active BUG SCAN ─────────────────────────────────────
echo "\n[ 5/6 ] Checking for Tenant::where('is_active') bug...\n";

$tenantBugs = [];
foreach (glob($base . '/app/Http/Controllers/*.php') as $file) {
    $ctrl    = basename($file);
    $content = file_get_contents($file);
    $lines   = explode("\n", $content);
    foreach ($lines as $i => $line) {
        // Only flag is_active when querying Tenant (not other models)
        if (str_contains($line, "is_active") && str_contains($line, 'Tenant')) {
            $tenantBugs[] = "[TENANT BUG] {$ctrl}:L" . ($i+1) . ": " . trim($line);
            echo "   ❌ {$ctrl}:L" . ($i+1) . ": " . trim(substr($line, 0, 80)) . "\n";
        }
    }
}
if (empty($tenantBugs)) {
    echo "   ✅ No Tenant::is_active bugs found\n";
}

// ── 6. DUPLICATE METHOD NAMES IN CONTROLLERS ─────────────────────────
echo "\n[ 6/6 ] Checking for duplicate methods in controllers...\n";

$dupeMethods = [];
foreach (glob($base . '/app/Http/Controllers/*.php') as $file) {
    $ctrl    = basename($file);
    $content = file_get_contents($file);
    preg_match_all('/public function (\w+)\s*\(/', $content, $m);
    $counts = array_count_values($m[1]);
    foreach ($counts as $method => $count) {
        if ($count > 1) {
            $dupeMethods[] = "[DUPE METHOD] {$ctrl}::{$method}() appears {$count}x";
            echo "   ❌ {$ctrl}::{$method}() × {$count}\n";
        }
    }
}
if (empty($dupeMethods)) {
    echo "   ✅ No duplicate methods found\n";
}

// ── SUMMARY ──────────────────────────────────────────────────────────
$allIssues = array_merge($errors, $missingViews, $inlineIssues, $tenantBugs, $dupeMethods);
echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  AUDIT COMPLETE                                          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "  Total issues found : " . count($allIssues) . "\n";
echo "  Files checked OK   : {$ok}\n\n";

if (!empty($allIssues)) {
    echo "ALL ISSUES:\n";
    foreach ($allIssues as $issue) {
        echo "  • {$issue}\n";
    }

    // Write report to file
    $report  = "SMS Enterprise Audit Report — " . date('Y-m-d H:i:s') . "\n";
    $report .= str_repeat("=", 60) . "\n\n";
    foreach ($allIssues as $issue) {
        $report .= $issue . "\n";
    }
    file_put_contents($base . '/audit_report.txt', $report);
    echo "\n  Full report saved to: audit_report.txt\n";
} else {
    echo "  ✅ No issues found — project is clean!\n";
}
echo "\n";
