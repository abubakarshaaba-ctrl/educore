<?php

namespace App\Services\DataMigration\Adapters;

use App\Contracts\DataMigration\SourceAdapter;
use App\DataMigration\SourceDatasetDefinition;
use App\DataMigration\SourceInspection;
use App\Models\MigrationFile;
use App\Services\DataMigration\FileSignatureInspector;
use App\Services\DataMigration\SourceFileAccess;
use Generator;
use RuntimeException;
use SplFileObject;

class DelimitedTextSourceAdapter implements SourceAdapter
{
    public function __construct(private readonly FileSignatureInspector $signatures, private readonly SourceFileAccess $files) {}

    public function supports(MigrationFile $file, string $detectedFormat): bool
    {
        return in_array($detectedFormat, ['csv', 'tsv'], true);
    }

    public function inspect(MigrationFile $file): SourceInspection
    {
        $format = $this->signatures->detect($file);
        $local = $this->files->localPath($file);
        try {
            $csv = new SplFileObject($local->path, 'rb');
            $delimiter = $format === 'tsv' ? "\t" : $this->detectDelimiter($csv);
            $csv->setCsvControl($delimiter);
            $headers = $this->normaliseHeaders($csv->fgetcsv() ?: []);
            if ($headers === []) {
                throw new RuntimeException('Delimited source has no header row.');
            }

            return new SourceInspection($format, [
                new SourceDatasetDefinition(pathinfo($file->original_filename, PATHINFO_FILENAME), $headers, null, 'default', ['delimiter' => $delimiter]),
            ], ['delimiter' => $delimiter]);
        } finally {
            $local->close();
        }
    }

    public function streamRows(MigrationFile $file, SourceDatasetDefinition $dataset, int $startRow = 1): Generator
    {
        $local = $this->files->localPath($file);
        try {
            $csv = new SplFileObject($local->path, 'rb');
            $delimiter = (string) ($dataset->metadata['delimiter'] ?? ',');
            $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
            $csv->setCsvControl($delimiter);
            $headers = $this->normaliseHeaders($csv->fgetcsv() ?: []);
            $logicalRow = 0;

            while (! $csv->eof()) {
                $values = $csv->fgetcsv();
                if ($values === false || $values === [null] || $this->isBlank($values)) {
                    continue;
                }
                $logicalRow++;
                if ($logicalRow < $startRow) {
                    continue;
                }
                yield $logicalRow => $this->combine($headers, $values);
            }
        } finally {
            $local->close();
        }
    }

    private function detectDelimiter(SplFileObject $file): string
    {
        $line = (string) $file->fgets();
        $file->rewind();
        $counts = [',' => substr_count($line, ','), "\t" => substr_count($line, "\t"), ';' => substr_count($line, ';'), '|' => substr_count($line, '|')];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    private function normaliseHeaders(array $headers): array
    {
        $seen = [];

        return array_values(array_map(function ($header, $index) use (&$seen): string {
            $name = trim((string) $header);
            $name = $name !== '' ? $name : 'column_'.($index + 1);
            $base = $name;
            $suffix = 2;
            while (isset($seen[mb_strtolower($name)])) {
                $name = $base.'_'.$suffix++;
            }
            $seen[mb_strtolower($name)] = true;

            return $name;
        }, $headers, array_keys($headers)));
    }

    private function combine(array $headers, array $values): array
    {
        $values = array_pad(array_slice($values, 0, count($headers)), count($headers), null);

        return array_combine($headers, $values) ?: [];
    }

    private function isBlank(array $values): bool
    {
        return count(array_filter($values, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0;
    }
}
