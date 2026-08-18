@extends('layouts.super')

@section('title', 'Import Academic Content')

@section('content')
<div class="import-page">
    <header class="import-header">
        <div>
            <span class="eyebrow">NEW IMPORT</span>
            <h1>Import archive</h1>
            <p>Upload lesson notes or curriculum resources.</p>
        </div>
    </header>

    @include('curriculum-sources._navigation')

    @if($errors->any())
        <div class="notice notice-error">{{ $errors->first() }}</div>
    @endif

    <form id="archiveImportForm" method="POST" enctype="multipart/form-data" action="{{ route('super.curriculum-sources.store') }}">
        @csrf
        <div class="import-layout">
            <main class="import-main">
                <section class="import-panel upload-panel">
                    <div class="panel-heading">
                        <span class="step">01</span>
                        <div><h2>Select file</h2><p>ZIP, DOCX, DOC, PDF, XLSX or XLS</p></div>
                    </div>

                    <label class="archive-drop" id="archiveDrop" for="archiveFile">
                        <input id="archiveFile" name="source_files[]" type="file" accept=".zip,.docx,.doc,.pdf,.xlsx,.xls" required>
                        <span class="drop-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg>
                        </span>
                        <strong id="dropTitle">Drop a file here</strong>
                        <span id="dropHint">or click to browse</span>
                        <small>One file per import</small>
                    </label>

                    <div class="selected-file" id="selectedFile" hidden>
                        <span class="selected-type" id="selectedType">ZIP</span>
                        <div><strong id="selectedName"></strong><span id="selectedSize"></span></div>
                        <button type="button" id="removeFile" aria-label="Remove selected file">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>

                    <section class="upload-progress" id="uploadProgress" hidden aria-live="polite">
                        <div class="progress-head">
                            <div><strong id="progressName">Uploading</strong><span id="progressState">Preparing file...</span></div>
                            <strong id="progressPercent">0%</strong>
                        </div>
                        <div class="progress-track" id="progressTrack" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i></i></div>
                        <div class="progress-meta"><span id="progressSize">0 MB of 0 MB</span><span id="progressSpeed">Calculating...</span><span id="progressTime">Estimating...</span></div>
                        <button type="button" id="cancelUpload">Cancel</button>
                    </section>
                </section>

                <section class="import-panel details-panel">
                    <div class="panel-heading">
                        <span class="step">02</span>
                        <div><h2>Archive details</h2><p>Optional fields improve matching.</p></div>
                    </div>

                    <div class="form-grid">
                        <div class="field field-wide">
                            <label for="importTitle">Title <span>Optional</span></label>
                            <input id="importTitle" name="title" value="{{ old('title') }}" placeholder="Archive title">
                        </div>

                        <div class="field">
                            <label for="importAuthority">Authority</label>
                            <select id="importAuthority" name="authority" required>
                                @foreach(['OTHER'=>'Other', 'NERDC'=>'NERDC', 'WAEC'=>'WAEC', 'NECO'=>'NECO', 'JAMB'=>'JAMB', 'TEXTBOOK'=>'Textbook'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('authority', 'OTHER') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="importType">Resource type</label>
                            <select id="importType" name="source_type" required>
                                @foreach(['lesson_note'=>'Lesson note', 'curriculum_document'=>'Curriculum document', 'teacher_guide'=>'Teacher guide', 'approved_textbook'=>'Approved textbook', 'assessment_syllabus'=>'Assessment syllabus', 'school_scheme'=>'School scheme'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('source_type', 'lesson_note') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="importSubject">Subject <span>Optional</span></label>
                            <select id="importSubject" name="subject_id">
                                <option value="">Auto-detect</option>
                                @foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>@endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="importClass">Class <span>Optional</span></label>
                            <select id="importClass" name="curriculum_level_id">
                                <option value="">Auto-detect</option>
                                @foreach($classLevels as $classLevel)<option value="{{ $classLevel->id }}" @selected((string) old('curriculum_level_id') === (string) $classLevel->id)>{{ $classLevel->name }}</option>@endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="importTopic">Topic <span>Optional</span></label>
                            <input id="importTopic" name="topic" value="{{ old('topic') }}" placeholder="Topic">
                        </div>

                        <div class="field">
                            <label for="importSubtopic">Subtopics <span>Optional</span></label>
                            <input id="importSubtopic" name="subtopic" value="{{ old('subtopic') }}" placeholder="Subtopics">
                        </div>

                        <div class="field">
                            <label for="importVersion">Version <span>Optional</span></label>
                            <input id="importVersion" name="version" value="{{ old('version') }}" placeholder="Year or edition">
                        </div>

                        <div class="field">
                            <label for="importRights">Usage rights</label>
                            <select id="importRights" name="rights_status" required>
                                <option value="institution_authorised" @selected(old('rights_status', 'institution_authorised') === 'institution_authorised')>Institution authorised</option>
                                <option value="licensed" @selected(old('rights_status') === 'licensed')>Licensed</option>
                                <option value="public_official" @selected(old('rights_status') === 'public_official')>Public official</option>
                            </select>
                        </div>
                    </div>
                </section>
            </main>

            <aside class="import-side">
                <section class="summary-card">
                    <span class="summary-kicker">IMPORT</span>
                    <h2>Ready to process</h2>
                    <ul>
                        <li><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>Files are indexed automatically</li>
                        <li><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>Duplicates are skipped</li>
                        <li><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>Resources remain inactive for review</li>
                    </ul>
                    <input type="hidden" name="column_mapping_json" value='{"title":"Title","topic":"Topic","subtopic":"Subtopic","content":"Content","resource_type":"Resource Type","source_year":"Source Year"}'>
                    <button type="submit" class="button button-primary button-full" id="submitImport" disabled>
                        Import archive
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <a href="{{ route('super.curriculum-sources.index') }}" class="cancel-link">Cancel</a>
                </section>
            </aside>
        </div>
    </form>
</div>

<style>
:root{--import-blue:#1756a9;--import-navy:#09244a;--import-ink:#12233d;--import-muted:#6d7b90;--import-line:#dfe7f0;--import-soft:#f5f8fc}
.import-page{max-width:1280px;margin:0 auto;padding:24px 30px 50px;color:var(--import-ink)}
.import-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.eyebrow{display:block;margin-bottom:6px;color:var(--import-blue);font-size:10px;font-weight:850;letter-spacing:.14em}.import-header h1{margin:0;color:#071b38;font-size:30px;line-height:1.15;letter-spacing:-.035em}.import-header p{margin:7px 0 0;color:var(--import-muted);font-size:13px}
.button{min-height:42px;padding:0 17px;border:0;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:13px;font-weight:750;text-decoration:none;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease,background .15s ease}.button:hover{transform:translateY(-1px)}.button svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.button-primary{color:#fff;background:var(--import-blue);box-shadow:0 8px 20px rgba(23,86,169,.2)}.button-primary:hover{color:#fff;background:#10498f}.button-primary:disabled{box-shadow:none;opacity:.48;cursor:not-allowed;transform:none}.button-quiet{border:1px solid #d8e1ec;color:#344b68;background:#fff}.button-full{width:100%}.notice{margin:0 0 18px;padding:12px 15px;border:1px solid;border-radius:10px;font-size:13px;font-weight:650}.notice-error{color:#9c2f2f;background:#fff5f5;border-color:#f1cccc}
.import-layout{display:grid;grid-template-columns:minmax(0,1fr) 285px;align-items:start;gap:18px}.import-panel,.summary-card{background:#fff;border:1px solid var(--import-line);border-radius:15px;box-shadow:0 5px 20px rgba(15,39,72,.035)}.import-panel{overflow:hidden;margin-bottom:18px}.panel-heading{min-height:70px;padding:15px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--import-line)}.step{width:34px;height:34px;display:grid;place-items:center;border-radius:9px;color:#2b66a9;background:#eef4fb;font-size:10px;font-weight:850}.panel-heading h2{margin:0;color:#102744;font-size:16px}.panel-heading p{margin:4px 0 0;color:var(--import-muted);font-size:11px}
.archive-drop{min-height:245px;margin:18px;padding:30px 20px;display:flex;align-items:center;justify-content:center;flex-direction:column;border:1.5px dashed #9eb4cd;border-radius:13px;background:linear-gradient(145deg,#fbfdff,#f4f8fd);text-align:center;cursor:pointer;transition:border .15s ease,background .15s ease,transform .15s ease}.archive-drop:hover,.archive-drop.is-dragging{border-color:#3473b9;background:#f0f6fd;transform:translateY(-1px)}.archive-drop input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}.drop-icon{width:52px;height:52px;margin-bottom:14px;display:grid;place-items:center;border-radius:15px;color:#2866aa;background:#e6f0fb}.drop-icon svg{width:25px;height:25px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.archive-drop strong{color:#173455;font-size:15px}.archive-drop>span:not(.drop-icon){margin-top:5px;color:#6d7e94;font-size:12px}.archive-drop small{margin-top:13px;padding:5px 9px;border-radius:999px;color:#65758a;background:#eaf0f7;font-size:9px;font-weight:750;letter-spacing:.04em;text-transform:uppercase}
.selected-file{margin:0 18px 18px;padding:12px 13px;display:flex;align-items:center;gap:11px;border:1px solid #d7e3ef;border-radius:11px;background:#f8fbfe}.selected-type{width:39px;height:39px;display:grid;place-items:center;flex:0 0 39px;border-radius:9px;color:#2864a6;background:#e5effa;font-size:9px;font-weight:850}.selected-file div{min-width:0;flex:1}.selected-file strong,.selected-file div span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.selected-file strong{color:#203955;font-size:12px}.selected-file div span{margin-top:4px;color:#7b899b;font-size:10px}.selected-file button{width:30px;height:30px;padding:7px;border:0;border-radius:8px;color:#718096;background:transparent;cursor:pointer}.selected-file button:hover{color:#a82e2e;background:#fff0f0}.selected-file svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round}
.upload-progress{margin:0 18px 18px;padding:16px;border:1px solid #cbdced;border-radius:12px;background:#f3f8fd}.progress-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.progress-head>div{min-width:0}.progress-head strong,.progress-head span{display:block}.progress-head>div strong{overflow:hidden;color:#163658;font-size:12px;text-overflow:ellipsis;white-space:nowrap}.progress-head>div span{margin-top:4px;color:#60758f;font-size:10px}.progress-head>strong{color:#174e8b;font-size:18px}.progress-track{height:8px;margin:13px 0 9px;overflow:hidden;border-radius:999px;background:#dbe7f3}.progress-track i{display:block;width:0;height:100%;border-radius:inherit;background:linear-gradient(90deg,#1c5ca4,#4a91dc,#e1ae2c);transition:width .2s ease}.progress-meta{display:grid;grid-template-columns:1.3fr 1fr 1fr;gap:8px;color:#687c93;font-size:9px}.upload-progress>button{margin-top:12px;padding:0;border:0;color:#a33939;background:transparent;font-size:10px;font-weight:800;cursor:pointer}.upload-progress.is-processing .progress-track i{background-size:200% 100%;animation:progressShimmer 1.3s linear infinite}.upload-progress.is-error{border-color:#efcaca;background:#fff6f6}.upload-progress.is-error .progress-head>div span{color:#a52d2d}
.form-grid{padding:18px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field-wide{grid-column:1/-1}.field label{display:block;margin-bottom:6px;color:#354a65;font-size:11px;font-weight:750}.field label span{color:#8b98a8;font-size:9px;font-weight:600}.field input,.field select{width:100%;height:42px;padding:0 11px;border:1px solid #d4deea;border-radius:9px;outline:0;color:#1c304d;background:#fff;font:inherit;font-size:12px;transition:border .15s ease,box-shadow .15s ease}.field input:focus,.field select:focus{border-color:#4f86c4;box-shadow:0 0 0 3px rgba(53,112,180,.1)}
.summary-card{position:sticky;top:18px;padding:20px}.summary-kicker{color:var(--import-blue);font-size:9px;font-weight:850;letter-spacing:.13em}.summary-card h2{margin:6px 0 16px;color:#102744;font-size:17px}.summary-card ul{margin:0 0 20px;padding:0;list-style:none}.summary-card li{padding:10px 0;display:flex;align-items:flex-start;gap:8px;border-bottom:1px solid #edf1f5;color:#596b82;font-size:10px;line-height:1.45}.summary-card li svg{width:15px;height:15px;flex:0 0 15px;fill:none;stroke:#24905a;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}.cancel-link{display:block;margin-top:13px;color:#6f7e90;font-size:11px;font-weight:700;text-align:center;text-decoration:none}
@keyframes progressShimmer{to{background-position:-200% 0}}
@media(max-width:980px){.import-layout{grid-template-columns:1fr}.summary-card{position:static;display:grid;grid-template-columns:1fr auto;align-items:center;column-gap:20px}.summary-card h2{margin-bottom:6px}.summary-card ul{grid-column:1;grid-row:3;margin:0}.summary-card .button{grid-column:2;grid-row:1/3;min-width:190px}.summary-card .cancel-link{grid-column:2;grid-row:3}.summary-kicker{grid-column:1}}
@media(max-width:680px){.import-page{padding:18px 14px 40px}.import-header h1{font-size:25px}.import-header p{display:none}.archive-drop{min-height:205px;margin:14px}.selected-file,.upload-progress{margin-left:14px;margin-right:14px}.form-grid{grid-template-columns:1fr;padding:15px}.field-wide{grid-column:auto}.summary-card{display:block}.summary-card .button{width:100%}.progress-meta{grid-template-columns:1fr 1fr}.progress-meta span:last-child{grid-column:1/-1}}
@media(max-width:420px){.archive-drop{padding:24px 14px}}
</style>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('archiveImportForm');
    const input = document.getElementById('archiveFile');
    const drop = document.getElementById('archiveDrop');
    const selected = document.getElementById('selectedFile');
    const submit = document.getElementById('submitImport');
    const progress = document.getElementById('uploadProgress');
    if (!form || !input || !window.XMLHttpRequest) return;

    let request;
    const formatBytes = value => value >= 1073741824
        ? `${(value / 1073741824).toFixed(2)} GB`
        : `${(value / 1048576).toFixed(value >= 104857600 ? 0 : 1)} MB`;
    const remaining = seconds => {
        if (!Number.isFinite(seconds) || seconds < 0) return 'Estimating...';
        if (seconds < 60) return `${Math.max(1, Math.ceil(seconds))} sec left`;
        return `${Math.ceil(seconds / 60)} min left`;
    };
    const showFile = file => {
        if (!file) return;
        document.getElementById('selectedName').textContent = file.name;
        document.getElementById('selectedSize').textContent = formatBytes(file.size);
        document.getElementById('selectedType').textContent = (file.name.split('.').pop() || 'FILE').toUpperCase().slice(0, 4);
        document.getElementById('dropTitle').textContent = 'File selected';
        document.getElementById('dropHint').textContent = 'Click to replace';
        selected.hidden = false;
        submit.disabled = false;
    };

    input.addEventListener('change', () => showFile(input.files[0]));
    document.getElementById('removeFile').addEventListener('click', () => {
        input.value = '';
        selected.hidden = true;
        submit.disabled = true;
        document.getElementById('dropTitle').textContent = 'Drop a file here';
        document.getElementById('dropHint').textContent = 'or click to browse';
    });
    ['dragenter', 'dragover'].forEach(name => drop.addEventListener(name, event => {
        event.preventDefault();
        drop.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach(name => drop.addEventListener(name, event => {
        event.preventDefault();
        drop.classList.remove('is-dragging');
    }));
    drop.addEventListener('drop', event => {
        if (!event.dataTransfer.files.length) return;
        input.files = event.dataTransfer.files;
        showFile(input.files[0]);
    });

    form.addEventListener('submit', event => {
        const file = input.files[0];
        if (!file) return;
        event.preventDefault();
        progress.hidden = false;
        progress.className = 'upload-progress';
        submit.disabled = true;
        document.getElementById('progressName').textContent = file.name;
        document.getElementById('progressState').textContent = 'Uploading...';
        progress.scrollIntoView({ behavior: 'smooth', block: 'center' });

        const started = performance.now();
        const bar = progress.querySelector('.progress-track i');
        const track = document.getElementById('progressTrack');
        request = new XMLHttpRequest();
        request.open(form.method || 'POST', form.action, true);
        request.setRequestHeader('Accept', 'text/html,application/xhtml+xml');
        request.upload.addEventListener('progress', upload => {
            if (!upload.lengthComputable) return;
            const value = Math.min(100, Math.round(upload.loaded / upload.total * 100));
            const elapsed = Math.max((performance.now() - started) / 1000, .1);
            const speed = upload.loaded / elapsed;
            bar.style.width = `${value}%`;
            document.getElementById('progressPercent').textContent = `${value}%`;
            document.getElementById('progressSize').textContent = `${formatBytes(upload.loaded)} of ${formatBytes(upload.total)}`;
            document.getElementById('progressSpeed').textContent = `${formatBytes(speed)}/s`;
            document.getElementById('progressTime').textContent = remaining((upload.total - upload.loaded) / speed);
            track.setAttribute('aria-valuenow', value);
            if (value === 100) {
                progress.classList.add('is-processing');
                document.getElementById('progressState').textContent = 'Extracting and indexing...';
                document.getElementById('progressTime').textContent = 'Processing';
                document.getElementById('cancelUpload').hidden = true;
            }
        });
        request.addEventListener('load', () => {
            if (request.status >= 200 && request.status < 400) {
                document.getElementById('progressState').textContent = 'Import complete';
                window.location.assign(request.responseURL || `{{ route('super.curriculum-sources.index') }}`);
                return;
            }
            progress.classList.add('is-error');
            submit.disabled = false;
            document.getElementById('progressState').textContent = request.status === 413 ? 'File exceeds the server limit.' : 'Import failed. Try again.';
        });
        request.addEventListener('error', () => {
            progress.classList.add('is-error');
            submit.disabled = false;
            document.getElementById('progressState').textContent = 'Connection lost. Try again.';
        });
        request.addEventListener('abort', () => {
            progress.classList.add('is-error');
            submit.disabled = false;
            document.getElementById('progressState').textContent = 'Upload cancelled.';
        });
        document.getElementById('cancelUpload').onclick = () => request?.abort();
        request.send(new FormData(form));
    });
})();
</script>
@endpush
@endsection
