<?php

namespace App\Services;

use App\Models\AcademicTopic;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AcademicRepositoryQualityService
{
    private const MEASURABLE_VERBS = [
        'identify','define','state','list','name','mention','describe','explain','outline','classify',
        'distinguish','differentiate','compare','contrast','calculate','solve','demonstrate','illustrate',
        'draw','label','construct','apply','analyse','analyze','evaluate','discuss','summarise','summarize',
    ];

    private const VAGUE_VERBS = ['know','understand','learn','appreciate','familiarise','familiarize'];

    public function __construct(private AcademicRepositoryTopicConsolidationService $consolidation)
    {
    }

    public function inspect(AcademicTopic $topic): array
    {
        $blocks = $this->consolidation->blocks($topic, [
            'objective','presentation','definition','explanation','example','practical','note','evaluation','assignment',
        ]);

        $objectives = $this->contents($blocks, 'objective');
        $presentation = $this->contents($blocks, 'presentation');
        $evaluation = $this->contents($blocks, 'evaluation');
        $assignment = $this->contents($blocks, 'assignment');
        $noteContent = $blocks->whereIn('block_type', ['definition','explanation','example','practical','note','presentation'])
            ->pluck('content')->map(fn ($v) => trim((string) $v))->filter()->values();

        $objectiveQuality = count($objectives) >= 2 && collect($objectives)->every(fn ($item) => $this->hasMeasurableVerb($item));
        $presentationQuality = count($presentation) >= 2 && collect($presentation)->every(fn ($item) => mb_strlen($item) >= 50);
        $evaluationAlignment = $this->evaluationAlignment($objectives, $evaluation);
        $duplicates = $this->duplicateCount($blocks->pluck('content')->all());
        $noteChars = $noteContent->sum(fn ($v) => mb_strlen($v));

        $introduction = (string) $this->consolidation->scalar($topic, 'introduction');
        $entryBehaviour = (string) $this->consolidation->scalar($topic, 'entry_behaviour');
        $previousKnowledge = (string) $this->consolidation->scalar($topic, 'previous_knowledge');
        $reference = (string) $this->consolidation->scalar($topic, 'reference');

        $checks = [
            'Measurable objectives' => $objectiveQuality,
            'At least two presentation steps' => count($presentation) >= 2,
            'Presentation steps are substantive' => $presentationQuality,
            'Evaluation has at least two questions' => count($evaluation) >= 2,
            'Evaluation aligns with objectives' => $evaluationAlignment['aligned'],
            'Assignment is substantive' => count($assignment) >= 1 && collect($assignment)->contains(fn ($item) => mb_strlen($item) >= 12),
            'Introduction is substantive' => mb_strlen(trim($introduction)) >= 30,
            'Entry behaviour is substantive' => mb_strlen(trim($entryBehaviour)) >= 20,
            'Previous knowledge is substantive' => mb_strlen(trim($previousKnowledge)) >= 20,
            'Student-note content is substantive' => $noteChars >= 300,
            'Reference is identifiable' => mb_strlen(trim($reference)) >= 5,
            'No duplicate content blocks' => $duplicates === 0,
        ];

        $weights = [
            'Measurable objectives' => 15,
            'At least two presentation steps' => 10,
            'Presentation steps are substantive' => 10,
            'Evaluation has at least two questions' => 10,
            'Evaluation aligns with objectives' => 15,
            'Assignment is substantive' => 5,
            'Introduction is substantive' => 5,
            'Entry behaviour is substantive' => 5,
            'Previous knowledge is substantive' => 5,
            'Student-note content is substantive' => 10,
            'Reference is identifiable' => 5,
            'No duplicate content blocks' => 5,
        ];

        $score = collect($checks)->reduce(fn ($total, $ok, $label) => $total + ($ok ? $weights[$label] : 0), 0);
        $issues = collect($checks)->filter(fn ($ok) => ! $ok)->keys()->values()->all();

        $critical = [];
        if (! $objectiveQuality) $critical[] = 'Objectives need measurable, observable verbs.';
        if (! $presentationQuality) $critical[] = 'Presentation requires at least two substantive teaching steps.';
        if (! $evaluationAlignment['aligned']) $critical[] = 'Evaluation questions do not adequately map to the lesson objectives.';
        if ($noteChars < 150) $critical[] = 'Student-note content is too thin for reliable learner use.';

        return [
            'score' => (int) $score,
            'checks' => $checks,
            'issues' => $issues,
            'critical' => $critical,
            'objective_count' => count($objectives),
            'presentation_count' => count($presentation),
            'evaluation_count' => count($evaluation),
            'assignment_count' => count($assignment),
            'student_note_characters' => (int) $noteChars,
            'duplicate_blocks' => $duplicates,
            'objective_evaluation_alignment' => $evaluationAlignment,
            'consolidation' => $this->consolidation->summary($topic),
        ];
    }

    private function hasMeasurableVerb(string $objective): bool
    {
        $normal = Str::lower(trim($objective));
        foreach (self::VAGUE_VERBS as $verb) {
            if (preg_match('/\b'.preg_quote($verb, '/').'\b/u', $normal)) return false;
        }
        foreach (self::MEASURABLE_VERBS as $verb) {
            if (preg_match('/\b'.preg_quote($verb, '/').'\b/u', $normal)) return true;
        }
        return false;
    }

    private function evaluationAlignment(array $objectives, array $evaluation): array
    {
        if (! $objectives || ! $evaluation) {
            return ['aligned' => false, 'matched' => 0, 'total' => count($objectives), 'ratio' => 0];
        }

        $matched = 0;
        foreach ($objectives as $objective) {
            $keywords = $this->keywords($objective);
            $hasMatch = collect($evaluation)->contains(function ($question) use ($keywords) {
                return count(array_intersect($keywords, $this->keywords($question))) >= 1;
            });
            if ($hasMatch) $matched++;
        }

        $ratio = $matched / max(1, count($objectives));
        return ['aligned' => $ratio >= 0.6, 'matched' => $matched, 'total' => count($objectives), 'ratio' => round($ratio, 2)];
    }

    private function keywords(string $text): array
    {
        $stop = array_merge(self::MEASURABLE_VERBS, [
            'the','a','an','and','or','of','to','in','on','for','with','from','by','at','is','are','be','being',
            'students','student','pupils','pupil','learners','learner','should','able','lesson','state','give','what','how','why',
        ]);
        preg_match_all('/[\pL\pN]{4,}/u', Str::lower($text), $matches);
        return collect($matches[0] ?? [])->reject(fn ($word) => in_array($word, $stop, true))->unique()->values()->all();
    }

    private function duplicateCount(array $items): int
    {
        $normalised = collect($items)->map(function ($item) {
            $text = Str::lower(trim((string) $item));
            return trim((string) preg_replace('/\s+/u', ' ', $text));
        })->filter();
        return max(0, $normalised->count() - $normalised->unique()->count());
    }

    private function contents(Collection $blocks, string $type): array
    {
        return $blocks->where('block_type', $type)
            ->pluck('content')
            ->map(fn ($content) => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }
}
