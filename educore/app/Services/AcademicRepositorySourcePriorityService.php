<?php

namespace App\Services;

use App\Models\AcademicTopic;
use Illuminate\Support\Collection;

class AcademicRepositorySourcePriorityService
{
    /**
     * Higher values are preferred when several repository resources cover the
     * same curriculum topic. The ordering is deliberately explicit and stable
     * so generation never depends on an opaque ranking algorithm.
     */
    public const RESOURCE_PRIORITY = [
        'curriculum' => 100,
        'scheme_of_work' => 95,
        'syllabus' => 90,
        'teacher_note' => 75,
        'lesson_note' => 70,
        'textbook_extract' => 65,
        'practical_guide' => 60,
        'past_questions' => 55,
        'lesson_plan' => 50,
        'other' => 40,
    ];

    public function score(AcademicTopic $topic): int
    {
        $base = self::RESOURCE_PRIORITY[$topic->resource_type] ?? self::RESOURCE_PRIORITY['other'];
        $source = $topic->relationLoaded('source') ? $topic->source : $topic->source()->first();

        if ($source?->is_official) {
            $base += 5;
        }

        if ($source?->is_active) {
            $base += 2;
        }

        return $base;
    }

    public function provenance(AcademicTopic $topic): array
    {
        $candidates = $this->matchingTopics($topic)
            ->map(function (AcademicTopic $candidate) use ($topic) {
                $source = $candidate->source;

                return [
                    'academic_topic_id' => $candidate->id,
                    'source_id' => $candidate->curriculum_source_id,
                    'title' => $source?->title ?: $candidate->reference,
                    'filename' => $source?->original_filename,
                    'resource_type' => $candidate->resource_type,
                    'priority' => $this->score($candidate),
                    'is_official' => (bool) ($source?->is_official),
                    'is_primary' => (int) $candidate->id === (int) $topic->id,
                    'reference' => $candidate->reference,
                ];
            })
            ->filter(fn (array $item) => filled($item['title']) || filled($item['filename']) || filled($item['reference']))
            ->sortByDesc('priority')
            ->unique(fn (array $item) => $item['source_id'] ?: ($item['filename'] ?: $item['title']))
            ->values();

        return $candidates->take(8)->all();
    }

    public function primary(AcademicTopic $topic): ?array
    {
        return collect($this->provenance($topic))->first();
    }

    private function matchingTopics(AcademicTopic $topic): Collection
    {
        $query = AcademicTopic::query()
            ->with('source')
            ->where('class_label', $topic->class_label)
            ->where('subject_label', $topic->subject_label)
            ->where('term_label', $topic->term_label)
            ->whereRaw('LOWER(topic) = ?', [mb_strtolower(trim((string) $topic->topic))]);

        if ($topic->week_number) {
            $query->where(function ($scope) use ($topic) {
                $scope->whereNull('week_number')->orWhere('week_number', $topic->week_number);
            });
        }

        $items = $query->get();

        if (! $items->contains('id', $topic->id)) {
            $topic->loadMissing('source');
            $items->push($topic);
        }

        return $items;
    }
}
