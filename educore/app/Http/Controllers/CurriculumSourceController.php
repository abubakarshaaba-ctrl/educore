<?php

namespace App\Http\Controllers;

use App\Models\CurriculumFragment;
use App\Models\CurriculumSource;
use App\Models\ClassLevel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ZipArchive;

class CurriculumSourceController extends Controller
{
    public function index()
    {
        $platform = auth()->user()->isSuperAdmin(); $tenantId = $platform ? null : auth()->user()->tenant_id;
        $this->authoriseManager($platform);
        $sources = CurriculumSource::query()->when($platform, fn($q)=>$q->whereNull('tenant_id'), fn($q)=>$q->where('tenant_id',$tenantId))->withCount('fragments')->latest()->paginate(20);
        $subjects = Subject::query()->when(! $platform, fn($q)=>$q->where('tenant_id',$tenantId))->orderBy('name')->get();
        $classLevels = ClassLevel::query()->when(! $platform, fn($q)=>$q->where('tenant_id',$tenantId))->orderBy('order_index')->get();
        return view('curriculum-sources.index', compact('platform','sources','subjects','classLevels'));
    }

    public function store(Request $request)
    {
        $platform = auth()->user()->isSuperAdmin(); $tenantId = $platform ? null : (int) auth()->user()->tenant_id;
        $this->authoriseManager($platform);
        $data = $request->validate([
            'authority'=>['required',Rule::in(['NERDC','WAEC','NECO','JAMB','SCHOOL','TEXTBOOK','OTHER'])],
            'source_type'=>['required',Rule::in(['curriculum_document','teacher_guide','assessment_syllabus','approved_textbook','school_scheme'])],
            'title'=>'required|string|max:255','version'=>'required|string|max:80','publication_year'=>'nullable|integer|min:1900|max:'.(date('Y')+1),
            'publisher'=>'nullable|string|max:255','authors'=>'nullable|string|max:255','isbn'=>'nullable|string|max:32',
            'education_level'=>'nullable|string|max:80','subject_id'=>'nullable|integer','class_level_id'=>'nullable|integer',
            'topic'=>'required|string|max:255','subtopic'=>'nullable|string|max:255','source_reference'=>'nullable|string|max:255',
            'approval_reference'=>'nullable|required_if:authority,TEXTBOOK|string|max:255',
            'rights_status'=>['required',Rule::in(['public_official','licensed','institution_authorised'])],
            'source_file'=>'required|file|max:15360|mimes:txt,csv,docx','is_official'=>'nullable|boolean',
        ]);
        if (! $platform && in_array($data['authority'], ['NERDC','WAEC','NECO','JAMB'], true)) $data['authority'] = 'SCHOOL';
        if ($data['authority'] === 'TEXTBOOK') $data['source_type'] = 'approved_textbook';
        $this->assertAcademicScope($data, $tenantId, $platform);
        $upload = $request->file('source_file'); $checksum = hash_file('sha256', $upload->getRealPath());
        $text = $this->extract($upload->getRealPath(), strtolower($upload->getClientOriginalExtension()));
        if (mb_strlen(trim($text)) < 40) return back()->withErrors(['source_file'=>'No usable text could be extracted from this document.'])->withInput();
        $path = $upload->store('curriculum-sources/'.($platform?'platform':$tenantId), 'local');

        DB::transaction(function () use ($data,$tenantId,$platform,$checksum,$path,$text) {
            $source = CurriculumSource::create(['tenant_id'=>$tenantId,'subject_id'=>$data['subject_id']??null,'class_level_id'=>$data['class_level_id']??null,
                'authority'=>$data['authority'],'source_type'=>$data['source_type'],'title'=>$data['title'],'education_level'=>$data['education_level']??null,
                'version'=>$data['version'],'publication_year'=>$data['publication_year']??null,'source_reference'=>$data['source_reference']??null,
                'publisher'=>$data['publisher']??null,'authors'=>$data['authors']??null,'isbn'=>$data['isbn']??null,
                'approval_reference'=>$data['approval_reference']??null,'rights_status'=>$data['rights_status'],
                'source_file_path'=>$path,'checksum'=>$checksum,'is_official'=>$platform && (bool)($data['is_official']??false),'is_active'=>false,
                'review_status'=>'pending','created_by'=>auth()->id(),'metadata'=>['original_name'=>request()->file('source_file')->getClientOriginalName()]]);
            foreach ($this->chunks($text) as $sequence => $chunk) CurriculumFragment::create(['curriculum_source_id'=>$source->id,
                'subject_id'=>$source->subject_id,'class_level_id'=>$source->class_level_id,'topic'=>$data['topic'],'subtopic'=>$data['subtopic']??null,
                'content'=>$chunk,'sequence'=>$sequence,'metadata'=>['ingestion'=>'deterministic-text-v1']]);
        });
        return back()->with('success','Curriculum source uploaded and segmented. Review and activate it before AI retrieval.');
    }

    public function activate(CurriculumSource $curriculumSource)
    {
        $platform = auth()->user()->isSuperAdmin(); $this->authoriseManager($platform);
        if ($platform ? $curriculumSource->tenant_id !== null : (int)$curriculumSource->tenant_id !== (int)auth()->user()->tenant_id) abort(403);
        $curriculumSource->update(['review_status'=>'approved','is_active'=>true,'reviewed_by'=>auth()->id(),'reviewed_at'=>now()]);
        return back()->with('success','Curriculum source approved and activated.');
    }

    private function authoriseManager(bool $platform): void
    {
        if (! $platform && ! in_array(auth()->user()->role, ['admin','academic_administrator'], true)) abort(403);
    }

    private function assertAcademicScope(array $data, ?int $tenantId, bool $platform): void
    {
        foreach (['subject_id'=>'subjects','class_level_id'=>'class_levels'] as $field=>$table) if (!empty($data[$field])) {
            $exists=DB::table($table)->where('id',$data[$field])->when(! $platform,fn($q)=>$q->where('tenant_id',$tenantId))->exists(); if(!$exists) abort(422,'Invalid academic scope.');
        }
    }

    private function extract(string $path, string $extension): string
    {
        if (in_array($extension,['txt','csv'],true)) return (string) file_get_contents($path);
        $zip=new ZipArchive(); if($zip->open($path)!==true) return ''; $xml=$zip->getFromName('word/document.xml')?:''; $zip->close();
        return html_entity_decode(strip_tags(str_replace(['</w:p>','</w:tr>'],["\n","\n"],$xml)),ENT_QUOTES|ENT_XML1,'UTF-8');
    }

    private function chunks(string $text): array
    {
        $paragraphs=preg_split('/\n{2,}/',preg_replace('/[ \t]+/',' ',trim($text))); $chunks=[]; $current='';
        foreach($paragraphs as $paragraph){$paragraph=trim($paragraph);if($paragraph==='')continue;if(mb_strlen($current.' '.$paragraph)>2600){$chunks[]=$current;$current='';}$current.=($current?"\n\n":'').$paragraph;}
        if($current)$chunks[]=$current; return $chunks;
    }
}
