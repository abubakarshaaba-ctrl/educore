<?php

declare(strict_types=1);

/**
 * Temporary, read-only EduCore production diagnostic.
 *
 * URL:
 *   /brand/educore-diagnostic.php?token=<DEPLOY_TOKEN>
 *
 * Authentication intentionally mirrors the shell-free deploy endpoint:
 * DEPLOY_TOKEN when configured, otherwise the APP_KEY-derived fallback.
 *
 * This file does not mutate application state, clear caches, run migrations,
 * or expose environment secrets.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$docroot = dirname(__DIR__);
$appRoot = $docroot . '/educore';
$envPath = $appRoot . '/.env';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function readEnvFile(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
                || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $env[$key] = $value;
    }

    return $env;
}

function tailFile(string $path, int $maxLines = 120, int $maxBytes = 131072): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $size = filesize($path);
    if (!is_int($size) || $size <= 0) {
        return [];
    }

    $readBytes = min($size, $maxBytes);
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    try {
        if ($size > $readBytes) {
            fseek($handle, -$readBytes, SEEK_END);
        }

        $content = stream_get_contents($handle);
        if (!is_string($content)) {
            return [];
        }
    } finally {
        fclose($handle);
    }

    $lines = preg_split('/\R/', $content) ?: [];
    if ($size > $readBytes && $lines !== []) {
        array_shift($lines);
    }

    return array_slice($lines, -$maxLines);
}

function pathState(string $path): array
{
    $exists = file_exists($path);

    return [
        'exists' => $exists,
        'type' => $exists ? (is_dir($path) ? 'directory' : (is_file($path) ? 'file' : 'other')) : null,
        'readable' => $exists ? is_readable($path) : false,
        'writable' => $exists ? is_writable($path) : false,
        'size' => is_file($path) ? (@filesize($path) ?: 0) : null,
        'modified_at' => $exists && @filemtime($path)
            ? date(DATE_ATOM, (int) filemtime($path))
            : null,
    ];
}

function fileFingerprint(string $path): array
{
    $state = pathState($path);
    $state['sha256'] = is_file($path) && is_readable($path)
        ? (@hash_file('sha256', $path) ?: null)
        : null;

    return $state;
}

$env = readEnvFile($envPath);
$appKey = (string) ($env['APP_KEY'] ?? '');
$expectedToken = (string) ($env['DEPLOY_TOKEN'] ?? '');

if ($expectedToken === '' && $appKey !== '') {
    $expectedToken = hash_hmac('sha256', 'educore-self-deploy', $appKey);
}

$providedToken = (string) ($_GET['token'] ?? '');
if ($expectedToken === '' || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    respond(['ok' => false, 'message' => 'Invalid diagnostic token.'], 403);
}

$checks = [];
$errors = [];

$checks['runtime'] = [
    'php_version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'openssl' => extension_loaded('openssl'),
        'zip' => extension_loaded('zip'),
    ],
];

$checks['filesystem'] = [
    'app_root' => pathState($appRoot),
    'env' => pathState($envPath),
    'storage' => pathState($appRoot . '/storage'),
    'storage_logs' => pathState($appRoot . '/storage/logs'),
    'storage_framework' => pathState($appRoot . '/storage/framework'),
    'storage_framework_views' => pathState($appRoot . '/storage/framework/views'),
    'bootstrap_cache' => pathState($appRoot . '/bootstrap/cache'),
    'free_bytes' => @disk_free_space($appRoot) ?: null,
];

$cacheFiles = [];
foreach (glob($appRoot . '/bootstrap/cache/*.php') ?: [] as $cacheFile) {
    $cacheFiles[basename($cacheFile)] = pathState($cacheFile);
}
$checks['bootstrap_cache_files'] = $cacheFiles;

$criticalFiles = [
    'bootstrap/app.php',
    'routes/web.php',
    'app/Http/Controllers/PublicMarketingController.php',
    'app/Http/Controllers/SelfDeployController.php',
    'resources/views/welcome.blade.php',
    'database/migrations/2026_09_21_130500_add_recipient_scope_to_platform_broadcasts.php',
    'database/migrations/2026_09_21_140500_add_conventional_staff_working_days.php',
    'database/migrations/2026_09_21_151000_add_day_start_times_to_timetable_configs.php',
];

$checks['critical_files'] = [];
foreach ($criticalFiles as $relativePath) {
    $checks['critical_files'][$relativePath] = fileFingerprint($appRoot . '/' . $relativePath);
}

$database = [
    'configured_driver' => $env['DB_CONNECTION'] ?? null,
    'connected' => false,
    'migration_count' => null,
    'latest_migrations' => [],
    'schema' => [],
];

try {
    $driver = (string) ($env['DB_CONNECTION'] ?? 'mysql');

    if ($driver !== 'mysql') {
        throw new RuntimeException('Diagnostic database probe currently supports MySQL/MariaDB only.');
    }

    $host = (string) ($env['DB_HOST'] ?? '127.0.0.1');
    $port = (string) ($env['DB_PORT'] ?? '3306');
    $databaseName = (string) ($env['DB_DATABASE'] ?? '');
    $username = (string) ($env['DB_USERNAME'] ?? '');
    $password = (string) ($env['DB_PASSWORD'] ?? '');

    if ($databaseName === '' || $username === '') {
        throw new RuntimeException('Database name or username is not configured.');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $databaseName);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->query('SELECT 1')->fetchColumn();
    $database['connected'] = true;

    $migrationTableExists = (bool) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'migrations'"
    )->fetchColumn();

    if ($migrationTableExists) {
        $database['migration_count'] = (int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
        $database['latest_migrations'] = $pdo->query(
            'SELECT migration, batch FROM migrations ORDER BY id DESC LIMIT 15'
        )->fetchAll();
    }

    $schemaChecks = [
        'platform_broadcasts.recipient_scope' => [
            'table' => 'platform_broadcasts',
            'column' => 'recipient_scope',
        ],
        'timetable_configs.day_start_times' => [
            'table' => 'timetable_configs',
            'column' => 'day_start_times',
        ],
        'staff_attendance_working_days' => [
            'table' => 'staff_attendance_working_days',
            'column' => null,
        ],
    ];

    foreach ($schemaChecks as $label => $definition) {
        if ($definition['column'] === null) {
            $statement = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $statement->execute([$definition['table']]);
        } else {
            $statement = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
            );
            $statement->execute([$definition['table'], $definition['column']]);
        }

        $database['schema'][$label] = (bool) $statement->fetchColumn();
    }
} catch (Throwable $e) {
    $errors[] = [
        'area' => 'database',
        'type' => get_class($e),
        'message' => $e->getMessage(),
    ];
}

$checks['database'] = $database;

$logPath = $appRoot . '/storage/logs/laravel.log';
$logLines = tailFile($logPath, 160);
$checks['laravel_log'] = [
    'file' => pathState($logPath),
    'tail' => $logLines,
];

respond([
    'ok' => $errors === [],
    'generated_at' => date(DATE_ATOM),
    'host' => $_SERVER['HTTP_HOST'] ?? null,
    'checks' => $checks,
    'errors' => $errors,
    'note' => 'Read-only diagnostic. No caches, migrations, files, or database rows were changed.',
]);
