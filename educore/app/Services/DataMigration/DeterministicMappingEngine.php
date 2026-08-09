<?php

namespace App\Services\DataMigration;

use App\DataMigration\ColumnMappingSuggestion;
use App\DataMigration\Schema\CanonicalEntityDefinition;
use App\Enums\MigrationMappingDecision;
use Illuminate\Support\Str;

class DeterministicMappingEngine
{
    public function suggest(string $sourceColumn, array $sampleValues, CanonicalEntityDefinition $entity, ?array $savedMapping = null): ColumnMappingSuggestion
    {
        $profile = app(ColumnProfiler::class)->profile($sampleValues);
        if (($savedMapping['decision'] ?? null) === MigrationMappingDecision::IgnoreExplicitly->value) {
            return new ColumnMappingSuggestion($sourceColumn, null, MigrationMappingDecision::IgnoreExplicitly, 100, $profile, [], 'saved_profile_explicit_ignore');
        }
        if ($savedMapping && isset($savedMapping['field']) && $entity->field($savedMapping['field'])) {
            return new ColumnMappingSuggestion($sourceColumn, $savedMapping['field'], MigrationMappingDecision::AutoMap, 100, $profile, [], 'saved_profile');
        }

        $needle = $this->normalise($sourceColumn);
        $candidates = [];
        foreach ($entity->fields as $field) {
            $labels = array_unique([$field->name, ...$field->aliases]);
            $heading = max(array_map(fn ($label) => $this->headingScore($needle, $this->normalise($label)), $labels));
            $type = $this->typeScore($profile['detected_type'], $field->type);
            $score = min(100, $heading + $type);
            $candidates[] = ['field' => $field->name, 'confidence' => round($score, 2), 'heading_score' => $heading, 'type_score' => $type];
        }
        usort($candidates, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
        $best = $candidates[0] ?? null;
        $ambiguous = isset($candidates[1]) && $best && ($best['confidence'] - $candidates[1]['confidence']) < 4;
        $confidence = $best['confidence'] ?? 0;
        $decision = match (true) {
            ! $best || $confidence < config('data_migration.mapping_review_confidence', 75) => MigrationMappingDecision::Unmapped,
            $confidence >= config('data_migration.mapping_auto_confidence', 95) && ! $ambiguous => MigrationMappingDecision::AutoMap,
            default => MigrationMappingDecision::Review,
        };

        return new ColumnMappingSuggestion($sourceColumn, $decision === MigrationMappingDecision::Unmapped ? null : $best['field'], $decision, $confidence, $profile, array_slice($candidates, 0, 3), 'deterministic_alias_type_score');
    }

    private function normalise(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '')->toString();
    }

    private function headingScore(string $a, string $b): float
    {
        if ($a === $b) {
            return 95;
        }
        similar_text($a, $b, $percent);

        return round($percent * 0.82, 2);
    }

    private function typeScore(string $observed, string $expected): float
    {
        if ($observed === 'unknown') {
            return 0;
        }
        if ($observed === $expected) {
            return 5;
        }
        if ($expected === 'string' || (in_array($expected, ['name', 'phone'], true) && $observed === 'string') || ($expected === 'decimal' && $observed === 'integer')) {
            return 3;
        }

        return -8;
    }
}
