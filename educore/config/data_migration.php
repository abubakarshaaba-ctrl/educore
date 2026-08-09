<?php

return [
    'schema_version' => '1.0',
    'parser_version' => '1.0',
    'source_disk' => env('DATA_MIGRATION_DISK', 'local'),
    'source_prefix' => 'migration-sources',
    'maximum_source_bytes' => (int) env('DATA_MIGRATION_MAX_BYTES', 536_870_912),
    'source_retention_days' => (int) env('DATA_MIGRATION_SOURCE_RETENTION_DAYS', 90),
    'staging_retention_days' => (int) env('DATA_MIGRATION_STAGING_RETENTION_DAYS', 30),
    'staging_chunk_rows' => (int) env('DATA_MIGRATION_STAGING_CHUNK_ROWS', 500),
    'spreadsheet_chunk_rows' => (int) env('DATA_MIGRATION_SPREADSHEET_CHUNK_ROWS', 500),
    'json_memory_limit_bytes' => (int) env('DATA_MIGRATION_JSON_MEMORY_LIMIT_BYTES', 33_554_432),
    'allowed_directions' => ['inbound', 'outbound', 'tenant_to_tenant'],
    'allowed_types' => ['standard_import', 'full_migration', 'full_export', 'selective_export'],
    'mapping_sample_rows' => 100,
    'mapping_auto_confidence' => 95,
    'mapping_review_confidence' => 75,
    'normalisation_day_first' => true,
    'normalisation_default_country_calling_code' => '234',
    'archive_max_entries' => 500,
    'archive_max_uncompressed_bytes' => 1073741824,
    'archive_max_compression_ratio' => 100,
];
