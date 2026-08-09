<?php

namespace App\Services\DataMigration;

use App\Models\DataMigration;
use App\Models\MigrationDataset;
use App\Models\MigrationRow;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MigrationRowStager
{
    public function stage(DataMigration $migration, MigrationDataset $dataset, int $rowNumber, array $rawPayload, ?string $sourceIdentifier = null): MigrationRow
    {
        if ((int) $dataset->migration_id !== (int) $migration->id) {
            throw new InvalidArgumentException('Dataset does not belong to the migration batch.');
        }
        if ($rowNumber < 1) {
            throw new InvalidArgumentException('Source row number must be positive.');
        }

        $checksum = hash('sha256', json_encode($this->canonicalise($rawPayload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return MigrationRow::firstOrCreate(
            ['dataset_id' => $dataset->id, 'row_number' => $rowNumber],
            [
                'migration_id' => $migration->id,
                'source_identifier' => $sourceIdentifier,
                'raw_payload' => $rawPayload,
                'validation_status' => 'pending',
                'source_record_checksum' => $checksum,
            ]
        );
    }

    public function stageMany(DataMigration $migration, MigrationDataset $dataset, array $rows): int
    {
        if ((int) $dataset->migration_id !== (int) $migration->id) {
            throw new InvalidArgumentException('Dataset does not belong to the migration batch.');
        }

        $timestamp = now();
        $records = [];
        foreach ($rows as $row) {
            $rowNumber = (int) $row['row_number'];
            $payload = (array) $row['raw_payload'];
            if ($rowNumber < 1) {
                throw new InvalidArgumentException('Source row number must be positive.');
            }
            $records[] = [
                'migration_id' => $migration->id,
                'dataset_id' => $dataset->id,
                'row_number' => $rowNumber,
                'source_identifier' => $row['source_identifier'] ?? null,
                'raw_payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'validation_status' => 'pending',
                'source_record_checksum' => $this->checksum($payload),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        return $records === [] ? 0 : DB::table('migration_rows')->insertOrIgnore($records);
    }

    public function checksum(array $payload): string
    {
        return hash('sha256', json_encode($this->canonicalise($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function canonicalise(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalise($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}
