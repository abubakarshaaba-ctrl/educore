<?php

namespace App\Services\DataMigration\Adapters;

use App\Contracts\DataMigration\SourceAdapter;
use App\DataMigration\SourceDatasetDefinition;
use App\DataMigration\SourceInspection;
use App\Models\MigrationFile;
use App\Services\DataMigration\SourceFileAccess;
use Generator;
use RuntimeException;

class SqlDumpSourceAdapter implements SourceAdapter
{
    public function __construct(private SourceFileAccess $files) {}

    public function supports(MigrationFile $file, string $detectedFormat): bool
    {
        return $detectedFormat === 'sql';
    }

    public function inspect(MigrationFile $file): SourceInspection
    {
        $tables = [];
        foreach ($this->statements($file) as $s) {
            if (preg_match('/INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?\s*\(([^)]+)\)/i', $s, $m)) {
                $tables[$m[1]] = array_map(fn ($v) => trim($v, ' `"'), explode(',', $m[2]));
            }
        }

return new SourceInspection('sql', array_map(fn ($n, $h) => new SourceDatasetDefinition($n, $h, null, $n), array_keys($tables), $tables), ['executed' => false]);
    }

    public function streamRows(MigrationFile $file, SourceDatasetDefinition $dataset, int $startRow = 1): Generator
    {
        $n = 0;
        foreach ($this->statements($file) as $s) {
            if (! preg_match('/INSERT\s+INTO\s+[`"]?'.preg_quote($dataset->locator, '/').'[`"]?\s*\(([^)]+)\)\s*VALUES\s*(.+)$/is', $s, $m)) {
                continue;
            }$headers = array_map(fn ($v) => trim($v, ' `"'), explode(',', $m[1]));
            preg_match_all('/\(([^()]*)\)/', $m[2], $tuples);
            foreach ($tuples[1] as $tuple) {
                $n++;
                if ($n < $startRow) {
                    continue;
                }$values = str_getcsv($tuple, ',', "'");
                if (count($values) !== count($headers)) {
                    throw new RuntimeException('Unsupported complex SQL value expression.');
                }yield $n => array_combine($headers, array_map(fn ($v) => strcasecmp(trim($v), 'NULL') === 0 ? null : trim($v), $values));
            }
        }
    }

    private function statements(MigrationFile $file): Generator
    {
        $local = $this->files->localPath($file);
        try {
            $h = fopen($local->path, 'rb');
            $buf = '';
            while (($line = fgets($h)) !== false) {
                $trim = ltrim($line);
                if (str_starts_with($trim, '--') || str_starts_with($trim, '/*')) {
                    continue;
                }$buf .= $line;
                if (str_ends_with(rtrim($line), ';')) {
                    yield rtrim(trim($buf), ';');
                    $buf = '';
                }
            }if (trim($buf) !== '') {
                yield trim($buf);
            }
        } finally {
            if (isset($h) && is_resource($h)) {
                fclose($h);
            }$local->close();
        }
    }
}
