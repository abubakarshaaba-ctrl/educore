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

class JsonSourceAdapter implements SourceAdapter
{
    public function __construct(private readonly FileSignatureInspector $signatures, private readonly SourceFileAccess $files) {}

    public function supports(MigrationFile $file, string $detectedFormat): bool
    {
        return in_array($detectedFormat, ['json', 'jsonl'], true);
    }

    public function inspect(MigrationFile $file): SourceInspection
    {
        $format = strtolower(pathinfo($file->sanitized_filename, PATHINFO_EXTENSION)) === 'jsonl' ? 'jsonl' : $this->signatures->detect($file);
        if ($format === 'jsonl') {
            $first = null;
            foreach ($this->streamJsonLines($file, 1) as $row) {
                $first = $row;
                break;
            }

            return new SourceInspection('jsonl', [new SourceDatasetDefinition(pathinfo($file->original_filename, PATHINFO_FILENAME), array_keys($first ?? []), null, 'default')]);
        }

        $decoded = $this->decodeBounded($file);
        $datasets = [];
        if (array_is_list($decoded)) {
            $datasets[] = new SourceDatasetDefinition(pathinfo($file->original_filename, PATHINFO_FILENAME), array_keys((array) ($decoded[0] ?? [])), count($decoded), 'default');
        } elseif ($this->isDatasetObject($decoded)) {
            foreach ($decoded as $name => $rows) {
                $datasets[] = new SourceDatasetDefinition((string) $name, array_keys((array) ($rows[0] ?? [])), count($rows), (string) $name);
            }
        } else {
            $datasets[] = new SourceDatasetDefinition(pathinfo($file->original_filename, PATHINFO_FILENAME), array_keys($decoded), 1, 'default');
        }

        return new SourceInspection('json', $datasets);
    }

    public function streamRows(MigrationFile $file, SourceDatasetDefinition $dataset, int $startRow = 1): Generator
    {
        if (strtolower(pathinfo($file->sanitized_filename, PATHINFO_EXTENSION)) === 'jsonl') {
            yield from $this->streamJsonLines($file, $startRow);

            return;
        }

        $decoded = $this->decodeBounded($file);
        $rows = array_is_list($decoded) ? $decoded : ($this->isDatasetObject($decoded) ? ($decoded[$dataset->locator] ?? []) : [$decoded]);
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            if ($rowNumber >= $startRow) {
                yield $rowNumber => (array) $row;
            }
        }
    }

    private function streamJsonLines(MigrationFile $file, int $startRow): Generator
    {
        $local = $this->files->localPath($file);
        try {
            $handle = fopen($local->path, 'rb');
            if ($handle === false) {
                throw new RuntimeException('JSONL source cannot be opened.');
            }
            $rowNumber = 0;
            while (($line = fgets($handle)) !== false) {
                if (trim($line) === '') {
                    continue;
                }
                $rowNumber++;
                if ($rowNumber < $startRow) {
                    continue;
                }
                $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($row)) {
                    throw new RuntimeException("JSONL row {$rowNumber} is not an object.");
                }
                yield $rowNumber => $row;
            }
            fclose($handle);
        } finally {
            $local->close();
        }
    }

    private function decodeBounded(MigrationFile $file): array
    {
        if ($file->file_size > config('data_migration.json_memory_limit_bytes')) {
            throw new RuntimeException('Large JSON arrays must be supplied as JSONL for streaming ingestion.');
        }
        $local = $this->files->localPath($file);
        try {
            $decoded = json_decode((string) file_get_contents($local->path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new RuntimeException('JSON root must be an object or array.');
            }

            return $decoded;
        } finally {
            $local->close();
        }
    }

    private function isDatasetObject(array $value): bool
    {
        return ! array_is_list($value) && $value !== [] && count(array_filter($value, fn ($item) => is_array($item) && array_is_list($item))) === count($value);
    }
}
