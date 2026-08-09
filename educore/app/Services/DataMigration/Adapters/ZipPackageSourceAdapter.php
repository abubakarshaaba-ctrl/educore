<?php

namespace App\Services\DataMigration\Adapters;

use App\Contracts\DataMigration\SourceAdapter;
use App\DataMigration\SourceDatasetDefinition;
use App\DataMigration\SourceInspection;
use App\Models\MigrationFile;
use App\Services\DataMigration\SourceFileAccess;
use Generator;
use RuntimeException;
use ZipArchive;

class ZipPackageSourceAdapter implements SourceAdapter
{
    public function __construct(private SourceFileAccess $files) {}

    public function supports(MigrationFile $file, string $detectedFormat): bool
    {
        return $detectedFormat === 'zip';
    }

    public function inspect(MigrationFile $file): SourceInspection
    {
        $local = $this->files->localPath($file);
        try {
            $z = $this->open($local->path);
            $sets = [];
            $total = 0;
            if ($z->numFiles > config('data_migration.archive_max_entries')) {
                throw new RuntimeException('Archive contains too many entries.');
            }for ($i = 0; $i < $z->numFiles; $i++) {
                $s = $z->statIndex($i);
                $name = $s['name'];
                $this->safe($name);
                $total += $s['size'];
                if ($total > config('data_migration.archive_max_uncompressed_bytes')) {
                    throw new RuntimeException('Archive uncompressed size limit exceeded.');
                }if ($s['comp_size'] > 0 && config('data_migration.archive_max_compression_ratio') < $s['size'] / $s['comp_size']) {
                    throw new RuntimeException('Unsafe archive compression ratio.');
                }if (! preg_match('/\.(csv|tsv)$/i', $name)) {
                    continue;
                }$first = strtok($z->getFromIndex($i), "\r\n") ?: '';
                $delimiter = str_ends_with(strtolower($name), '.tsv') ? "\t" : ',';
                $sets[] = new SourceDatasetDefinition(pathinfo($name, PATHINFO_FILENAME), str_getcsv($first, $delimiter), null, (string) $i, ['archive_entry' => $name]);
            }$z->close();

            return new SourceInspection('zip', $sets, ['entries' => $z->numFiles ?? count($sets), 'uncompressed_bytes' => $total]);
        } finally {
            $local->close();
        }
    }

    public function streamRows(MigrationFile $file, SourceDatasetDefinition $dataset, int $startRow = 1): Generator
    {
        $local = $this->files->localPath($file);
        try {
            $z = $this->open($local->path);
            $content = $z->getFromIndex((int) $dataset->locator);
            $z->close();
            $h = fopen('php://temp', 'w+b');
            fwrite($h, $content);
            rewind($h);
            $delimiter = str_ends_with(strtolower($dataset->metadata['archive_entry']), '.tsv') ? "\t" : ',';
            $headers = fgetcsv($h, 0, $delimiter);
            $n = 0;
            while (($values = fgetcsv($h, 0, $delimiter)) !== false) {
                $n++;
                if ($n >= $startRow) {
                    yield $n => array_combine($headers, array_pad($values, count($headers), null));
                }
            }fclose($h);
        } finally {
            $local->close();
        }
    }

    private function open(string $p): ZipArchive
    {
        $z = new ZipArchive;
        if ($z->open($p) !== true) {
            throw new RuntimeException('Archive cannot be opened.');
        }

        return $z;
    }

    private function safe(string $n): void
    {
        $normalised = str_replace('\\', '/', $n);
        if (in_array('..', explode('/', $normalised), true) || str_starts_with($normalised, '/') || preg_match('/^[A-Za-z]:/', $normalised)) {
            throw new RuntimeException('Archive contains an unsafe path.');
        }
    }
}
