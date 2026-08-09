<?php

namespace App\Services\DataMigration;

use App\Models\MigrationFile;
use ZipArchive;

class FileSignatureInspector
{
    public function __construct(private readonly SourceFileAccess $files) {}

    public function detect(MigrationFile $file): string
    {
        $local = $this->files->localPath($file);
        try {
            $handle = fopen($local->path, 'rb');
            $prefix = $handle === false ? '' : (string) fread($handle, 512);
            if (is_resource($handle)) {
                fclose($handle);
            }

            if (str_starts_with($prefix, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
                return 'xls';
            }
            if (str_starts_with($prefix, 'PAR1')) {
                return 'parquet';
            }
            if (str_starts_with($prefix, 'PK')) {
                return $this->detectZipContainer($local->path);
            }

            $trimmed = ltrim($prefix, "\xEF\xBB\xBF \t\r\n");
            if (str_starts_with($trimmed, '<')) {
                return 'xml';
            }
            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                return 'json';
            }

            $extension = strtolower(pathinfo($file->sanitized_filename, PATHINFO_EXTENSION));
            if ($extension === 'sql' && preg_match('/\b(CREATE\s+TABLE|INSERT\s+INTO|COPY\s+)/i', $trimmed)) {
                return 'sql';
            }
            if ($extension === 'jsonl') {
                return 'jsonl';
            }
            if ($extension === 'tsv' || substr_count(strtok($trimmed, "\r\n") ?: '', "\t") > substr_count(strtok($trimmed, "\r\n") ?: '', ',')) {
                return 'tsv';
            }
            if ($extension === 'csv' || str_contains(strtok($trimmed, "\r\n") ?: '', ',')) {
                return 'csv';
            }

            return 'unknown';
        } finally {
            $local->close();
        }
    }

    private function detectZipContainer(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return 'zip';
        }
        try {
            if ($zip->locateName('[Content_Types].xml') !== false && $zip->locateName('xl/workbook.xml') !== false) {
                return 'xlsx';
            }
            $mime = $zip->getFromName('mimetype');
            if (is_string($mime) && str_contains($mime, 'opendocument.spreadsheet')) {
                return 'ods';
            }

            return 'zip';
        } finally {
            $zip->close();
        }
    }
}
