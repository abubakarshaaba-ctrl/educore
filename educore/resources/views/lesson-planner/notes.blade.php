@extends('layouts.app')
@section('title', 'Student Notes — ' . $lessonPlan->topic)

@section('content')
<div class="page-content">
    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
        <div>
            <a href="{{ route('lesson-planner.show', $lessonPlan) }}" style="color:var(--slate-light);text-decoration:none;font-size:13px">← Lesson Plan</a>
            <h1 style="font-size:20px;font-weight:800;color:var(--midnight);margin-top:4px">Student Notes</h1>
            <p style="font-size:13px;color:var(--slate-light);margin-top:2px">
                {{ $lessonPlan->subject->name }} &middot; {{ $lessonPlan->classLevel->name }} &middot; {{ $lessonPlan->topic }}
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($revision)<button class="btn btn-primary" type="button" onclick="document.getElementById('noteEditor').hidden=false">Edit Note</button>@endif
            <a href="{{ route('lesson-planner.print-notes', $lessonPlan) }}" target="_blank" class="btn btn-secondary">
                🖨 Print Notes
            </a>
            <button onclick="generateStudentNotes()" id="regenBtn" class="btn btn-primary" style="background:linear-gradient(135deg,#059669,#047857);border:none">
                ✨ Regenerate Notes
            </button>
        </div>
    </div>
    @if($revision)<form id="noteEditor" hidden method="POST" action="{{route('lesson-planner.notes.update',$lessonPlan)}}" style="background:#fff;border:1px solid var(--border);padding:18px;border-radius:12px;margin-bottom:18px">@csrf @method('PUT')<h3>Edit Student Note</h3><p style="font-size:13px;color:var(--slate-light)">Make corrections before publishing or downloading.</p><textarea name="note_text" style="width:100%;min-height:420px;padding:12px;border:1px solid var(--border);border-radius:8px">{{strip_tags(str_replace(['</p>','</h2>','</h3>','</li>'],["\n\n","\n\n","\n","\n"],$lessonPlan->lesson_notes))}}</textarea><div style="margin-top:10px;display:flex;gap:8px"><button name="note_status" value="draft" class="btn btn-secondary">Save Draft</button><button name="note_status" value="published" class="btn btn-primary">Publish Note</button></div></form>@endif

    {{-- Notes meta banner --}}
    <div style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);border:1px solid #C4B5FD;border-radius:var(--radius);padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
        <div style="font-size:28px">📖</div>
        <div>
            <div style="font-weight:700;color:#4C1D95;font-size:14px">AI-Generated Study Notes</div>
            <div style="font-size:12px;color:#6D28D9">
                {{ $lessonPlan->subject->name }} &middot; {{ $lessonPlan->classLevel->name }}
                &middot; {{ $lessonPlan->topic }}
                &middot; Revision {{ $revision->revision ?? 'Legacy' }} &middot; {{ ucfirst($revision->depth ?? $lessonPlan->note_depth ?? 'standard') }} depth
            </div>
        </div>
    </div>

    @if($validation)
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;margin-bottom:18px">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center"><div><b>Curriculum validation</b><div style="font-size:12px;color:#64748b">Plan coverage: {{ $validation->plan_coverage }}. Alignment appears only where approved source evidence was retrieved.</div></div><span style="padding:5px 11px;border-radius:999px;background:{{ $validation->status==='pass'?'#dcfce7':'#fef3c7' }};color:{{ $validation->status==='pass'?'#166534':'#92400e' }}">{{ strtoupper($validation->status) }}</span></div>
        @if($validation->missing_plan_items)<ul style="margin:12px 0 0;color:#9a3412">@foreach($validation->missing_plan_items as $item)<li>{{ $item }}</li>@endforeach</ul><button onclick="regenerateMissing()" id="missingBtn" class="btn btn-secondary" style="margin-top:10px">Regenerate missing content</button>@endif
        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-top:12px">@foreach($validation->authority_alignment ?? [] as $authority=>$alignment)<span style="font-size:11px;background:#f1f5f9;padding:4px 8px;border-radius:999px">{{ $authority }}: {{ $alignment }}</span>@endforeach</div>
    </div>
    @endif

    {{-- Status message --}}
    <div id="notesStatus" style="display:none;margin-bottom:16px;padding:14px 18px;border-radius:var(--radius);font-size:14px"></div>

    {{-- Notes content --}}
    <div id="notesContent" style="background:white;border:1px solid var(--border);border-radius:var(--radius);padding:28px 32px;line-height:1.8;color:#1e293b">
        @if($lessonPlan->lesson_notes)
            {!! $lessonPlan->lesson_notes !!}
        @else
            <div style="text-align:center;padding:40px;color:var(--slate-light)">
                No notes generated yet. Click <strong>Generate Student Notes</strong> on the lesson plan page.
            </div>
        @endif
    </div>
</div>

<style>
/* Notes content styling */
#notesContent h1, #notesContent h2 { color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin: 24px 0 12px; }
#notesContent h2 { font-size: 17px; }
#notesContent h3 { font-size: 15px; color: #4338ca; margin: 18px 0 8px; }
#notesContent p  { margin: 8px 0; }
#notesContent ul, #notesContent ol { padding-left: 22px; margin: 8px 0; }
#notesContent li { margin: 4px 0; }
#notesContent table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 13px; }
#notesContent th { background: #1e40af; color: white; padding: 8px 12px; text-align: left; }
#notesContent td { padding: 7px 12px; border: 1px solid #e2e8f0; }
#notesContent tr:nth-child(even) td { background: #f8fafc; }
#notesContent svg { max-width: 100%; display: block; margin: 16px auto; }
#notesContent figure { margin: 20px 0; text-align: center; }
#notesContent figcaption { font-size: 12px; color: #64748b; font-style: italic; margin-top: 6px; }
#notesContent blockquote { border-left: 4px solid #4338ca; padding-left: 14px; color: #475569; margin: 12px 0; }
#notesContent .exam-question { background: #FFF7ED; border-left: 4px solid #EA580C; padding: 12px 16px; margin: 10px 0; border-radius: 4px; }
#notesContent .key-points { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 16px 20px; border-radius: 8px; margin: 16px 0; }
</style>

@push('scripts')
<script>
function generateStudentNotes() {
    const btn = document.getElementById('regenBtn');
    const status = document.getElementById('notesStatus');
    btn.disabled = true;
    btn.textContent = '⏳ Generating...';

    fetch('{{ route('lesson-planner.generate-notes', $lessonPlan) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('notesContent').innerHTML = data.notes;
            status.style.display = 'block';
            status.style.cssText = 'display:block;padding:12px 16px;border-radius:8px;background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;font-size:14px;margin-bottom:16px';
            status.textContent = '✅ Notes regenerated successfully.';
        } else {
            status.style.cssText = 'display:block;padding:12px 16px;border-radius:8px;background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;font-size:14px;margin-bottom:16px';
            status.textContent = '⚠ ' + data.message;
        }
        btn.disabled = false;
        btn.textContent = '✨ Regenerate Notes';
    });
}
function regenerateMissing(){const b=document.getElementById('missingBtn');b.disabled=true;b.textContent='Generating missing sections...';fetch('{{ route('lesson-planner.regenerate-missing',$lessonPlan) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{if(d.success){location.reload();}else{b.disabled=false;b.textContent=d.message||'Try again';}});}
</script>
@endpush
@endsection
