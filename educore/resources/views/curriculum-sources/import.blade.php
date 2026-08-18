@extends('layouts.super')

@section('title', 'Import Academic Content')

@section('content')
<div class="repo-shell">
    <nav class="repo-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('super.curriculum-sources.index') }}">Academic Repository</a>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        <span>Import archive</span>
    </nav>

    <header class="repo-page-head">
        <div>
            <h1>Import archive</h1>
            <p>Upload lesson notes or curriculum resources.</p>
        </div>
    </header>

    @include('curriculum-sources._navigation')

    @if($errors->any())
        <div class="repo-notice repo-notice-error">{{ $errors->first() }}</div>
    @endif

    <form id="archiveImportForm" method="POST" enctype="multipart/form-data" action="{{ route('super.curriculum-sources.store') }}">
        @csrf
        <div class="repo-import-grid">
            <section class="repo-card">
                <div class="repo-panel-head">
                    <span class="repo-step">1</span>
                    <div><h2>Select file</h2><p>ZIP, DOCX, DOC, PDF, XLSX or XLS</p></div>
                </div>

                <div class="repo-drop-wrap">
                    <label class="repo-drop" id="archiveDrop" for="archiveFile">
                        <input id="archiveFile" name="source_files[]" type="file" accept=".zip,.docx,.doc,.pdf,.xlsx,.xls" required>
                        <span class="repo-upload-art" aria-hidden="true">
                            <i class="repo-upload-file"><svg viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6zM14 3v5h5M9 13h6M9 17h4"/></svg></i>
                            <i class="repo-upload-cloud"><svg viewBox="0 0 24 24"><path d="M7 18H6a4 4 0 0 1-.5-7.97A6.5 6.5 0 0 1 18 9a4.5 4.5 0 0 1 0 9h-1M12 19V9m0 0-4 4m4-4 4 4"/></svg></i>
                            <i class="repo-upload-file pdf"><svg viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6zM14 3v5h5M9 14h6M9 17h5"/></svg></i>
                        </span>
                        <strong id="dropTitle">Drag &amp; drop your file here</strong>
                        <span id="dropHint">or click to browse</span>
                        <span class="repo-button repo-button-primary">Browse files</span>
                        <small>Maximum file size: 2 GB</small>
                    </label>
                </div>

                <div class="repo-selected" id="selectedFile" hidden>
                    <span class="repo-selected-type" id="selectedType">ZIP</span>
                    <div><strong id="selectedName"></strong><span id="selectedSize"></span></div>
                    <button type="button" id="removeFile" aria-label="Remove selected file">
                        <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <section class="repo-progress" id="uploadProgress" hidden aria-live="polite">
                    <div class="repo-progress-head">
                        <div><strong id="progressName">Uploading</strong><span id="progressState">Preparing file...</span></div>
                        <strong id="progressPercent">0%</strong>
                    </div>
                    <div class="repo-progress-track" id="progressTrack" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i></i></div>
                    <div class="repo-progress-meta"><span id="progressSize">0 MB of 0 MB</span><span id="progressSpeed">Calculating...</span><span id="progressTime">Estimating...</span></div>
                    <button type="button" id="cancelUpload">Cancel upload</button>
                </section>

                <div class="repo-auto-note">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    Files are organised as Class → Subject → Term. The original ZIP path is preserved.
                </div>
            </section>

            <aside class="repo-card repo-summary">
                <span class="repo-summary-kicker">IMPORT SUMMARY</span>
                <h2>Ready to process</h2>
                <ul>
                    <li><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>Class, subject and term are detected automatically</li>
                    <li><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>Duplicate files are skipped</li>
                    <li><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>Resources remain inactive for review</li>
                </ul>

                <input type="hidden" name="authority" value="OTHER">
                <input type="hidden" name="source_type" value="lesson_note">
                <input type="hidden" name="rights_status" value="institution_authorised">
                <input type="hidden" name="column_mapping_json" value='{"title":"Title","topic":"Topic","subtopic":"Subtopic","content":"Content","resource_type":"Resource Type","source_year":"Source Year"}'>

                <button type="submit" class="repo-button repo-button-primary repo-button-full" id="submitImport" disabled>
                    <svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg>
                    Import archive
                </button>
                <a href="{{ route('super.curriculum-sources.index') }}" class="repo-cancel">Cancel</a>
            </aside>
        </div>
    </form>
</div>

@include('curriculum-sources._styles')

@push('scripts')
<script>
(() => {
    const form = document.getElementById('archiveImportForm');
    const input = document.getElementById('archiveFile');
    const drop = document.getElementById('archiveDrop');
    const selected = document.getElementById('selectedFile');
    const submit = document.getElementById('submitImport');
    const progress = document.getElementById('uploadProgress');
    const cancel = document.getElementById('cancelUpload');
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
        if (file.size > 2147483648) {
            input.value = '';
            selected.hidden = true;
            submit.disabled = true;
            document.getElementById('dropTitle').textContent = 'File exceeds 2 GB';
            document.getElementById('dropHint').textContent = 'Choose a smaller archive';
            return;
        }
        document.getElementById('selectedName').textContent = file.name;
        document.getElementById('selectedSize').textContent = `${formatBytes(file.size)} · Ready to import`;
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
        document.getElementById('dropTitle').textContent = 'Drag & drop your file here';
        document.getElementById('dropHint').textContent = 'or click to browse';
    });
    ['dragenter', 'dragover'].forEach(name => drop.addEventListener(name, event => {
        event.preventDefault();
        drop.classList.add('dragging');
    }));
    ['dragleave', 'drop'].forEach(name => drop.addEventListener(name, event => {
        event.preventDefault();
        drop.classList.remove('dragging');
    }));
    drop.addEventListener('drop', event => {
        const file = event.dataTransfer.files[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        showFile(file);
    });

    form.addEventListener('submit', event => {
        const file = input.files[0];
        if (!file) return;
        event.preventDefault();
        progress.hidden = false;
        progress.className = 'repo-progress';
        submit.disabled = true;
        cancel.hidden = false;
        document.getElementById('progressName').textContent = file.name;
        document.getElementById('progressState').textContent = 'Uploading...';
        progress.scrollIntoView({ behavior: 'smooth', block: 'center' });

        const started = performance.now();
        const bar = progress.querySelector('.repo-progress-track i');
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
                progress.classList.add('processing');
                document.getElementById('progressState').textContent = 'Sorting, extracting and indexing...';
                document.getElementById('progressTime').textContent = 'Processing';
                cancel.hidden = true;
            }
        });
        request.addEventListener('load', () => {
            if (request.status >= 200 && request.status < 400) {
                document.getElementById('progressState').textContent = 'Import complete';
                window.location.assign(request.responseURL || `{{ route('super.curriculum-sources.index') }}`);
                return;
            }
            progress.classList.add('error');
            submit.disabled = false;
            document.getElementById('progressState').textContent = request.status === 413 ? 'File exceeds the server limit.' : 'Import failed. Try again.';
        });
        request.addEventListener('error', () => {
            progress.classList.add('error');
            submit.disabled = false;
            document.getElementById('progressState').textContent = 'Connection lost. Try again.';
        });
        request.addEventListener('abort', () => {
            progress.classList.add('error');
            submit.disabled = false;
            document.getElementById('progressState').textContent = 'Upload cancelled.';
        });
        cancel.onclick = () => request?.abort();
        request.send(new FormData(form));
    });
})();
</script>
@endpush
@endsection
