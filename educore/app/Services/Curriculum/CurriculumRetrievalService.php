<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumFragment;
use App\Models\LessonPlan;
use Illuminate\Support\Collection;

class CurriculumRetrievalService
{
    public function forLessonPlan(LessonPlan $plan, int $limit = 18): Collection
    {
        $phrases = collect(array_filter(array_merge([$plan->topic], preg_split('/[,;\n]+/', (string) $plan->subtopic))))
            ->map(fn ($value) => trim((string) $value))
            ->filter();
        $terms = $phrases
            ->flatMap(fn ($phrase) => array_merge([$phrase], preg_split('/\W+/u', $phrase, -1, PREG_SPLIT_NO_EMPTY) ?: []))
            ->map(fn ($term) => trim((string) $term))
            ->filter(fn ($term) => mb_strlen($term) >= 4)
            ->reject(fn ($term) => in_array(mb_strtolower($term), ['introduction', 'meaning', 'lesson', 'topic', 'biology', 'science'], true))
            ->unique()
            ->take(16);

        $exactTopic = mb_strtolower(trim((string) $plan->topic));
        $exactSubtopic = mb_strtolower(trim((string) $plan->subtopic));
        $originLevel = $plan->curriculum_level_id ?: $plan->class_level_id;

        $query = CurriculumFragment::query()
            ->with('source:id,tenant_id,authority,source_type,title,version,is_official,is_active,review_status,extraction_status,index_status,curriculum_level_id,source_class_level_id,term_id,metadata')
            ->whereHas('source', function ($source) use ($plan) {
                $source
                    // Platform-owned, active, successfully extracted and indexed resources are
                    // the canonical knowledge base. Human download/read status is irrelevant.
                    ->whereNull('tenant_id')
                    ->where('is_active', true)
                    ->where('extraction_status', 'extracted')
                    ->where('index_status', 'indexed')
                    ->where(function ($term) use ($plan) {
                        $term->whereNull('term_id');
                        if ($plan->term_id) {
                            $term->orWhere('term_id', $plan->term_id);
                        }
                    })
                    ->where(fn ($dates) => $dates->whereNull('effective_from')->orWhere('effective_from', '<=', now()->toDateString()))
                    ->where(fn ($dates) => $dates->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString()));
            })
            ->where(fn ($subject) => $subject->whereNull('subject_id')->orWhere('subject_id', $plan->subject_id));

        if ($terms->isNotEmpty()) {
            $query->where(function ($match) use ($terms) {
                foreach ($terms as $term) {
                    $match->orWhere('topic', 'like', "%{$term}%")
                        ->orWhere('subtopic', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%");
                }
            });
        }

        return $query
            // Exact topic/subtopic alignment is more important than file type.
            ->orderByRaw(
                'CASE WHEN LOWER(topic) = ? THEN 0 WHEN LOWER(subtopic) = ? THEN 1 WHEN topic LIKE ? OR subtopic LIKE ? THEN 2 ELSE 3 END',
                [$exactTopic, $exactSubtopic, '%'.$exactTopic.'%', '%'.$exactSubtopic.'%']
            )
            ->orderByRaw('CASE WHEN class_level_id = ? THEN 0 WHEN class_level_id IS NULL THEN 1 ELSE 2 END', [$originLevel])
            // For learner-ready notes, prefer full lesson/teacher-note prose, while
            // retaining curriculum/syllabus material as authoritative alignment evidence.
            ->orderByRaw("CASE WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.source_type IN ('lesson_note','teacher_note')) THEN 0 WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.source_type = 'textbook_extract') THEN 1 WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.source_type IN ('curriculum','syllabus')) THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.authority = 'NERDC') THEN 0 WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.authority IN ('WAEC','NECO','JAMB')) THEN 1 WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.authority = 'TEXTBOOK' AND cs.is_official = 1) THEN 2 ELSE 3 END")
            ->orderBy('sequence')
            ->limit($limit)
            ->get();
    }

    public function compactContext(Collection $fragments): array
    {
        return $fragments->map(fn ($fragment) => [
            'fragment_id' => $fragment->id,
            'source_id' => $fragment->curriculum_source_id,
            'authority' => $fragment->source?->authority,
            'source' => $fragment->source?->title,
            'version' => $fragment->source?->version,
            'source_type' => $fragment->source?->source_type,
            'approval_status' => $fragment->source?->is_official ? 'verified' : 'canonical_repository',
            'topic' => $fragment->topic,
            'subtopic' => $fragment->subtopic,
            // Preserve the full indexed knowledge fragment. Learning expectations are
            // alignment metadata; they must not replace substantive lesson-note text.
            'requirement' => $fragment->content,
            'learning_expectation' => $fragment->learning_expectation,
            'locator' => $fragment->source_locator,
        ])->values()->all();
    }
}
