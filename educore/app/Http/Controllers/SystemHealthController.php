<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemHealthController extends Controller
{
    public function index()
    {
        $checks = [];

        $checks[] = $this->probe('Database', function (): array {
            DB::select('select 1');

            return ['detail' => 'Database connection is responding.'];
        });

        $checks[] = $this->probe('Cache', function (): array {
            $key = 'educore:system-health:' . bin2hex(random_bytes(6));
            Cache::put($key, 'ok', 30);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            if (!$ok) {
                throw new \RuntimeException('Cache write/read verification failed.');
            }

            return ['detail' => 'Cache write/read verification passed.'];
        });

        $checks[] = $this->probe('Storage', function (): array {
            $paths = [storage_path(), storage_path('framework'), storage_path('logs')];
            $unwritable = collect($paths)->filter(fn (string $path) => !is_dir($path) || !is_writable($path));

            if ($unwritable->isNotEmpty()) {
                throw new \RuntimeException('Unwritable path(s): ' . $unwritable->implode(', '));
            }

            return ['detail' => 'Laravel storage paths are writable.'];
        });

        $checks[] = $this->queueCheck();
        $checks[] = $this->mailCheck();
        $checks[] = $this->environmentCheck();
        $checks[] = $this->diskCheck();

        $summary = [
            'healthy' => collect($checks)->where('status', 'healthy')->count(),
            'warning' => collect($checks)->where('status', 'warning')->count(),
            'critical' => collect($checks)->where('status', 'critical')->count(),
            'checked_at' => now(),
        ];

        $runtime = [
            'environment' => app()->environment(),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'queue' => (string) config('queue.default'),
            'cache' => (string) config('cache.default'),
            'session' => (string) config('session.driver'),
            'maintenance' => app()->isDownForMaintenance(),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
        ];

        return view('super.system-health', compact('checks', 'summary', 'runtime'));
    }

    private function probe(string $name, callable $callback): array
    {
        try {
            $result = $callback();

            return [
                'name' => $name,
                'status' => $result['status'] ?? 'healthy',
                'detail' => $result['detail'] ?? 'Operational.',
                'meta' => $result['meta'] ?? null,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'name' => $name,
                'status' => 'critical',
                'detail' => 'Health verification failed. Review application logs for the underlying exception.',
                'meta' => null,
            ];
        }
    }

    private function queueCheck(): array
    {
        return $this->probe('Queue', function (): array {
            $driver = (string) config('queue.default');
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;

            if ($driver === 'sync') {
                return [
                    'status' => app()->environment('production') ? 'warning' : 'healthy',
                    'detail' => app()->environment('production')
                        ? 'Queue uses the sync driver in production; long-running jobs execute inside web requests.'
                        : 'Queue uses the sync driver.',
                    'meta' => ['driver' => $driver, 'failed_jobs' => $failed],
                ];
            }

            if ($failed !== null && $failed > 0) {
                return [
                    'status' => 'warning',
                    'detail' => "{$failed} failed queue job(s) require review.",
                    'meta' => ['driver' => $driver, 'failed_jobs' => $failed],
                ];
            }

            return [
                'detail' => 'Queue configuration has no currently detected failure condition.',
                'meta' => ['driver' => $driver, 'failed_jobs' => $failed],
            ];
        });
    }

    private function mailCheck(): array
    {
        return $this->probe('Mail', function (): array {
            $mailer = (string) config('mail.default');
            $config = (array) config("mail.mailers.{$mailer}", []);
            $transport = (string) ($config['transport'] ?? $mailer);
            $from = (string) config('mail.from.address');
            $problems = [];

            if ($mailer === '') {
                $problems[] = 'default mailer is not configured';
            }
            if (in_array($transport, ['log', 'array'], true) && app()->environment('production')) {
                $problems[] = "{$transport} transport does not deliver real email";
            }
            if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $problems[] = 'MAIL_FROM_ADDRESS is missing or invalid';
            }
            if ($transport === 'smtp') {
                if (empty($config['host'])) {
                    $problems[] = 'SMTP host is missing';
                }
                if (empty($config['port'])) {
                    $problems[] = 'SMTP port is missing';
                }
            }

            return [
                'status' => $problems === [] ? 'healthy' : 'warning',
                'detail' => $problems === []
                    ? 'Mail transport configuration is structurally valid. Delivery is not tested by this page.'
                    : 'Mail configuration: ' . implode('; ', $problems) . '.',
                'meta' => ['mailer' => $mailer, 'transport' => $transport],
            ];
        });
    }

    private function environmentCheck(): array
    {
        return $this->probe('Environment', function (): array {
            $isProduction = app()->environment('production');
            $debug = (bool) config('app.debug');

            if ($isProduction && $debug) {
                return [
                    'status' => 'critical',
                    'detail' => 'APP_DEBUG is enabled in production and may expose sensitive diagnostic information.',
                ];
            }

            return [
                'detail' => $isProduction
                    ? 'Production environment has debug output disabled.'
                    : 'Environment configuration is visible to Super Admin only.',
            ];
        });
    }

    private function diskCheck(): array
    {
        return $this->probe('Disk Space', function (): array {
            $total = @disk_total_space(base_path());
            $free = @disk_free_space(base_path());

            if ($total === false || $free === false || $total <= 0) {
                return [
                    'status' => 'warning',
                    'detail' => 'Disk capacity could not be read on this host.',
                ];
            }

            $freePercent = round(($free / $total) * 100, 1);
            $status = $freePercent < 5 ? 'critical' : ($freePercent < 15 ? 'warning' : 'healthy');

            return [
                'status' => $status,
                'detail' => $freePercent . '% disk space is free.',
                'meta' => [
                    'free_bytes' => (int) $free,
                    'total_bytes' => (int) $total,
                ],
            ];
        });
    }
}
