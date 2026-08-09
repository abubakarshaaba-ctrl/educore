<?php

namespace App\Services\DataMigration\Adapters;

use App\Contracts\DataMigration\SourceAdapter;
use App\DataMigration\SourceDatasetDefinition;
use App\DataMigration\SourceInspection;
use App\Models\MigrationFile;
use App\Services\DataMigration\SourceFileAccess;
use Generator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class SpreadsheetSourceAdapter implements SourceAdapter
{
    public function __construct(private readonly SourceFileAccess $files) {}

    public function supports(MigrationFile $file, string $detectedFormat): bool
    {
        return in_array($detectedFormat, ['xlsx', 'xls', 'ods'], true);
    }

    public function inspect(MigrationFile $file): SourceInspection
    {
        $local = $this->files->localPath($file);
        try {
            $reader = IOFactory::createReaderForFile($local->path);
            $datasets = [];
            foreach ($reader->listWorksheetInfo($local->path) as $sheet) {
                $headers = $this->readHeaders($local->path, (string) $sheet['worksheetName']);
                $datasets[] = new SourceDatasetDefinition(
                    (string) $sheet['worksheetName'],
                    $headers,
                    max(0, (int) $sheet['totalRows'] - 1),
                    (string) $sheet['worksheetName'],
                    ['total_columns' => (int) $sheet['totalColumns']]
                );
            }
            if ($datasets === []) {
                throw new RuntimeException('Spreadsheet contains no worksheets.');
            }

            return new SourceInspection(strtolower(pathinfo($file->sanitized_filename, PATHINFO_EXTENSION)), $datasets);
        } finally {
            $local->close();
        }
    }

    public function streamRows(MigrationFile $file, SourceDatasetDefinition $dataset, int $startRow = 1): Generator
    {
        $local = $this->files->localPath($file);
        try {
            $headers = $dataset->headers;
            $chunkSize = max(1, (int) config('data_migration.spreadsheet_chunk_rows'));
            $finalRow = ($dataset->estimatedRows ?? 0) + 1;
            $sourceStart = max(2, $startRow + 1);

            for ($chunkStart = $sourceStart; $chunkStart <= $finalRow; $chunkStart += $chunkSize) {
                $chunkEnd = min($finalRow, $chunkStart + $chunkSize - 1);
                $reader = IOFactory::createReaderForFile($local->path);
                $reader->setReadDataOnly(true);
                $reader->setLoadSheetsOnly($dataset->locator);
                $reader->setReadFilter(new SpreadsheetChunkReadFilter($chunkStart, $chunkEnd));
                $spreadsheet = $reader->load($local->path);
                $sheet = $spreadsheet->getSheetByName((string) $dataset->locator);
                if ($sheet === null) {
                    throw new RuntimeException("Worksheet {$dataset->locator} no longer exists.");
                }

                foreach ($sheet->rangeToArray("A{$chunkStart}:{$sheet->getHighestColumn()}{$chunkEnd}", null, true, true, false) as $offset => $values) {
                    if ($this->isBlank($values)) {
                        continue;
                    }
                    $sourceRow = $chunkStart + $offset;
                    $logicalRow = $sourceRow - 1;
                    $values = array_pad(array_slice($values, 0, count($headers)), count($headers), null);
                    yield $logicalRow => (array_combine($headers, $values) ?: []);
                }
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $reader);
            }
        } finally {
            $local->close();
        }
    }

    private function readHeaders(string $path, string $sheetName): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly($sheetName);
        $reader->setReadFilter(new SpreadsheetChunkReadFilter(2, 1));
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $values = $sheet?->rangeToArray('A1:'.$sheet->getHighestColumn().'1', null, true, true, false)[0] ?? [];
        $spreadsheet->disconnectWorksheets();
        $seen = [];

        return array_values(array_map(function ($value, $index) use (&$seen): string {
            $name = trim((string) $value) ?: 'column_'.($index + 1);
            $base = $name;
            $suffix = 2;
            while (isset($seen[mb_strtolower($name)])) {
                $name = $base.'_'.$suffix++;
            }
            $seen[mb_strtolower($name)] = true;

            return $name;
        }, $values, array_keys($values)));
    }

    private function isBlank(array $values): bool
    {
        return count(array_filter($values, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0;
    }
}
