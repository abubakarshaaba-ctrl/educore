<?php

namespace App\Services;

use App\Models\AcademicTopic;
use Illuminate\Support\Collection;

class AcademicRepositoryTopicConsolidationService
{
    public function __construct(
        private AcademicRepositorySourcePriorityService $priority,
        private AcademicRepositoryTopicMatcher $matcher,
    ) {
    }

    public function candidates(AcademicTopic $topic): Collection
    {
        $query = AcademicTopic::query()
            ->with(['source', 'blocks'])
            ->where('class_label', $topic->class_label)
            ->where('subject_label', $topic->subject_label)
            ->where('term_label', $topic->term_label);

        if ($topic->week_number) {
            $query->where(function ($scope) use ($topic) {
                $scope->whereNull('week_number')->orWhere('week_number', $topic->week_number);
            });
        }

        $items = $query->limit(250)->get()
            ->filter(function (AcademicTopic $candidate) use ($topic) {
                return (int) $candidate->id === (int) $topic->id
                    || $this->matcher->isNearDuplicate($topic->topic, $candidate->topic);
            });

        if (! $items->contains('id', $topic->id)) {
            $topic->loadMissing(['source', 'blocks']);
            $items->push($topic);
        }

        return $items
            ->sortByDesc(fn (AcademicTopic $candidate) => $this->priority->score($candidate))
            ->values();
    }

    public function scalar(AcademicTopic $topic, string $field)
    {
        foreach ($this->candidates($topic) as $candidate) {
            if ($candidate->status !== 'approved') {
                continue;
            }
            $value = $candidate->{$field};
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    public function blocks(AcademicTopic $topic, array|string $types): Collection
    {
        $types = is_array($types) ? $types : [$types];
        $seen = [];
        $result = collect();

        foreach ($this->candidates($topic) as $candidate) {
            if ($candidate->status !== 'approved') {
                continue;
            }

            foreach ($candidate->blocks->whereIn('block_type', $types)->where('is_approved', true)->sortBy('sequence') as $block) {
                $key = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $block->content)));
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $result->push($block);
            }
        }

        return $result->values();
    }

    public function summary(AcademicTopic $topic): array
    {
        $candidates = $this->candidates($topic);

        return [
            'source_count' => $candidates->filter(fn ($candidate) => $candidate->curriculum_source_id)->pluck('curriculum_source_id')->unique()->count(),
            'approved_source_count' => $candidates->filter(fn ($candidate) => $candidate->status === 'approved' && $candidate->curriculum_source_id)->pluck('curriculum_source_id')->unique()->count(),
            'topic_count' => $candidates->count(),
            'primary_topic_id' => optional($candidates->first())->id,
            'near_duplicates' => $candidates->reject(fn ($candidate) => (int) $candidate->id === (int) $topic->id)->map(fn ($candidate) => [
                'id' => $candidate->id,
                'topic' => $candidate->topic,
                'similarity' => round($this->matcher->similarity($topic->topic, $candidate->topic), 2),
                'resource_type' => $candidate->resource_type,
                'priority' => $this->priority->score($candidate),
                'status' => $candidate->status,
            ])->values()->all(),
        ];
    }
}
