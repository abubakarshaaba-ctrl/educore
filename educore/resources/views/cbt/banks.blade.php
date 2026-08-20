@extends('layouts.app')
@section('title', 'CBT Question Banks')
@section('page-title', 'CBT Exams')

@push('styles')
<style>
.cbt-shell{max-width:1320px;margin:0 auto}.page-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:18px}.page-tab{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:650;border:1px solid var(--border);background:white;color:var(--slate);text-decoration:none}.page-tab.active,.page-tab:hover{background:var(--indigo);border-color:var(--indigo);color:white}
.studio-head{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:24px;padding:24px 26px;margin-bottom:16px;border-radius:16px;background:linear-gradient(125deg,#071E45,#0B326B);color:white;box-shadow:0 14px 32px rgba(7,30,69,.13)}.studio-head:after{content:"";position:absolute;width:260px;height:260px;border-radius:50%;right:-80px;top:-140px;border:50px solid rgba(215,154,33,.12)}.studio-head>div,.studio-head>a{position:relative;z-index:1}.eyebrow{font-size:9px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#F2C35B;margin-bottom:7px}.studio-head h1{font-size:24px;line-height:1.15;margin:0 0 7px;color:white}.studio-head p{font-size:12px;line-height:1.55;color:rgba(255,255,255,.72);max-width:620px}.head-action{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border-radius:9px;background:#F2B233;color:#071E45;text-decoration:none;font-size:12px;font-weight:800;white-space:nowrap}
.metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}.metric{background:white;border:1px solid var(--border);border-radius:12px;padding:14px 16px}.metric strong{display:block;font-size:20px;color:var(--midnight);line-height:1}.metric span{display:block;font-size:10px;color:var(--slate);margin-top:6px}.grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start}.card{background:white;border:1px solid var(--border);border-radius:14px;box-shadow:0 2px 8px rgba(15,23,42,.04);overflow:hidden}.card-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 18px;border-bottom:1px solid var(--border)}.card-head h2{font-size:14px;color:var(--midnight);margin:0}.card-head span{font-size:10px;color:var(--slate)}.bank-list{padding:8px}.bank-row{display:grid;grid-template-columns:minmax(190px,1.4fr) .8fr .55fr auto;gap:14px;align-items:center;padding:14px 12px;border-bottom:1px solid #EEF2F7}.bank-row:last-child{border-bottom:0}.bank-main{min-width:0}.bank-main strong{display:block;font-size:13px;color:var(--midnight);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.bank-main span,.bank-cell span{display:block;font-size:10px;color:var(--slate);margin-top:4px}.bank-cell b{font-size:12px;color:var(--midnight)}.status{display:inline-flex!important;width:max-content;padding:3px 7px;border-radius:20px;background:#ECFDF5;color:#047857!important;font-size:9px!important;font-weight:750;margin-top:6px!important}.actions{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 10px;font:650 11px inherit;border-radius:7px;border:1px solid var(--border);background:#fff;color:var(--midnight);text-decoration:none;cursor:pointer}.btn-primary{background:var(--indigo);border-color:var(--indigo);color:white}.btn-soft{background:#F8FAFC}.btn-danger{color:#B91C1C;background:#FEF2F2;border-color:#FECACA}.empty{text-align:center;padding:44px 20px}.empty-icon{width:58px;height:58px;margin:0 auto 12px;border-radius:18px;display:grid;place-items:center;background:#EEF4FC;color:#174F9E;font-size:25px}.empty strong{display:block;color:var(--midnight);font-size:14px}.empty p{font-size:11px;color:var(--slate);margin:5px 0 14px}.form-body{padding:18px}.form-group{margin-bottom:13px}.form-label{display:block;font-size:10px;font-weight:750;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}.form-control{width:100%;padding:9px 11px;font:inherit;font-size:12px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}.form-control:focus{background:white;border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,.08)}.create-btn{width:100%;padding:10px}.alert{border-radius:9px;padding:11px 14px;font-size:12px;margin-bottom:14px}.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#047857}.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C}.workflow{margin-top:12px;padding:13px;border-radius:10px;background:#F8FAFC;border:1px solid var(--border)}.workflow strong{display:block;font-size:10px;color:var(--midnight);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em}.workflow ol{margin:0;padding-left:17px}.workflow li{font-size:10px;color:var(--slate);padding:3px 0;line-height:1.4}
@media(max-width:1100px){.grid{grid-template-columns:1fr}.bank-row{grid-template-columns:minmax(180px,1fr) .75fr .5fr auto}}@media(max-width:760px){.studio-head{align-items:flex-start;flex-direction:column}.metrics{grid-template-columns:1fr 1fr}.bank-row{grid-template-columns:1fr 1fr}.actions{grid-column:1/-1;justify-content:flex-start}.bank-cell:nth-child(3){text-align:right}}@media(max-width:480px){.metrics{grid-template-columns:1fr}.studio-head{padding:20px}.studio-head h1{font-size:20px}.bank-row{grid-template-columns:1fr}.bank-cell:nth-child(3){text-align:left}.actions{grid-column:auto}}
</style>
@endpush

@section('content')
<div class="cbt-shell">
    <div class="page-tabs">
        <a href="{{ route('cbt.banks') }}" class="page-tab active">Question Banks</a>
        <a href="{{ route('cbt.exams') }}" class="page-tab">Exams & Sections</a>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())<a href="{{ route('cbt.retakes') }}" class="page-tab">Retake Control</a><a href="{{ route('cbt.lan') }}" class="page-tab">LAN Mode</a>@endif
    </div>

    <section class="studio-head">
        <div><div class="eyebrow">EduCore Assessment Studio</div><h1>Question Bank Workspace</h1><p>Build reusable objective and hierarchical theory questions, then arrange them into any number of examination sections.</p></div>
        <a class="head-action" href="{{ route('cbt.exams') }}">Create structured exam →</a>
    </section>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    <div class="metrics">
        <div class="metric"><strong>{{ $banks->count() }}</strong><span>Question banks</span></div>
        <div class="metric"><strong>{{ $banks->sum('questions_count') }}</strong><span>Reusable questions</span></div>
        <div class="metric"><strong>{{ $banks->sum('exams_count') }}</strong><span>Linked examinations</span></div>
    </div>

    <div class="grid">
        <section class="card">
            <div class="card-head"><div><h2>Question banks</h2><span>Objective, theory and nested question branches</span></div></div>
            @forelse($banks as $bank)
            @if($loop->first)<div class="bank-list">@endif
                <article class="bank-row">
                    <div class="bank-main"><strong>{{ $bank->name }}</strong><span>{{ $bank->subject->name }} · {{ $bank->classLevel->name }}</span><span class="status">Hierarchy ready</span></div>
                    <div class="bank-cell"><b>{{ $bank->questions_count }}</b><span>Questions</span></div>
                    <div class="bank-cell"><b>{{ $bank->exams_count }}</b><span>Exams</span></div>
                    <div class="actions">
                        <a href="{{ route('cbt.questions', $bank) }}" class="btn btn-primary">Open builder</a>
                        <a href="{{ route('cbt.bulk-upload', $bank) }}" class="btn btn-soft">Import</a>
                        <a href="{{ route('cbt.exams', ['bank' => $bank->id]) }}" class="btn btn-soft">Use in exam</a>
                        <a href="{{ route('cbt.banks.edit', $bank) }}" class="btn btn-soft">Edit</a>
                        <form method="POST" action="{{ route('cbt.banks.destroy', $bank) }}" onsubmit="return confirm('Delete this bank, its questions and linked examinations? This cannot be undone.')">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form>
                    </div>
                </article>
            @if($loop->last)</div>@endif
            @empty
                <div class="empty"><div class="empty-icon">＋</div><strong>No question banks yet</strong><p>Create a subject bank to begin building questions.</p></div>
            @endforelse
        </section>

        <aside class="card">
            <div class="card-head"><div><h2>New question bank</h2><span>Create a reusable content pool</span></div></div>
            <div class="form-body">
                <form method="POST" action="{{ route('cbt.banks.store') }}">@csrf
                    <div class="form-group"><label class="form-label">Bank name</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. SS 3 Biology 2026" required></div>
                    <div class="form-group"><label class="form-label">Subject</label><select name="subject_id" class="form-control" required><option value="">Select subject</option>@foreach($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">Class level</label><select name="class_level_id" class="form-control" required><option value="">Select class</option>@foreach($classLevels as $cl)<option value="{{ $cl->id }}" @selected(old('class_level_id') == $cl->id)>{{ $cl->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Optional description">{{ old('description') }}</textarea></div>
                    <button type="submit" class="btn btn-primary create-btn">Create question bank</button>
                </form>
                <div class="workflow"><strong>Current workflow</strong><ol><li>Create the reusable question bank.</li><li>Build objective or nested theory questions.</li><li>Create an exam and configure unlimited sections.</li><li>Assign questions, validate marks and publish.</li></ol></div>
            </div>
        </aside>
    </div>
</div>
@endsection
