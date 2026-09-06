<?php

namespace App\Services;

use App\Models\CurriculumSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcademicRepositoryCatalogueService
{
    public function query(): Builder
    {
        return CurriculumSource::query()
            ->whereNull('tenant_id')
            ->where('is_active', true)
            ->where('extraction_status', 'extracted')
            ->where('index_status', 'indexed')
            ->whereHas('fragments')
            ->withCount('fragments')
            ->latest();
    }

    public function catalogue(): Collection
    {
        return $this->query()->get();
    }

    public function filter(Collection $sources, array $filters): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['query'] ?? $filters['search'] ?? '')));
        $class = trim((string) ($filters['class'] ?? ''));
        $term = trim((string) ($filters['term'] ?? ''));
        $subject = trim((string) ($filters['subject'] ?? ''));

        return $sources
            ->when($search !== '', fn (Collection $items) => $items->filter(function (CurriculumSource $source) use ($search) {
                return str_contains(mb_strtolower(implode(' ', [
                    $source->title,
                    $source->original_filename,
                    $source->cleaned_text,
                    $this->label($source, 'class_label', ''),
                    $this->label($source, 'term_label', ''),
                    $this->label($source, 'subject_label', ''),
                ])), $search);
            }))
            ->when($class !== '', fn (Collection $items) => $items->filter(
                fn (CurriculumSource $source) => strcasecmp($this->label($source, 'class_label', 'Unmapped class'), $class) === 0
            ))
            ->when($term !== '', fn (Collection $items) => $items->filter(
                fn (CurriculumSource $source) => strcasecmp($this->label($source, 'term_label', 'Unmapped term'), $term) === 0
            ))
            ->when($subject !== '', fn (Collection $items) => $items->filter(
                fn (CurriculumSource $source) => strcasecmp($this->label($source, 'subject_label', 'Unmapped subject'), $subject) === 0
            ))
            ->values();
    }

    public function groups(Collection $sources): Collection
    {
        return $sources
            ->groupBy(fn (CurriculumSource $source) => $this->label($source, 'class_label', 'Unmapped class'))
            ->sortKeysUsing('strnatcasecmp')
            ->map(fn ($classSources) => $classSources
                ->groupBy(fn (CurriculumSource $source) => $this->label($source, 'term_label', 'Unmapped term'))
                ->sortKeysUsing(fn ($left, $right) => $this->termOrder($left) <=> $this->termOrder($right))
                ->map(fn ($termSources) => $termSources
                    ->groupBy(fn (CurriculumSource $source) => $this->label($source, 'subject_label', 'Unmapped subject'))
                    ->sortKeysUsing('strnatcasecmp')));
    }

    public function facets(Collection $sources): array
    {
        $classes = $sources->map(fn (CurriculumSource $source) => $this->label($source, 'class_label', 'Unmapped class'))
            ->unique(fn (string $value) => mb_strtolower($value))->sort('strnatcasecmp')->values();
        $terms = $sources->map(fn (CurriculumSource $source) => $this->label($source, 'term_label', 'Unmapped term'))
            ->unique(fn (string $value) => mb_strtolower($value))
            ->sort(fn (string $left, string $right) => $this->termOrder($left) <=> $this->termOrder($right))->values();
        $subjects = $sources->map(fn (CurriculumSource $source) => $this->label($source, 'subject_label', 'Unmapped subject'))
            ->unique(fn (string $value) => mb_strtolower($value))->sort('strnatcasecmp')->values();

        return compact('classes', 'terms', 'subjects');
    }

    public function available(CurriculumSource $source): bool
    {
        return $source->tenant_id === null
            && $source->is_active
            && $source->extraction_status === 'extracted'
            && $source->index_status === 'indexed'
            && $source->fragments()->exists();
    }

    public function label(CurriculumSource $source, string $key, string $fallback): string
    {
        $metadata = $source->metadata;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }
        $value = is_array($metadata) ? trim((string) ($metadata[$key] ?? '')) : '';

        return $value !== '' ? $value : $fallback;
    }

    public function resourcePayload(CurriculumSource $source): array
    {
        return [
            'id' => $source->id,
            'title' => $source->title,
            'filename' => $source->original_filename,
            'mime_type' => $source->mime_type,
            'file_size' => $source->file_size,
            'checksum' => $source->checksum,
            'class' => $this->label($source, 'class_label', 'Unmapped class'),
            'term' => $this->label($source, 'term_label', 'Unmapped term'),
            'subject' => $this->label($source, 'subject_label', 'Unmapped subject'),
            'authority' => $source->authority,
            'source_type' => $source->source_type,
            'version' => $source->version,
            'fragments_count' => (int) ($source->fragments_count ?? $source->fragments()->count()),
            'updated_at' => $source->updated_at?->toIso8601String(),
        ];
    }

    private function termOrder(string $term): int
    {
        $term = mb_strtolower($term);

        return match (true) {
            str_contains($term, 'first') || preg_match('/(^|\D)1(st)?(\D|$)/', $term) === 1 => 10,
            str_contains($term, 'second') || preg_match('/(^|\D)2(nd)?(\D|$)/', $term) === 1 => 20,
            str_contains($term, 'third') || preg_match('/(^|\D)3(rd)?(\D|$)/', $term) === 1 => 30,
            default => 90,
        };
    }
}
