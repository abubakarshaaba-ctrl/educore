<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurriculumSource;
use App\Services\AcademicRepositoryCatalogueService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademicRepositoryController extends Controller
{
    public function __construct(private readonly AcademicRepositoryCatalogueService $catalogue) {}

    public function classes(Request $request)
    {
        $this->guardReader($request);
        $sources = $this->catalogue->catalogue();
        $groups = $this->catalogue->groups($sources);

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'metrics' => [
                'resources' => $sources->count(),
                'classes' => $groups->count(),
                'subjects' => $this->catalogue->facets($sources)['subjects']->count(),
                'sections' => (int) $sources->sum('fragments_count'),
            ],
            'classes' => $groups->map(function ($terms, string $class) {
                return [
                    'name' => $class,
                    'resources_count' => $terms->flatten(2)->count(),
                    'terms' => $terms->map(function ($subjects, string $term) {
                        return [
                            'name' => $term,
                            'resources_count' => $subjects->flatten(1)->count(),
                            'subjects' => $subjects->map(fn ($resources, string $subject) => [
                                'name' => $subject,
                                'resources_count' => $resources->count(),
                            ])->values(),
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function resources(Request $request)
    {
        $this->guardReader($request);
        $data = $request->validate([
            'class' => ['nullable', 'string', 'max:100'],
            'term' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:150'],
            'query' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $all = $this->catalogue->catalogue();
        $filtered = $this->catalogue->filter($all, $data);
        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 30);
        $paginator = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'filters' => $this->catalogue->facets($all),
            'resources' => collect($paginator->items())->map(fn (CurriculumSource $source) => $this->catalogue->resourcePayload($source)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, int $sourceId)
    {
        $this->guardReader($request);
        $source = CurriculumSource::with(['fragments' => fn ($query) => $query->orderBy('sequence')->orderBy('id')])
            ->withCount('fragments')->findOrFail($sourceId);
        $this->guardSource($source);

        return response()->json([
            'contract_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'resource' => array_merge($this->catalogue->resourcePayload($source), [
                'fragments' => $source->fragments->map(fn ($fragment) => [
                    'id' => $fragment->id,
                    'sequence' => $fragment->sequence,
                    'theme' => $fragment->theme,
                    'topic' => $fragment->topic,
                    'subtopic' => $fragment->subtopic,
                    'content' => $fragment->content,
                    'learning_expectation' => $fragment->learning_expectation,
                    'source_locator' => $fragment->source_locator,
                ])->values(),
            ]),
        ]);
    }

    public function content(Request $request, int $sourceId)
    {
        return $this->show($request, $sourceId);
    }

    public function download(Request $request, int $sourceId)
    {
        $this->guardReader($request);
        $source = CurriculumSource::withCount('fragments')->findOrFail($sourceId);
        $this->guardSource($source);
        $path = (string) $source->source_file_path;
        abort_unless($path !== '' && Storage::disk('local')->exists($path), 404, 'The original resource file is unavailable.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fallback = Str::slug($source->title ?: 'academic-resource').($extension ? '.'.$extension : '');
        $filename = basename(str_replace('\\', '/', (string) ($source->original_filename ?: $fallback)));

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => $source->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function guardReader(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403, 'Academic Repository access is limited to school administrators and teachers.');
    }

    private function guardSource(CurriculumSource $source): void
    {
        abort_unless($this->catalogue->available($source), 404);
    }
}
