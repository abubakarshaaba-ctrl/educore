<?php
// Temporary debug — delete after use
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

$user = auth()->user();
if (!$user) { die('Not logged in'); }

echo '<pre>';
echo "Role key: "      . $user->roleKey() . "\n";
echo "Role label: "    . $user->roleLabel() . "\n";
echo "Is admin: "      . ($user->isAdmin() ? 'yes' : 'no') . "\n";
echo "Is superadmin: " . ($user->isSuperAdmin() ? 'yes' : 'no') . "\n\n";

$allowed = \App\Models\User::ROLE_ACCESS[$user->roleKey()] ?? [];
echo "Allowed modules for this role:\n";
echo implode(', ', $allowed) . "\n\n";

echo "canAccessModule('asc'): " . ($user->canAccessModule('asc') ? 'YES ✓' : 'NO ✗') . "\n";
echo "in_array('asc', allowed): " . (in_array('asc', $allowed) ? 'yes' : 'no') . "\n";
echo "in_array('*', allowed): "   . (in_array('*', $allowed) ? 'yes' : 'no') . "\n";
echo '</pre>';
