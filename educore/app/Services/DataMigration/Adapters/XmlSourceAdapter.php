<?php

namespace App\Services\DataMigration\Adapters;

use App\Contracts\DataMigration\SourceAdapter;
use App\DataMigration\SourceDatasetDefinition;
use App\DataMigration\SourceInspection;
use App\Models\MigrationFile;
use App\Services\DataMigration\SourceFileAccess;
use Generator;
use RuntimeException;
use XMLReader;

class XmlSourceAdapter implements SourceAdapter
{
    public function __construct(private readonly SourceFileAccess $files) {}

    public function supports(MigrationFile $file, string $detectedFormat): bool
    {
        return $detectedFormat === 'xml';
    }

    public function inspect(MigrationFile $file): SourceInspection
    {
        $local = $this->files->localPath($file);
        try {
            $reader = $this->open($local->path);
            $rowName = null;
            $headers = [];
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->depth === 1) {
                    $rowName = $reader->localName;
                    $headers = array_keys($this->elementToArray($reader->readOuterXml()));
                    break;
                }
            }
            $reader->close();
            if ($rowName === null) {
                throw new RuntimeException('XML source contains no row elements.');
            }

            return new SourceInspection('xml', [new SourceDatasetDefinition(pathinfo($file->original_filename, PATHINFO_FILENAME), $headers, null, $rowName)]);
        } finally {
            $local->close();
        }
    }

    public function streamRows(MigrationFile $file, SourceDatasetDefinition $dataset, int $startRow = 1): Generator
    {
        $local = $this->files->localPath($file);
        try {
            $reader = $this->open($local->path);
            $rowNumber = 0;
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->depth !== 1 || $reader->localName !== $dataset->locator) {
                    continue;
                }
                $rowNumber++;
                if ($rowNumber < $startRow) {
                    continue;
                }
                yield $rowNumber => $this->elementToArray($reader->readOuterXml());
            }
            $reader->close();
        } finally {
            $local->close();
        }
    }

    private function open(string $path): XMLReader
    {
        $reader = new XMLReader;
        if (! $reader->open($path, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new RuntimeException('XML source cannot be opened.');
        }

        return $reader;
    }

    private function elementToArray(string $xml): array
    {
        $element = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if ($element === false) {
            throw new RuntimeException('XML row is malformed.');
        }
        $row = [];
        foreach ($element->attributes() as $name => $value) {
            $row['@'.$name] = (string) $value;
        }
        foreach ($element->children() as $name => $value) {
            $text = trim((string) $value);
            $row[$name] = $text !== '' ? $text : json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return $row;
    }
}
