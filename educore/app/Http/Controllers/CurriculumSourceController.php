<?php

namespace App\Http\Controllers;

use App\Models\ClassLevel;
use App\Models\CurriculumSource;
use App\Models\CurriculumTopic;
use App\Models\RepositoryImport;
use App\Models\Subject;
use App\Services\Curriculum\AcademicRepositoryIngestionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurriculumSourceController extends Controller
{
    private function guard(): void { abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Platform Super Admin access required.'); }

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

        $sources = $query->latest()->paginate(20)->withQueryString();

        $repository = CurriculumSource::whereNull('tenant_id');
        $analytics = [
            'resources' => (clone $repository)->count(),
            'indexed' => (clone $repository)->where('index_status', 'indexed')->count(),
            'failed' => (clone $repository)->where('extraction_status', 'failed')->count(),
            'review' => (clone $repository)->where('needs_review', true)->count(),
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

        return view('curriculum-sources.index', compact('sources', 'analytics', 'folders'));
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
        $data = $request->validate([
            'source_files'=>'required|array|min:1|max:2',
            'source_files.*'=>'file|max:2097152|mimes:docx,doc,pdf,xlsx,xls,zip',
            'title'=>'nullable|string|max:255','authority'=>['required',Rule::in(['NERDC','WAEC','NECO','JAMB','TEXTBOOK','OTHER'])],
            'source_type'=>['required',Rule::in(['curriculum_document','teacher_guide','assessment_syllabus','approved_textbook','school_scheme','lesson_note'])],
            'subject_id'=>'nullable|integer','curriculum_level_id'=>'nullable|integer','topic'=>'nullable|string|max:255','subtopic'=>'nullable|string|max:255','version'=>'nullable|string|max:80',
            'rights_status'=>['required',Rule::in(['public_official','licensed','institution_authorised'])],'is_official'=>'nullable|boolean','column_mapping_json'=>'nullable|json',
        ]);
        $data['column_mapping'] = ($data['column_mapping_json'] ?? null) ? json_decode($data['column_mapping_json'], true) : [];
        foreach ($request->file('source_files') as $file) $service->ingest($file, $data, auth()->id());
        return redirect()->route('super.curriculum-sources.index')->with('success', 'Archive imported.');
    }

    public function activate(CurriculumSource $curriculumSource) { $this->guard(); abort_unless($curriculumSource->tenant_id===null,403); abort_if($curriculumSource->extraction_status!=='extracted',422,'A resource with failed extraction cannot be activated.'); $curriculumSource->update(['review_status'=>'approved','is_active'=>true,'needs_review'=>false,'reviewed_by'=>auth()->id(),'reviewed_at'=>now()]); return back()->with('success','Resource activated and available to lesson generation.'); }
    public function deactivate(CurriculumSource $curriculumSource) { $this->guard(); $curriculumSource->update(['is_active'=>false]); return back()->with('success','Resource deactivated.'); }
    public function destroy(CurriculumSource $curriculumSource) { $this->guard(); abort_unless($curriculumSource->tenant_id===null,403); $curriculumSource->delete(); return back()->with('success','Repository resource removed.'); }

    public function topic(Request $request)
    {
        $this->guard(); $data=$request->validate(['subject_id'=>'required|integer','curriculum_level_id'=>'required|integer','topic'=>'required|string|max:255','subtopics_text'=>'nullable|string','keywords_text'=>'nullable|string']);
        CurriculumTopic::create(['subject_id'=>$data['subject_id'],'curriculum_level_id'=>$data['curriculum_level_id'],'topic'=>$data['topic'],'subtopics'=>preg_split('/[,;\n]+/',$data['subtopics_text']??'',-1,PREG_SPLIT_NO_EMPTY),'keywords'=>preg_split('/[,;\n]+/',$data['keywords_text']??'',-1,PREG_SPLIT_NO_EMPTY),'created_by'=>auth()->id()]);
        return back()->with('success','Canonical curriculum topic added.');
    }
}
