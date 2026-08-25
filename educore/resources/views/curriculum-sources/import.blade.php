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

    <form id="archiveImportForm" method="POST" enctype="multipart/form-data"
        action="{{ route('super.curriculum-sources.store') }}"
        data-initiate-url="{{ route('super.curriculum-sources.uploads.initiate') }}"
        data-status-url="{{ route('super.curriculum-sources.uploads.status', ['upload' => 'UPLOAD_ID']) }}"
        data-chunk-url="{{ route('super.curriculum-sources.uploads.chunk', ['upload' => 'UPLOAD_ID']) }}"
        data-complete-url="{{ route('super.curriculum-sources.uploads.complete', ['upload' => 'UPLOAD_ID']) }}"
        data-cancel-url="{{ route('super.curriculum-sources.uploads.cancel', ['upload' => 'UPLOAD_ID']) }}">
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
                    <div class="repo-progress-actions">
                        <button type="button" id="pauseUpload">Pause upload</button>
                        <button type="button" id="resumeUpload" hidden>Resume upload</button>
                        <button type="button" id="retryUpload" hidden>Retry upload</button>
                        <button type="button" id="cancelUpload" class="danger">Cancel upload</button>
                    </div>
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
    const pause = document.getElementById('pauseUpload');
    const resume = document.getElementById('resumeUpload');
    const retry = document.getElementById('retryUpload');
    const cancel = document.getElementById('cancelUpload');
    if (!form || !input || !window.XMLHttpRequest) return;

    const storageKey = 'educore.repository-upload.{{ auth()->id() }}.v2';
    const csrf = form.querySelector('input[name="_token"]').value;
    const endpoint = (type, id = '') => form.dataset[`${type}Url`].replace('UPLOAD_ID', id);
    const bar = progress.querySelector('.repo-progress-track i');
    const track = document.getElementById('progressTrack');
    let request = null;
    let currentFile = null;
    let state = null;
    let running = false;
    let pauseRequested = false;
    let startedAt = 0;
    let startedBytes = 0;

    const formatBytes = value => {
        if (value >= 1073741824) return `${(value / 1073741824).toFixed(2)} GB`;
        if (value >= 1048576) return `${(value / 1048576).toFixed(value >= 104857600 ? 0 : 1)} MB`;
        if (value >= 1024) return `${(value / 1024).toFixed(1)} KB`;
        return `${value} B`;
    };
    const remaining = seconds => {
        if (!Number.isFinite(seconds) || seconds < 0) return 'Estimating...';
        if (seconds < 60) return `${Math.max(1, Math.ceil(seconds))} sec left`;
        return `${Math.ceil(seconds / 60)} min left`;
    };
    const fingerprint = file => `${file.name}:${file.size}:${file.lastModified || 0}`;
    const persist = () => {
        try {
            state ? localStorage.setItem(storageKey, JSON.stringify(state)) : localStorage.removeItem(storageKey);
        } catch (_) {}
    };
    const messageFrom = payload => {
        if (payload?.errors) {
            const messages = Object.values(payload.errors).flat();
            if (messages.length) return messages[0];
        }
        return payload?.message || 'The upload could not be completed.';
    };
    const api = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.headers || {}),
            },
        });
        const payload = response.status === 204 ? {} : await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(messageFrom(payload));
        return payload;
    };
    const setFileCard = (name, size, suffix) => {
        document.getElementById('selectedName').textContent = name;
        document.getElementById('selectedSize').textContent = `${formatBytes(size)} · ${suffix}`;
        document.getElementById('selectedType').textContent = (name.split('.').pop() || 'FILE').toUpperCase().slice(0, 4);
        selected.hidden = false;
    };
    const setProgress = (uploaded, total, speed = 0) => {
        const value = Math.min(100, Math.floor(uploaded / Math.max(1, total) * 100));
        bar.style.width = `${value}%`;
        document.getElementById('progressPercent').textContent = `${value}%`;
        document.getElementById('progressSize').textContent = `${formatBytes(uploaded)} of ${formatBytes(total)}`;
        document.getElementById('progressSpeed').textContent = speed > 0 ? `${formatBytes(speed)}/s` : 'Paused';
        document.getElementById('progressTime').textContent = speed > 0 ? remaining((total - uploaded) / speed) : 'Ready to resume';
        track.setAttribute('aria-valuenow', value);
    };
    const showState = (message, mode = 'paused') => {
        if (!state) return;
        progress.hidden = false;
        progress.className = `repo-progress${mode === 'error' ? ' error' : ''}${mode === 'processing' ? ' processing' : ''}`;
        document.getElementById('progressName').textContent = state.file_name;
        document.getElementById('progressState').textContent = message;
        setProgress(state.uploaded_bytes || 0, state.file_size || 1);
        setFileCard(state.file_name, state.file_size, `${state.progress || 0}% uploaded`);
        pause.hidden = mode !== 'uploading';
        resume.hidden = mode !== 'paused' || !currentFile;
        retry.hidden = mode !== 'error' || !currentFile;
        cancel.hidden = mode === 'processing' || mode === 'completed';
        submit.disabled = mode === 'uploading' || mode === 'processing' || !currentFile;
    };
    const resetUi = () => {
        state = null;
        currentFile = null;
        request = null;
        running = false;
        pauseRequested = false;
        persist();
        input.value = '';
        selected.hidden = true;
        progress.hidden = true;
        progress.className = 'repo-progress';
        bar.style.width = '0%';
        submit.disabled = true;
        document.getElementById('dropTitle').textContent = 'Drag & drop your file here';
        document.getElementById('dropHint').textContent = 'or click to browse';
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
        if (state && !['completed'].includes(state.status) && state.fingerprint !== fingerprint(file)) {
            input.value = '';
            currentFile = null;
            showState(`Select ${state.file_name} to resume this upload, or cancel it before choosing another file.`, 'error');
            return;
        }
        currentFile = file;
        setFileCard(file.name, file.size, state ? 'Ready to resume' : 'Ready to import');
        document.getElementById('dropTitle').textContent = state ? 'File ready to resume' : 'File selected';
        document.getElementById('dropHint').textContent = 'Click to replace';
        submit.disabled = false;
        if (state) showState('Upload paused. Continue from the saved position.', 'paused');
    };

    input.addEventListener('change', () => showFile(input.files[0]));
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

    const uploadChunk = index => new Promise((resolve, reject) => {
        const start = index * state.chunk_size;
        const end = Math.min(currentFile.size, start + state.chunk_size);
        const body = new FormData();
        body.append('index', index);
        body.append('chunk', currentFile.slice(start, end), `${currentFile.name}.part`);
        const xhr = new XMLHttpRequest();
        request = xhr;
        xhr.open('POST', endpoint('chunk', state.id), true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.upload.addEventListener('progress', event => {
            if (!event.lengthComputable) return;
            const uploaded = Math.min(state.file_size, state.uploaded_bytes + event.loaded);
            const elapsed = Math.max((performance.now() - startedAt) / 1000, .1);
            const speed = Math.max(0, uploaded - startedBytes) / elapsed;
            setProgress(uploaded, state.file_size, speed);
        });
        xhr.addEventListener('load', () => {
            request = null;
            let payload = {};
            try { payload = JSON.parse(xhr.responseText || '{}'); } catch (_) {}
            if (xhr.status >= 200 && xhr.status < 300) return resolve(payload);
            reject(new Error(messageFrom(payload)));
        });
        xhr.addEventListener('error', () => { request = null; reject(new Error('Connection lost. Retry to continue from the saved position.')); });
        xhr.addEventListener('abort', () => { request = null; reject(Object.assign(new Error('Upload paused.'), { paused: true })); });
        xhr.send(body);
    });

    const pollProcessing = async () => {
        showState('Sorting, extracting and indexing...', 'processing');
        while (state?.status === 'processing') {
            await new Promise(resolve => setTimeout(resolve, 3000));
            state = await api(endpoint('status', state.id));
            persist();
        }
        if (state?.status === 'completed') {
            localStorage.removeItem(storageKey);
            window.location.assign(state.redirect_url || `{{ route('super.curriculum-sources.index') }}`);
            return;
        }
        if (state?.status === 'failed') showState('Processing failed. Retry the upload.', 'error');
    };

    const startUpload = async () => {
        if (running || !currentFile) return;
        running = true;
        pauseRequested = false;
        submit.disabled = true;
        progress.scrollIntoView({ behavior: 'smooth', block: 'center' });
        try {
            if (!state) {
                const body = new FormData(form);
                body.delete('source_files[]');
                body.append('file_name', currentFile.name);
                body.append('file_size', currentFile.size);
                body.append('last_modified', currentFile.lastModified || 0);
                body.append('fingerprint', fingerprint(currentFile));
                state = await api(endpoint('initiate'), { method: 'POST', body });
            } else {
                state = await api(endpoint('status', state.id));
            }
            if (state.fingerprint !== fingerprint(currentFile)) throw new Error('Select the original file to resume this upload.');
            persist();
            showState('Uploading...', 'uploading');
            startedAt = performance.now();
            startedBytes = state.uploaded_bytes;

            while (state.received.length < state.total_chunks) {
                if (pauseRequested) throw Object.assign(new Error('Upload paused.'), { paused: true });
                const received = new Set(state.received);
                let index = 0;
                while (received.has(index)) index++;
                state = await uploadChunk(index);
                persist();
                showState('Uploading...', 'uploading');
            }

            if (pauseRequested) throw Object.assign(new Error('Upload paused.'), { paused: true });
            showState('Sorting, extracting and indexing...', 'processing');
            state = await api(endpoint('complete', state.id), { method: 'POST' });
            persist();
            if (state.status === 'processing') return await pollProcessing();
            if (state.status === 'completed') {
                localStorage.removeItem(storageKey);
                window.location.assign(state.redirect_url || `{{ route('super.curriculum-sources.index') }}`);
            }
        } catch (error) {
            if (pauseRequested || error.paused) {
                showState('Upload paused. Resume when ready.', 'paused');
            } else {
                showState(error.message || 'Connection lost. Retry the upload.', 'error');
            }
        } finally {
            running = false;
            request = null;
            if (state && !['processing', 'completed'].includes(state.status)) {
                submit.disabled = !currentFile;
            }
        }
    };

    const cancelSession = async () => {
        if (!state) return resetUi();
        if ((state.uploaded_bytes || 0) > 0 && !window.confirm('Cancel this upload and remove its saved progress?')) return;
        pauseRequested = true;
        request?.abort();
        try {
            await api(endpoint('cancel', state.id), { method: 'DELETE' });
            resetUi();
        } catch (error) {
            showState(error.message || 'Unable to cancel the upload.', 'error');
        }
    };

    form.addEventListener('submit', event => {
        if (!currentFile) return;
        event.preventDefault();
        startUpload();
    });
    pause.addEventListener('click', () => {
        pauseRequested = true;
        request?.abort();
        if (!request && state) showState('Upload paused. Resume when ready.', 'paused');
    });
    resume.addEventListener('click', startUpload);
    retry.addEventListener('click', startUpload);
    cancel.addEventListener('click', cancelSession);
    document.getElementById('removeFile').addEventListener('click', cancelSession);

    (async () => {
        let saved = null;
        try { saved = JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch (_) {}
        if (!saved?.id) return;
        state = saved;
        try {
            state = await api(endpoint('status', state.id));
            persist();
            document.getElementById('dropTitle').textContent = 'Resume saved upload';
            document.getElementById('dropHint').textContent = `Select ${state.file_name}`;
            if (state.status === 'completed') {
                localStorage.removeItem(storageKey);
                return window.location.assign(state.redirect_url || `{{ route('super.curriculum-sources.index') }}`);
            }
            if (state.status === 'processing') return pollProcessing();
            showState('Saved upload found. Select the same file to resume.', state.status === 'failed' ? 'error' : 'paused');
        } catch (error) {
            if (/expired|not found/i.test(error.message)) {
                resetUi();
                return;
            }
            showState('Connection unavailable. Retry when the network returns.', 'error');
        }
    })();
})();
</script>
@endpush
@endsection
