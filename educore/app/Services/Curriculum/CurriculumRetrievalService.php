<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumFragment;
use App\Models\LessonPlan;
use Illuminate\Support\Collection;

class CurriculumRetrievalService
{
    public function forLessonPlan(LessonPlan $plan, int $limit = 18): Collection
    {
        $tenantId = (int) $plan->tenant_id;
        $phrases=collect(array_filter(array_merge([$plan->topic],preg_split('/[,;\n]+/',(string)$plan->subtopic))))->map(fn($v)=>trim((string)$v))->filter();
        $terms=$phrases->flatMap(fn($phrase)=>array_merge([$phrase],preg_split('/\W+/u',$phrase,-1,PREG_SPLIT_NO_EMPTY)?:[]))
            ->map(fn($term)=>trim((string)$term))->filter(fn($term)=>mb_strlen($term)>=4)
            ->reject(fn($term)=>in_array(mb_strtolower($term),['introduction','meaning','lesson','topic','biology','science'],true))->unique()->take(16);

        return CurriculumFragment::query()->with('source:id,tenant_id,authority,title,version,is_official,is_active,review_status')
            ->whereHas('source', fn ($q) => $q->where('is_active', true)->where('review_status', 'approved')
                ->where(fn ($scope) => $scope->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
                ->where(fn ($dates) => $dates->whereNull('effective_from')->orWhere('effective_from', '<=', now()->toDateString()))
                ->where(fn ($dates) => $dates->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString())))
            ->where(fn ($q) => $q->whereNull('subject_id')->orWhere('subject_id', $plan->subject_id))
            ->where(fn ($q) => $q->whereNull('class_level_id')->orWhere('class_level_id', $plan->class_level_id))
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) $query->orWhere('topic', 'like', "%{$term}%")->orWhere('subtopic', 'like', "%{$term}%")->orWhere('content', 'like', "%{$term}%");
            })->orderByRaw("CASE WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.authority = 'NERDC') THEN 0 WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.authority IN ('WAEC','NECO','JAMB')) THEN 1 WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.authority = 'TEXTBOOK' AND cs.is_official = 1) THEN 2 WHEN EXISTS (SELECT 1 FROM curriculum_sources cs WHERE cs.id = curriculum_fragments.curriculum_source_id AND cs.authority = 'SCHOOL') THEN 3 ELSE 4 END")
            ->orderBy('sequence')->limit($limit)->get();
    }

    public function compactContext(Collection $fragments): array
    {
        return $fragments->map(fn ($fragment) => [
            'fragment_id' => $fragment->id, 'authority' => $fragment->source->authority,
            'source' => $fragment->source->title, 'version' => $fragment->source->version,
            'source_type' => $fragment->source->source_type,
            'approval_status' => $fragment->source->is_official ? 'verified' : 'school-reviewed',
            'topic' => $fragment->topic, 'subtopic' => $fragment->subtopic,
            'requirement' => $fragment->learning_expectation ?: $fragment->content,
            'locator' => $fragment->source_locator,
        ])->values()->all();
    }
}
