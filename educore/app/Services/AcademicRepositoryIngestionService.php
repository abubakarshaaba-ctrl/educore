<?php

namespace App\Services;

use App\Models\AcademicTopic;
use App\Models\CurriculumSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcademicRepositoryIngestionService
{
    public function preview(): array
    {
        $sources = $this->eligibleSources()->get();
        $alreadyLinked = AcademicTopic::query()->whereNotNull('curriculum_source_id')->pluck('curriculum_source_id')->unique();

        $rows = $sources->map(function (CurriculumSource $source) use ($alreadyLinked) {
            $metadata = $this->metadata($source);
            $mapping = $this->mapping($source, $metadata);
            $schemeTopics = $this->resourceType($source) === 'scheme_of_work' ? $this->extractSchemeTopics($source) : [];
            $mapped = filled($mapping['class_label']) && filled($mapping['subject_label']) && filled($mapping['term_label']);

            return [
                'source_id' => $source->id,
                'title' => $source->title ?: $source->original_filename,
                'class_label' => $mapping['class_label'],
                'subject_label' => $mapping['subject_label'],
                'term_label' => $mapping['term_label'],
                'resource_type' => $this->resourceType($source),
                'candidate_topics' => count($schemeTopics) ?: 1,
                'mapped' => $mapped,
                'already_ingested' => $alreadyLinked->contains($source->id),
            ];
        });

        return [
            'sources' => $rows,
            'summary' => [
                'total_sources' => $rows->count(),
                'mapped_sources' => $rows->where('mapped', true)->count(),
                'unmapped_sources' => $rows->where('mapped', false)->count(),
                'already_ingested' => $rows->where('already_ingested', true)->count(),
                'ready_to_ingest' => $rows->where('mapped', true)->where('already_ingested', false)->count(),
                'candidate_topics' => $rows->where('mapped', true)->where('already_ingested', false)->sum('candidate_topics'),
            ],
        ];
    }

    public function ingestAll(): array
    {
        $created = 0;
        $skipped = 0;
        $sourceCount = 0;

        foreach ($this->eligibleSources()->cursor() as $source) {
            if (AcademicTopic::query()->where('curriculum_source_id', $source->id)->exists()) {
                $skipped++;
                continue;
            }

            $metadata = $this->metadata($source);
            $mapping = $this->mapping($source, $metadata);
            if (!filled($mapping['class_label']) || !filled($mapping['subject_label']) || !filled($mapping['term_label'])) {
                $skipped++;
                continue;
            }

            $sourceCount++;
            $resourceType = $this->resourceType($source);
            if ($resourceType === 'scheme_of_work') {
                $schemeTopics = $this->extractSchemeTopics($source);
                if ($schemeTopics) {
                    DB::transaction(function () use ($source, $mapping, $resourceType, $schemeTopics, &$created) {
                        foreach ($schemeTopics as $item) {
                            AcademicTopic::create([
                                'curriculum_source_id' => $source->id,
                                'class_label' => $mapping['class_label'],
                                'subject_label' => $mapping['subject_label'],
                                'term_label' => $mapping['term_label'],
                                'week_number' => $item['week'],
                                'topic' => $item['topic'],
                                'sub_topic' => $item['sub_topic'],
                                'resource_type' => $resourceType,
                                'reference' => $source->original_filename ?: $source->title,
                                'status' => 'draft',
                            ]);
                            $created++;
                        }
                    });
                    continue;
                }
            }

            DB::transaction(function () use ($source, $mapping, $metadata, $resourceType, &$created) {
                $parsed = $this->parseStandardSections($source);
                $topic = AcademicTopic::create([
                    'curriculum_source_id' => $source->id,
                    'class_label' => $mapping['class_label'],
                    'subject_label' => $mapping['subject_label'],
                    'term_label' => $mapping['term_label'],
                    'week_number' => $mapping['week_number'],
                    'lesson_number' => $mapping['lesson_number'],
                    'topic' => $parsed['topic'] ?: ($metadata['topic'] ?? $this->cleanTitle($source)),
                    'sub_topic' => $parsed['sub_topic'] ?: ($metadata['sub_topic'] ?? null),
                    'lesson_time' => $parsed['lesson_time'],
                    'duration_minutes' => $parsed['duration_minutes'],
                    'average_age' => $parsed['average_age'],
                    'sex' => $parsed['sex'],
                    'resource_type' => $resourceType,
                    'entry_behaviour' => $parsed['entry_behaviour'],
                    'previous_knowledge' => $parsed['previous_knowledge'],
                    'instructional_resources' => $parsed['instructional_resources'],
                    'introduction' => $parsed['introduction'],
                    'reference' => $parsed['reference'] ?: ($source->original_filename ?: $source->title),
                    'student_note_summary' => $parsed['student_note_summary'],
                    'status' => 'draft',
                ]);

                $blocks = $parsed['blocks'];
                if (!$blocks['explanation'] && $source->relationLoaded('fragments')) {
                    $blocks['explanation'] = $source->fragments->take(12)->pluck('content')->filter()->map(fn ($v) => trim((string) $v))->values()->all();
                }

                foreach ($blocks as $type => $items) {
                    $sequence = 1;
                    foreach ($items as $item) {
                        $content = is_array($item) ? ($item['content'] ?? '') : $item;
                        $title = is_array($item) ? ($item['title'] ?? null) : null;
                        if (!filled($content)) continue;
                        $topic->blocks()->create([
                            'block_type' => $type,
                            'sequence' => $sequence++,
                            'title' => $title,
                            'content' => trim((string) $content),
                            'is_required' => in_array($type, AcademicRepositoryKnowledgeService::REQUIRED_BLOCK_TYPES, true),
                            'is_approved' => false,
                        ]);
                    }
                }
                $created++;
            });
        }

        return compact('created', 'skipped', 'sourceCount');
    }

    private function eligibleSources()
    {
        return CurriculumSource::query()
            ->whereNull('tenant_id')
            ->where('is_active', true)
            ->where('extraction_status', 'extracted')
            ->where('index_status', 'indexed')
            ->whereHas('fragments')
            ->with(['fragments' => fn ($query) => $query->orderBy('sequence')->orderBy('id')]);
    }

    private function mapping(CurriculumSource $source, array $metadata): array
    {
        $identity = implode(' ', array_filter([$source->title, $source->original_filename, $source->raw_text]));

        return [
            'class_label' => trim((string) ($metadata['class_label'] ?? '')) ?: null,
            'subject_label' => trim((string) ($metadata['subject_label'] ?? '')) ?: null,
            'term_label' => trim((string) ($metadata['term_label'] ?? '')) ?: null,
            'week_number' => $this->integerMeta($metadata, ['week_number', 'week']) ?: $this->matchInt($identity, '/\bweek\s*[:#\-]?\s*(\d{1,2})\b/i'),
            'lesson_number' => $this->integerMeta($metadata, ['lesson_number', 'lesson']) ?: $this->matchInt($identity, '/\blesson\s*[:#\-]?\s*(\d{1,2})\b/i'),
        ];
    }

    private function parseStandardSections(CurriculumSource $source): array
    {
        $text = trim((string) ($source->raw_text ?: $source->cleaned_text));
        $section = fn (array $labels) => $this->section($text, $labels);

        $presentationText = $section(['PRESENTATION']);
        $presentation = $this->presentationSteps($presentationText);
        $objectives = $this->listItems($section(['BEHAVIOURAL OBJECTIVES', 'BEHAVIORAL OBJECTIVES', 'LEARNING OBJECTIVES', 'OBJECTIVES']));
        $evaluation = $this->listItems($section(['EVALUATION', 'REVIEW QUESTIONS', 'ASSESSMENT']));
        $assignment = $this->listItems($section(['ASSIGNMENT', 'HOMEWORK']));
        $explanation = $presentation ? [] : $this->paragraphs($section(['CONTENT', 'LESSON CONTENT', 'NOTE', 'NOTES']));

        return [
            'topic' => $this->firstLine($section(['TOPIC'])),
            'sub_topic' => $this->firstLine($section(['SUB-TOPIC', 'SUB TOPIC', 'SUBTOPIC'])),
            'lesson_time' => $this->firstLine($section(['TIME'])),
            'duration_minutes' => $this->durationMinutes($this->firstLine($section(['DURATION']))),
            'average_age' => $this->matchInt($section(['AVERAGE AGE']), '/\b(\d{1,2})\b/'),
            'sex' => $this->firstLine($section(['SEX'])),
            'entry_behaviour' => $section(['ENTRY BEHAVIOUR', 'ENTRY BEHAVIOR']),
            'previous_knowledge' => $section(['PREVIOUS / BACKGROUND KNOWLEDGE', 'PREVIOUS KNOWLEDGE', 'BACKGROUND KNOWLEDGE']),
            'instructional_resources' => $section(['INSTRUCTIONAL RESOURCES', 'INSTRUCTIONAL MATERIALS', 'TEACHING AIDS']),
            'introduction' => $section(['INTRODUCTION', 'SET INDUCTION']),
            'reference' => $section(['REFERENCE', 'REFERENCES']),
            'student_note_summary' => $section(['SUMMARY', 'LESSON SUMMARY']),
            'blocks' => [
                'objective' => $objectives,
                'presentation' => $presentation,
                'definition' => [],
                'explanation' => $explanation,
                'example' => [],
                'practical' => [],
                'note' => [],
                'evaluation' => $evaluation,
                'assignment' => $assignment,
            ],
        ];
    }

    private function section(string $text, array $labels): ?string
    {
        if ($text === '') return null;
        $all = ['TOPIC','SUB-TOPIC','SUB TOPIC','TIME','DURATION','AVERAGE AGE','SEX','ENTRY BEHAVIOUR','ENTRY BEHAVIOR','PREVIOUS / BACKGROUND KNOWLEDGE','PREVIOUS KNOWLEDGE','BACKGROUND KNOWLEDGE','BEHAVIOURAL OBJECTIVES','BEHAVIORAL OBJECTIVES','LEARNING OBJECTIVES','OBJECTIVES','INSTRUCTIONAL RESOURCES','INSTRUCTIONAL MATERIALS','TEACHING AIDS','INTRODUCTION','SET INDUCTION','PRESENTATION','CONTENT','LESSON CONTENT','NOTE','NOTES','EVALUATION','REVIEW QUESTIONS','ASSESSMENT','ASSIGNMENT','HOMEWORK','REFERENCE','REFERENCES','SUMMARY','LESSON SUMMARY'];
        $labelPattern = implode('|', array_map(fn ($v) => preg_quote($v, '/'), $labels));
        $stopPattern = implode('|', array_map(fn ($v) => preg_quote($v, '/'), $all));
        if (preg_match('/(?:^|\R)\s*(?:'.$labelPattern.')\s*[:\-]?\s*(?:\R|\s)(.*?)(?=\R\s*(?:'.$stopPattern.')\s*[:\-]?\s*(?:\R|$)|\z)/isu', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function presentationSteps(?string $text): array
    {
        if (!filled($text)) return [];
        $parts = preg_split('/(?=\bSTEP\s+(?:[IVX]+|\d+)\b\s*[:.\-]?)/iu', trim($text)) ?: [];
        $steps = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            if (preg_match('/^STEP\s+(?:[IVX]+|\d+)\s*[:.\-]?\s*([^\r\n]*)[\r\n]*(.*)$/isu', $part, $m)) {
                $steps[] = ['title' => trim($m[1]) ?: null, 'content' => trim($m[2])];
            } else {
                $steps[] = ['title' => null, 'content' => $part];
            }
        }
        return $steps;
    }

    private function extractSchemeTopics(CurriculumSource $source): array
    {
        $text = (string) ($source->raw_text ?: $source->cleaned_text);
        $items = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if (!preg_match('/^week\s*(\d{1,2})\s*[:.\-–—]?\s+(.+)$/iu', $line, $m)) continue;
            $rest = trim($m[2]);
            $parts = preg_split('/\s*[;|]\s*/u', $rest, 2) ?: [$rest];
            $items[] = ['week' => (int) $m[1], 'topic' => trim($parts[0]), 'sub_topic' => isset($parts[1]) ? trim($parts[1]) : null];
        }
        return collect($items)->filter(fn ($item) => filled($item['topic']))->unique(fn ($item) => $item['week'].'|'.mb_strtolower($item['topic']))->values()->all();
    }

    private function resourceType(CurriculumSource $source): string
    {
        $identity = mb_strtolower(implode(' ', array_filter([$source->title, $source->original_filename])));
        return match (true) {
            str_contains($identity, 'scheme') && str_contains($identity, 'work') => 'scheme_of_work',
            str_contains($identity, 'syllabus') => 'syllabus',
            str_contains($identity, 'curriculum') || str_contains($identity, 'nerdc') => 'curriculum',
            str_contains($identity, 'practical') => 'practical_guide',
            str_contains($identity, 'past question') => 'past_questions',
            str_contains($identity, 'lesson plan') => 'lesson_plan',
            str_contains($identity, 'teacher') && str_contains($identity, 'note') => 'teacher_note',
            str_contains($identity, 'textbook') => 'textbook_extract',
            default => 'lesson_note',
        };
    }

    private function metadata(CurriculumSource $source): array
    {
        return is_array($source->metadata) ? $source->metadata : [];
    }

    private function integerMeta(array $metadata, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (isset($metadata[$key]) && is_numeric($metadata[$key])) return (int) $metadata[$key];
        }
        return null;
    }

    private function matchInt(?string $value, string $pattern): ?int
    {
        if (!filled($value) || !preg_match($pattern, $value, $m)) return null;
        return (int) $m[1];
    }

    private function durationMinutes(?string $value): ?int
    {
        if (!filled($value)) return null;
        if (preg_match('/(\d{1,3})\s*(?:minutes?|mins?)/i', $value, $m)) return (int) $m[1];
        return $this->matchInt($value, '/\b(\d{1,3})\b/');
    }

    private function listItems(?string $text): array
    {
        if (!filled($text)) return [];
        $lines = preg_split('/\R/u', trim($text)) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $line = trim(preg_replace('/^\s*(?:[•▪●\-]|\d+[.)]|[a-z][.)])\s*/iu', '', $line));
            if ($line !== '' && !preg_match('/^At the end of the lesson/i', $line)) $items[] = $line;
        }
        return $items ?: [trim($text)];
    }

    private function paragraphs(?string $text): array
    {
        if (!filled($text)) return [];
        return collect(preg_split('/\R\s*\R/u', trim($text)) ?: [])->map(fn ($v) => trim($v))->filter()->values()->all();
    }

    private function firstLine(?string $text): ?string
    {
        if (!filled($text)) return null;
        return trim((string) (preg_split('/\R/u', trim($text))[0] ?? '')) ?: null;
    }

    private function cleanTitle(CurriculumSource $source): string
    {
        $title = $source->title ?: pathinfo((string) $source->original_filename, PATHINFO_FILENAME);
        return Str::of((string) $title)->replace(['_', '-'], ' ')->squish()->value();
    }
}
