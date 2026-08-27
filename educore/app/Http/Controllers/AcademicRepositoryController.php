<?php

namespace App\Http\Controllers;

use App\Models\CurriculumSource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademicRepositoryController extends Controller
{
    public function index(Request $request)
    {
        $this->guardReader();

        $catalogue = $this->availableSources()->get();
        $subjectNames = $catalogue
            ->map(fn (CurriculumSource $source) => $this->metadataLabel($source, 'subject_label', 'Unmapped subject'))
            ->unique(fn (string $subject) => mb_strtolower($subject))
            ->sort('strnatcasecmp')
            ->values();

        $sources = $catalogue
            ->when($request->filled('search'), function (Collection $items) use ($request) {
                $search = mb_strtolower($request->string('search')->trim()->toString());

                return $items->filter(function (CurriculumSource $source) use ($search) {
                    return str_contains(mb_strtolower(implode(' ', [
                        $source->title,
                        $source->original_filename,
                        $source->cleaned_text,
                        $this->metadataLabel($source, 'class_label', ''),
                        $this->metadataLabel($source, 'term_label', ''),
                        $this->metadataLabel($source, 'subject_label', ''),
                    ])), $search);
                });
            })
            ->when($request->filled('subject'), function (Collection $items) use ($request) {
                $subject = $request->string('subject')->trim()->toString();

                return $items->filter(fn (CurriculumSource $source) => strcasecmp($this->metadataLabel($source, 'subject_label', 'Unmapped subject'), $subject) === 0
                );
            })
            ->values();

        $groups = $this->groupSources($sources);
        $metrics = [
            'resources' => $catalogue->count(),
            'classes' => $catalogue->map(fn (CurriculumSource $source) => $this->metadataLabel($source, 'class_label', 'Unmapped class'))->unique()->count(),
            'subjects' => $subjectNames->count(),
            'sections' => $catalogue->sum('fragments_count'),
        ];

        return view('academic-repository.index', compact('groups', 'metrics', 'subjectNames'));
    }

    public function show(CurriculumSource $curriculumSource)
    {
        $this->guardReader();
        $this->guardSource($curriculumSource);
        $curriculumSource->load(['fragments' => fn ($query) => $query->orderBy('sequence')->orderBy('id')]);

        return view('academic-repository.show', [
            'source' => $curriculumSource,
            'classLabel' => $this->metadataLabel($curriculumSource, 'class_label', 'Unmapped class'),
            'termLabel' => $this->metadataLabel($curriculumSource, 'term_label', 'Unmapped term'),
            'subjectLabel' => $this->metadataLabel($curriculumSource, 'subject_label', 'Unmapped subject'),
        ]);
    }

    public function download(CurriculumSource $curriculumSource)
    {
        $this->guardReader();
        $this->guardSource($curriculumSource);

        $path = (string) $curriculumSource->source_file_path;
        abort_unless($path !== '' && Storage::disk('local')->exists($path), 404, 'The original resource file is unavailable.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fallback = Str::slug($curriculumSource->title ?: 'academic-resource').($extension ? '.'.$extension : '');
        $filename = basename(str_replace('\\', '/', (string) ($curriculumSource->original_filename ?: $fallback)));

        return Storage::disk('local')->download($path, $filename);
    }

    private function availableSources()
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

    private function groupSources(Collection $sources): Collection
    {
        return $sources
            ->groupBy(fn (CurriculumSource $source) => $this->metadataLabel($source, 'class_label', 'Unmapped class'))
            ->sortKeysUsing('strnatcasecmp')
            ->map(fn ($classSources) => $classSources
                ->groupBy(fn (CurriculumSource $source) => $this->metadataLabel($source, 'term_label', 'Unmapped term'))
                ->sortKeysUsing(fn ($left, $right) => $this->termOrder($left) <=> $this->termOrder($right))
                ->map(fn ($termSources) => $termSources
                    ->groupBy(fn (CurriculumSource $source) => $this->metadataLabel($source, 'subject_label', 'Unmapped subject'))
                    ->sortKeysUsing('strnatcasecmp')));
    }

    private function guardReader(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403, 'Academic Repository access is limited to school administrators and teachers.');
    }

    private function guardSource(CurriculumSource $source): void
    {
        abort_unless(
            $source->tenant_id === null
            && $source->is_active
            && $source->extraction_status === 'extracted'
            && $source->index_status === 'indexed'
            && $source->fragments()->exists(),
            404
        );
    }

    private function metadataLabel(CurriculumSource $source, string $key, string $fallback): string
    {
        $metadata = $source->metadata;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        $value = is_array($metadata) ? trim((string) ($metadata[$key] ?? '')) : '';

        return $value !== '' ? $value : $fallback;
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
