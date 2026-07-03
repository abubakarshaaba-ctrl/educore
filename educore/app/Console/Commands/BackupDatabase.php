<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--path= : Output directory relative to storage/app}
        {--prune-days=14 : Remove backups older than this many days after a successful backup}';

    protected $description = 'Create a MySQL database backup in storage/app/backups.';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}");

        if (($database['driver'] ?? null) !== 'mysql') {
            $this->error('backup:database only supports the mysql connection configured for this app.');

            return self::FAILURE;
        }

        $outputDirectory = storage_path('app/' . ltrim((string) ($this->option('path') ?: 'backups'), '/'));
        $this->files->ensureDirectoryExists($outputDirectory);

        $fileName = 'educore-db-' . now()->format('Ymd-His') . '.sql';
        $outputPath = $outputDirectory . DIRECTORY_SEPARATOR . $fileName;

        $process = new Process($this->buildCommand($database, $outputPath), null, [
            'MYSQL_PWD' => (string) ($database['password'] ?? ''),
        ]);
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput()) ?: $process->getOutput() ?: 'Database backup failed.');

            return self::FAILURE;
        }

        $this->info('Database backup created: ' . $outputPath);

        $pruneDays = max(0, (int) $this->option('prune-days'));
        if ($pruneDays > 0) {
            $this->pruneOldBackups($outputDirectory, $pruneDays);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function buildCommand(array $database, string $outputPath): array
    {
        $binary = $this->resolveMySqlDumpBinary();

        return array_filter([
            $binary,
            '--host=' . ($database['host'] ?? '127.0.0.1'),
            '--port=' . ($database['port'] ?? 3306),
            '--user=' . ($database['username'] ?? 'root'),
            '--default-character-set=utf8mb4',
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--databases',
            $database['database'] ?? config('database.connections.mysql.database'),
            '--result-file=' . $outputPath,
        ]);
    }

    private function resolveMySqlDumpBinary(): string
    {
        $configured = env('MYSQLDUMP_PATH');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $windowsDefault = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (PHP_OS_FAMILY === 'Windows' && is_file($windowsDefault)) {
            return $windowsDefault;
        }

        return 'mysqldump';
    }

    private function pruneOldBackups(string $directory, int $days): void
    {
        $cutoff = now()->subDays($days)->timestamp;

        collect($this->files->files($directory))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.sql'))
            ->each(function ($file) use ($cutoff): void {
                if ($file->getMTime() <= $cutoff) {
                    $this->files->delete($file->getPathname());
                }
            });
    }
}
