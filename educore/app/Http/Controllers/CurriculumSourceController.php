<?php

namespace App\Http\Controllers;

use App\Models\ClassLevel;
use App\Models\CurriculumSource;
use App\Models\CurriculumTopic;
use App\Models\RepositoryImport;
use App\Models\Subject;
use App\Services\Curriculum\AcademicRepositoryIngestionService;
use App\Services\Curriculum\ResumableAcademicRepositoryUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurriculumSourceController extends Controller
{
    private function guard(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Platform Super Admin access required.');
    }

    public function index(Request $request)
    {
        $this->guard();

        $query = CurriculumSource::whereNull('tenant_id')->withCount('fragments');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim().'%';
            $query->where(fn ($q) => $q
                ->where('title', 'like', $search)
                ->orWhere('original_filename', 'like', $search)
                ->orWhere('cleaned_text', 'like', $search));
        }

        foreach (['class_label' => 'class', 'subject_label' => 'subject', 'term_label' => 'term'] as $metadataKey => $filter) {
            if ($request->filled($filter)) {
                $query->where("metadata->{$metadataKey}", $request->string($filter)->toString());
            }
        }

        $sources = $query->latest()->get();

        $repository = CurriculumSource::whereNull('tenant_id');
        $analytics = [
            'resources' => (clone $repository)->count(),
            'indexed' => (clone $repository)->where('index_status', 'indexed')->count(),
            'failed' => (clone $repository)->where('extraction_status', 'failed')->count(),
            'review' => (clone $repository)->where('needs_review', true)->where('extraction_status', 'extracted')->count(),
            'imports' => RepositoryImport::count(),
        ];

        $metadata = (clone $repository)->get(['metadata'])->pluck('metadata')->map(function ($value) {
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : [];
            }

            return [];
        })->filter();
        $folders = [
            'classes' => $metadata->pluck('class_label')->filter()->unique()->sort()->values(),
            'subjects' => $metadata->pluck('subject_label')->filter()->unique()->sort()->values(),
            'terms' => $metadata->pluck('term_label')->filter()->unique()->sort()->values(),
        ];

        $groups = $sources
            ->groupBy(fn (CurriculumSource $source) => $this->metadataLabel($source, 'class_label', 'Unmapped class'))
            ->sortKeysUsing('strnatcasecmp')
            ->map(fn ($classSources) => $classSources
                ->groupBy(fn (CurriculumSource $source) => $this->metadataLabel($source, 'term_label', 'Unmapped term'))
                ->sortKeysUsing(fn ($left, $right) => $this->termOrder($left) <=> $this->termOrder($right))
                ->map(fn ($termSources) => $termSources
                    ->groupBy(fn (CurriculumSource $source) => $this->metadataLabel($source, 'subject_label', 'Unmapped subject'))
                    ->sortKeysUsing('strnatcasecmp')));

        return view('curriculum-sources.index', compact('sources', 'analytics', 'folders', 'groups'));
    }

    public function create()
    {
        $this->guard();

        return view('curriculum-sources.import');
    }

    public function topics(Request $request)
    {
        $this->guard();

        $query = CurriculumTopic::query();
        if ($request->filled('search')) {
            $query->where('topic', 'like', '%'.$request->search.'%');
        }

        $topics = $query->latest()->paginate(20)->withQueryString();
        $subjects = Subject::orderBy('name')->get()->unique(fn ($subject) => mb_strtolower($subject->name))->values();
        $classLevels = ClassLevel::orderBy('order_index')->get()->unique(fn ($level) => mb_strtolower($level->name))->values();
        $subjectNames = $subjects->pluck('name', 'id');
        $classLevelNames = $classLevels->pluck('name', 'id');

        return view('curriculum-sources.topics', compact('topics', 'subjects', 'classLevels', 'subjectNames', 'classLevelNames'));
    }

    public function store(Request $request, AcademicRepositoryIngestionService $service)
    {
        $this->guard();
        $data = $request->validate(array_merge([
            'source_files' => 'required|array|min:1|max:2',
            'source_files.*' => 'file|max:2097152|mimes:docx,doc,pdf,xlsx,xls,zip',
        ], $this->metadataRules()));
        $data = $this->normaliseMetadata($data);
        foreach ($request->file('source_files') as $file) {
            $service->ingest($file, $data, auth()->id());
        }

        return redirect()->route('super.curriculum-sources.index')->with('success', 'Archive imported.');
    }

    public function initiateUpload(Request $request, ResumableAcademicRepositoryUploadService $uploads)
    {
        $this->guard();
        $file = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1', 'max:'.config('academic_repository.max_upload_size', 2147483648)],
            'last_modified' => ['nullable', 'integer', 'min:0'],
            'fingerprint' => ['required', 'string', 'max:500'],
        ]);
        $metadata = $this->normaliseMetadata($request->validate($this->metadataRules()));

        return response()->json($uploads->create($file, $metadata, (int) auth()->id()), 201);
    }

    public function uploadStatus(string $upload, ResumableAcademicRepositoryUploadService $uploads)
    {
        $this->guard();

        return response()->json($uploads->status($upload, (int) auth()->id()));
    }

    public function uploadChunk(Request $request, string $upload, ResumableAcademicRepositoryUploadService $uploads)
    {
        $this->guard();
        $data = $request->validate([
            'index' => ['required', 'integer', 'min:0'],
            'chunk' => ['required', 'file', 'max:9216'],
        ]);

        return response()->json($uploads->storeChunk(
            $upload,
            (int) $data['index'],
            $request->file('chunk'),
            (int) auth()->id()
        ));
    }

    public function completeUpload(
        string $upload,
        ResumableAcademicRepositoryUploadService $uploads,
        AcademicRepositoryIngestionService $ingestion
    ) {
        $this->guard();
        $state = $uploads->complete($upload, (int) auth()->id(), $ingestion);

        return response()->json($state, $state['status'] === 'processing' ? 202 : 200);
    }

    public function cancelUpload(string $upload, ResumableAcademicRepositoryUploadService $uploads)
    {
        $this->guard();
        $uploads->cancel($upload, (int) auth()->id());

        return response()->noContent();
    }

    public function activate(CurriculumSource $curriculumSource)
    {
        $this->guard();
        abort_unless($curriculumSource->tenant_id === null, 403);
        abort_if($curriculumSource->extraction_status !== 'extracted' || ! $curriculumSource->fragments()->exists(), 422, 'Re-index this resource before activation.');
        $curriculumSource->update(['review_status' => 'approved', 'is_active' => true, 'needs_review' => false, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        return back()->with('success', 'Resource activated and available to lesson generation.');
    }

    public function deactivate(CurriculumSource $curriculumSource)
    {
        $this->guard();
        abort_unless($curriculumSource->tenant_id === null, 403);
        $curriculumSource->update(['is_active' => false]);

        return back()->with('success', 'Resource deactivated.');
    }

    public function destroy(CurriculumSource $curriculumSource)
    {
        $this->guard();
        abort_unless($curriculumSource->tenant_id === null, 403);
        $curriculumSource->delete();

        return back()->with('success', 'Repository resource removed.');
    }

    public function reindex(CurriculumSource $curriculumSource, AcademicRepositoryIngestionService $service)
    {
        $this->guard();
        abort_unless($curriculumSource->tenant_id === null, 403);

        try {
            $service->reindex($curriculumSource, (int) auth()->id());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['resource' => $exception->getMessage()]);
        }

        return back()->with('success', 'Resource re-indexed. It can now be reviewed and activated.');
    }

    public function bulk(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'source_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'source_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $sources = CurriculumSource::whereNull('tenant_id')
            ->whereIn('id', $data['source_ids'])
            ->withCount('fragments')
            ->get();
        abort_if($sources->count() !== count($data['source_ids']), 422, 'One or more selected resources are unavailable.');

        $changed = 0;
        $skipped = 0;
        DB::transaction(function () use ($sources, $data, &$changed, &$skipped) {
            foreach ($sources as $source) {
                if ($data['action'] === 'activate') {
                    if ($source->extraction_status !== 'extracted' || $source->fragments_count < 1) {
                        $skipped++;

                        continue;
                    }
                    $source->update([
                        'review_status' => 'approved', 'is_active' => true, 'needs_review' => false,
                        'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
                    ]);
                } elseif ($data['action'] === 'deactivate') {
                    $source->update(['is_active' => false]);
                } else {
                    $source->delete();
                }
                $changed++;
            }
        });

        $label = match ($data['action']) {
            'activate' => 'activated', 'deactivate' => 'deactivated', default => 'removed',
        };
        $message = number_format($changed).' '.str('resource')->plural($changed).' '.$label.'.';
        if ($skipped) {
            $message .= ' '.number_format($skipped).' failed-extraction '.str('resource')->plural($skipped).' skipped; re-index before activation.';
        }

        return back()->with('success', $message);
    }

    public function bulkAll(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'confirmation' => ['nullable', 'string', 'max:40'],
        ]);

        if ($data['action'] === 'delete') {
            abort_unless(
                hash_equals('REMOVE ALL', trim((string) ($data['confirmation'] ?? ''))),
                422,
                'Type REMOVE ALL to confirm this action.'
            );
        }

        $repository = CurriculumSource::whereNull('tenant_id');
        $total = (clone $repository)->count();
        if ($total === 0) {
            return back()->with('success', 'The Academic Repository is already empty.');
        }

        if ($data['action'] === 'activate') {
            $eligible = (clone $repository)
                ->where('extraction_status', 'extracted')
                ->where('index_status', 'indexed')
                ->whereHas('fragments');
            $eligibleCount = (clone $eligible)->count();

            DB::transaction(fn () => $eligible->update([
                'review_status' => 'approved',
                'is_active' => true,
                'needs_review' => false,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]));

            $skipped = $total - $eligibleCount;
            $message = number_format($eligibleCount).' eligible '.str('resource')->plural($eligibleCount).' activated.';
            if ($skipped > 0) {
                $message .= ' '.number_format($skipped).' failed or unindexed '.str('resource')->plural($skipped).' skipped.';
            }

            return back()->with('success', $message);
        }

        if ($data['action'] === 'deactivate') {
            DB::transaction(fn () => $repository->update(['is_active' => false]));

            return back()->with('success', number_format($total).' repository '.str('resource')->plural($total).' deactivated.');
        }

        DB::transaction(fn () => $repository->delete());

        return back()->with('success', number_format($total).' repository '.str('resource')->plural($total).' removed.');
    }

    public function topic(Request $request)
    {
        $this->guard();
        $data = $request->validate(['subject_id' => 'required|integer', 'curriculum_level_id' => 'required|integer', 'topic' => 'required|string|max:255', 'subtopics_text' => 'nullable|string', 'keywords_text' => 'nullable|string']);
        CurriculumTopic::create(['subject_id' => $data['subject_id'], 'curriculum_level_id' => $data['curriculum_level_id'], 'topic' => $data['topic'], 'subtopics' => preg_split('/[,;\n]+/', $data['subtopics_text'] ?? '', -1, PREG_SPLIT_NO_EMPTY), 'keywords' => preg_split('/[,;\n]+/', $data['keywords_text'] ?? '', -1, PREG_SPLIT_NO_EMPTY), 'created_by' => auth()->id()]);

        return back()->with('success', 'Canonical curriculum topic added.');
    }

    private function metadataRules(): array
    {
        return [
            'title' => 'nullable|string|max:255', 'authority' => ['required', Rule::in(['NERDC', 'WAEC', 'NECO', 'JAMB', 'TEXTBOOK', 'OTHER'])],
            'source_type' => ['required', Rule::in(['curriculum_document', 'teacher_guide', 'assessment_syllabus', 'approved_textbook', 'school_scheme', 'lesson_note'])],
            'subject_id' => 'nullable|integer', 'curriculum_level_id' => 'nullable|integer', 'topic' => 'nullable|string|max:255', 'subtopic' => 'nullable|string|max:255', 'version' => 'nullable|string|max:80',
            'rights_status' => ['required', Rule::in(['public_official', 'licensed', 'institution_authorised'])], 'is_official' => 'nullable|boolean', 'column_mapping_json' => 'nullable|json',
        ];
    }

    private function normaliseMetadata(array $data): array
    {
        $data['column_mapping'] = ($data['column_mapping_json'] ?? null)
            ? json_decode($data['column_mapping_json'], true)
            : [];
        unset($data['column_mapping_json'], $data['source_files']);

        return $data;
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
        return match (mb_strtolower($term)) {
            'first term' => 10, 'second term' => 20, 'third term' => 30, 'general' => 40, default => 50,
        };
    }
}
