<?php
/**
 * Prints the derived self-deploy token ONCE so it can be saved somewhere
 * safe. Copy this file to public_html/, open it in a browser, note the
 * token, then DELETE the file.
 *
 * The token is HMAC(APP_KEY), so it never needs to be stored in .env and
 * is unique to this installation.
 */
$root = __DIR__;
while ($root !== dirname($root) && !file_exists($root . '/educore/vendor/autoload.php')) {
    $root = dirname($root);
}
require $root . '/educore/vendor/autoload.php';
$app = require $root . '/educore/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Activate freshly extracted code: clear compiled views/routes/config
foreach ([Illuminate\Support\Facades\Artisan::class] as $a) {
    $a::call('view:clear');
    $a::call('route:clear');
    $a::call('config:clear');
    $a::call('cache:clear');
}
if (function_exists('opcache_reset')) opcache_reset();

$token = App\Http\Controllers\SelfDeployController::derivedToken();

header('Content-Type: text/plain; charset=utf-8');
echo "Self-deploy token (save it, then DELETE this file):\n\n";
echo $token, "\n\n";
echo "Caches cleared.

Deploy URL:\nhttps://educoreng.online/deploy/pull?token={$token}\n";
