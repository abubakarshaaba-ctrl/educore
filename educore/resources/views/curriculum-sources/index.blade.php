@extends('layouts.super')
@section('title','Platform Academic Content Repository')
@section('content')
<div class="repo-page"><header class="repo-hero"><div><span>EDUCORE ACADEMIC INTELLIGENCE</span><h1>Academic Content Repository</h1><p>One secure, platform-owned source library for every EduCore school. Prepared lesson notes retain their subject, class, term and week context when imported.</p></div><a href="#upload" class="btn btn-primary">Import archive</a></header>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif @if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif
<section class="metrics">@foreach(['resources'=>'Total Resources','indexed'=>'Indexed','review'=>'Needs Review','failed'=>'Failed Extraction','imports'=>'Imports'] as $k=>$label)<article><strong>{{$analytics[$k]}}</strong><span>{{$label}}</span></article>@endforeach</section>
<div class="repo-grid"><main><section class="panel"><div class="panel-head"><div><h2>Repository Search</h2><p>Search titles and indexed content.</p></div><form><input name="search" value="{{request('search')}}" placeholder="Search repository"><button class="btn btn-secondary">Search</button></form></div><div class="resource-list">@forelse($sources as $s)<article class="resource"><div><small>{{strtoupper(pathinfo($s->original_filename??'',PATHINFO_EXTENSION)?:$s->source_type)}} · {{$s->authority}}</small><h3>{{$s->title}}</h3><p>{{$s->original_filename}} · {{$s->fragments_count}} chunks · {{$s->extraction_status}}</p></div><div class="actions"><span class="status {{$s->is_active?'active':''}}">{{$s->is_active?'Active':($s->needs_review?'Needs review':'Inactive')}}</span>@if(!$s->is_active&&$s->extraction_status==='extracted')<form method="POST" action="{{route('super.curriculum-sources.activate',$s)}}">@csrf<button class="btn btn-primary">Activate</button></form>@elseif($s->is_active)<form method="POST" action="{{route('super.curriculum-sources.deactivate',$s)}}">@csrf<button class="btn btn-secondary">Deactivate</button></form>@endif</div></article>@empty<p class="empty">No repository resources yet.</p>@endforelse</div>{{$sources->links()}}</section>
<section class="panel"><h2>Import History</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>Filename</th><th>Format</th><th>Imported</th><th>Duplicates</th><th>Failed</th><th>Review</th><th>Status</th></tr></thead><tbody>@foreach($imports as $i)<tr><td>#{{$i->id}}</td><td>{{$i->filename}}</td><td>{{strtoupper($i->format)}}</td><td>{{$i->imported}}</td><td>{{$i->duplicates}}</td><td>{{$i->failed}}</td><td>{{$i->needs_review}}</td><td>{{$i->status}}</td></tr>@endforeach</tbody></table></div></section></main>
<aside><section class="panel" id="upload"><h2>Upload Resources</h2><p>DOCX, PDF, XLSX or ZIP. Up to 20 files, 50 MB each.</p><form method="POST" enctype="multipart/form-data" action="{{route('super.curriculum-sources.store')}}">@csrf<div class="drop"><b>Drag files here or click to browse</b><span>DOCX · PDF · XLSX/XLS · ZIP</span><input name="source_files[]" type="file" multiple accept=".docx,.pdf,.xlsx,.xls,.zip" required></div>@foreach([['title','Optional title'],['topic','Topic'],['subtopic','Subtopics'],['version','Version / year']] as $f)<label>{{$f[1]}}</label><input name="{{$f[0]}}">@endforeach<label>Authority</label><select name="authority"><option>NERDC</option><option>WAEC</option><option>NECO</option><option>JAMB</option><option>TEXTBOOK</option><option selected>OTHER</option></select><label>Resource Type</label><select name="source_type"><option value="curriculum_document">Curriculum document</option><option value="lesson_note">Lesson note</option><option value="teacher_guide">Teacher guide</option><option value="approved_textbook">Approved textbook</option></select><label>Subject</label><select name="subject_id"><option value="">Unmapped</option>@foreach($subjects as $s)<option value="{{$s->id}}">{{$s->name}}</option>@endforeach</select><label>Curriculum Level</label><select name="curriculum_level_id"><option value="">Unmapped</option>@foreach($classLevels as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select><label>Usage Rights</label><select name="rights_status"><option value="institution_authorised">Institution authorised</option><option value="licensed">Licensed</option><option value="public_official">Public official</option></select><input type="hidden" name="column_mapping_json" value='{"title":"Title","topic":"Topic","subtopic":"Subtopic","content":"Content","resource_type":"Resource Type","source_year":"Source Year"}'><button class="btn btn-primary full">Process Upload</button></form></section>
<section class="panel"><h2>Curriculum Mapping</h2><form method="POST" action="{{route('super.curriculum-sources.topics.store')}}">@csrf<label>Subject</label><select name="subject_id" required>@foreach($subjects as $s)<option value="{{$s->id}}">{{$s->name}}</option>@endforeach</select><label>Curriculum Level</label><select name="curriculum_level_id" required>@foreach($classLevels as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select><label>Canonical Topic</label><input name="topic" required><label>Subtopics</label><textarea name="subtopics_text"></textarea><label>Keywords</label><textarea name="keywords_text"></textarea><button class="btn btn-secondary full">Add Topic</button></form></section></aside></div></div>
<style>.repo-page{padding:24px;max-width:1500px;margin:auto}.repo-hero{padding:28px;background:linear-gradient(135deg,#071b3b,#123d73);color:white;border-radius:20px;display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:18px}.repo-hero span{font-size:11px;letter-spacing:.15em;color:#f6b72b}.repo-hero h1{font-size:28px;margin:6px 0}.repo-hero p{max-width:760px;color:#d7e4f4}.metrics{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin:18px 0}.metrics article,.panel{background:#fff;border:1px solid #dfe7f1;border-radius:15px;padding:18px}.metrics strong{display:block;font-size:24px;color:#0d2c57}.metrics span{font-size:12px;color:#66758b}.repo-grid{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:18px}.panel{margin-bottom:18px}.panel h2{font-size:17px;margin-bottom:5px}.panel p{font-size:13px;color:#66758b}.panel-head{display:flex;justify-content:space-between;gap:14px}.panel-head form{display:flex;gap:7px}.panel input,.panel select,.panel textarea{width:100%;border:1px solid #cad5e2;border-radius:9px;padding:10px;margin:5px 0 10px}.panel label{font-size:12px;font-weight:700}.drop{position:relative;border:2px dashed #8fa8c5;border-radius:13px;text-align:center;padding:28px 12px;margin:14px 0}.drop span{display:block;color:#66758b;margin-top:5px}.drop input{position:absolute;inset:0;opacity:0;cursor:pointer}.resource{display:flex;justify-content:space-between;gap:12px;padding:15px 0;border-top:1px solid #edf1f6}.resource small{color:#527096}.resource h3{font-size:15px;margin:3px 0}.resource p{margin:0}.actions{display:flex;align-items:center;gap:7px}.status{font-size:11px;background:#fff3cd;color:#875b00;padding:5px 8px;border-radius:99px}.status.active{background:#dcfce7;color:#166534}.full{width:100%}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #e8edf3;text-align:left;font-size:12px}@media(max-width:1050px){.metrics{grid-template-columns:repeat(3,1fr)}.repo-grid{grid-template-columns:1fr}}@media(max-width:640px){.repo-page{padding:12px}.repo-hero,.panel-head,.resource{display:block}.repo-hero .btn,.panel-head form{margin-top:12px}.metrics{grid-template-columns:repeat(2,1fr)}.actions{margin-top:10px;flex-wrap:wrap}}</style>
@push('scripts')
<script>
(() => {
    const form = document.querySelector('#upload form');
    const fileInput = form?.querySelector('input[name="source_files[]"]');
    if (!form || !fileInput || !window.XMLHttpRequest) return;

    fileInput.removeAttribute('multiple');
    fileInput.setAttribute('accept', '.zip,.docx,.doc,.pdf,.xlsx,.xls');
    const intro = document.querySelector('#upload > p');
    if (intro) intro.textContent = 'Upload one prepared lesson-note archive. Large ZIP uploads continue on this page while progress, speed and processing status are displayed.';

    const panel = document.createElement('section');
    panel.className = 'repo-upload-progress';
    panel.hidden = true;
    panel.setAttribute('aria-live', 'polite');
    panel.innerHTML = `
        <div class="rup-head">
            <div><span class="rup-kicker">UPLOAD IN PROGRESS</span><strong class="rup-name">Preparing archive…</strong></div>
            <strong class="rup-percent">0%</strong>
        </div>
        <div class="rup-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i></i></div>
        <div class="rup-meta"><span class="rup-size">0 MB of 0 MB</span><span class="rup-speed">Calculating speed…</span><span class="rup-time">Estimating time…</span></div>
        <div class="rup-state"><span class="rup-pulse"></span><span>Uploading securely to EduCore…</span></div>
        <button type="button" class="rup-cancel">Cancel upload</button>`;
    form.insertBefore(panel, form.firstChild);

    const submit = form.querySelector('button[type="submit"], button:not([type])');
    const track = panel.querySelector('.rup-track');
    const bar = track.querySelector('i');
    const percent = panel.querySelector('.rup-percent');
    const size = panel.querySelector('.rup-size');
    const speed = panel.querySelector('.rup-speed');
    const time = panel.querySelector('.rup-time');
    const state = panel.querySelector('.rup-state span:last-child');
    const cancel = panel.querySelector('.rup-cancel');
    const name = panel.querySelector('.rup-name');
    let request;

    const bytes = value => value >= 1073741824 ? `${(value / 1073741824).toFixed(2)} GB` : `${(value / 1048576).toFixed(value >= 104857600 ? 0 : 1)} MB`;
    const duration = seconds => {
        if (!Number.isFinite(seconds) || seconds < 0) return 'Estimating time…';
        if (seconds < 60) return `${Math.max(1, Math.ceil(seconds))} sec remaining`;
        return `${Math.ceil(seconds / 60)} min remaining`;
    };
    const setState = (message, kind = '') => {
        panel.classList.remove('is-error', 'is-complete', 'is-processing');
        if (kind) panel.classList.add(kind);
        state.textContent = message;
    };

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;
        const dropTitle = form.querySelector('.drop b');
        if (dropTitle) dropTitle.textContent = file.name;
        const dropHint = form.querySelector('.drop span');
        if (dropHint) dropHint.textContent = `${bytes(file.size)} · Ready to upload`;
    });

    form.addEventListener('submit', event => {
        const file = fileInput.files[0];
        if (!file) return;
        event.preventDefault();

        panel.hidden = false;
        name.textContent = file.name;
        submit.disabled = true;
        fileInput.disabled = false;
        setState('Uploading securely to EduCore…');
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });

        const started = performance.now();
        request = new XMLHttpRequest();
        request.open(form.method || 'POST', form.action, true);
        request.setRequestHeader('Accept', 'text/html,application/xhtml+xml');

        request.upload.addEventListener('progress', upload => {
            if (!upload.lengthComputable) return;
            const value = Math.min(100, Math.round((upload.loaded / upload.total) * 100));
            const elapsed = Math.max((performance.now() - started) / 1000, .1);
            const rate = upload.loaded / elapsed;
            bar.style.width = `${value}%`;
            percent.textContent = `${value}%`;
            track.setAttribute('aria-valuenow', value);
            size.textContent = `${bytes(upload.loaded)} of ${bytes(upload.total)}`;
            speed.textContent = `${bytes(rate)}/s`;
            time.textContent = duration((upload.total - upload.loaded) / rate);
            if (value === 100) {
                cancel.hidden = true;
                setState('Upload complete. EduCore is extracting and indexing the lesson notes…', 'is-processing');
                time.textContent = 'Processing archive';
            }
        });

        request.addEventListener('load', () => {
            if (request.status >= 200 && request.status < 400) {
                bar.style.width = '100%'; percent.textContent = '100%';
                setState('Archive accepted. Refreshing the repository summary…', 'is-complete');
                window.location.assign(request.responseURL || window.location.href);
            } else {
                submit.disabled = false; cancel.hidden = true;
                setState(request.status === 413 ? 'The server rejected the archive size. Confirm the hosting upload limit and try again.' : 'Upload could not be completed. Check the archive and try again.', 'is-error');
            }
        });
        request.addEventListener('error', () => { submit.disabled = false; cancel.hidden = true; setState('Network connection lost. Your original archive is safe; select it and retry.', 'is-error'); });
        request.addEventListener('abort', () => { submit.disabled = false; cancel.hidden = true; setState('Upload cancelled. No repository resources were changed.', 'is-error'); });
        cancel.hidden = false;
        cancel.onclick = () => request?.abort();
        request.send(new FormData(form));
    });
})();
</script>
@endpush

<style>
.repo-upload-progress{margin:0 0 18px;padding:18px;border:1px solid #c7d8ec;border-radius:14px;background:linear-gradient(145deg,#f8fbff,#eef5fd);box-shadow:0 10px 28px rgba(15,50,91,.08)}
.rup-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}.rup-head>div{min-width:0}.rup-kicker{display:block;color:#2463a7;font-size:10px;font-weight:800;letter-spacing:.12em;margin-bottom:5px}.rup-name{display:block;color:#102a4c;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.rup-percent{color:#123f75;font-size:20px}
.rup-track{height:10px;margin:14px 0 10px;border-radius:999px;background:#dbe7f4;overflow:hidden}.rup-track i{display:block;width:0;height:100%;border-radius:inherit;background:linear-gradient(90deg,#1d60aa,#4b8fd8,#e8b33c);transition:width .2s ease}
.rup-meta{display:grid;grid-template-columns:1.3fr 1fr 1fr;gap:8px;color:#5d7088;font-size:11px}.rup-state{display:flex;align-items:center;gap:8px;margin-top:13px;color:#214a77;font-size:12px;font-weight:700}.rup-pulse{width:8px;height:8px;border-radius:50%;background:#2e75bd;box-shadow:0 0 0 0 rgba(46,117,189,.45);animation:rupPulse 1.5s infinite}.rup-cancel{margin-top:13px;border:0;background:transparent;color:#a33838;font-size:11px;font-weight:800;cursor:pointer;padding:0}.repo-upload-progress.is-error{border-color:#fecaca;background:#fff7f7}.repo-upload-progress.is-error .rup-state{color:#991b1b}.repo-upload-progress.is-error .rup-pulse{background:#dc2626;animation:none}.repo-upload-progress.is-complete{border-color:#bbdfca;background:#f4fcf7}.repo-upload-progress.is-complete .rup-state{color:#166534}.repo-upload-progress.is-complete .rup-pulse{background:#22c55e;animation:none}.repo-upload-progress.is-processing .rup-track i{animation:rupShimmer 1.4s linear infinite;background-size:200% 100%}
@keyframes rupPulse{70%{box-shadow:0 0 0 7px rgba(46,117,189,0)}100%{box-shadow:0 0 0 0 rgba(46,117,189,0)}}@keyframes rupShimmer{to{background-position:-200% 0}}
@media(max-width:560px){.repo-upload-progress{padding:15px}.rup-meta{grid-template-columns:1fr 1fr}.rup-time{grid-column:1/-1}.rup-name{max-width:220px}}
</style>
@endsection

