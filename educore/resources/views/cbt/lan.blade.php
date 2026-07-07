@extends('layouts.app')
@section('title', 'CBT — LAN Mode')
@section('page-title', 'CBT — LAN Mode')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:20px; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border); }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    .lan-steps { font-size:13px;color:var(--slate);line-height:1.8;margin-bottom:16px; }
    .lan-steps b { color:var(--midnight); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .exam-row { display:flex;align-items:center;justify-content:space-between;border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:10px;flex-wrap:wrap;gap:10px; }
    .exam-row-title { font-size:13px;font-weight:700;color:var(--midnight); }
    .exam-row-meta { font-size:11px;color:var(--slate); }
    .pill { font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:#FEF9EC;color:var(--indigo);border:1px solid #F2C35B; }
    .pill-ok { background:#ECFDF5;color:var(--emerald);border-color:#A7F3D0; }
    .sync-status { font-size:11px;color:var(--slate-light);margin-left:8px; }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('cbt.banks') }}" class="page-tab">Question Banks</a>
    <a href="{{ route('cbt.exams') }}" class="page-tab">Exams</a>
    <a href="{{ route('cbt.lan') }}" class="page-tab active">📡 LAN Mode</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="card">
    <div class="card-header"><span class="card-title">📡 What is LAN Mode?</span></div>
    <div class="card-body">
        <div class="lan-steps">
            Use this when students must sit a CBT exam with <b>no internet</b> — e.g. a school hall
            with only local WiFi.
            <br><b>1. Before the exam (while online):</b> click "Export Package" on the exam below and
            download the file.
            <br><b>2. On the exam-day laptop</b> (running this same app locally via XAMPP, offline):
            open this same LAN Mode page and upload that package under "Import Package". The exam,
            its questions, and the enrolled students load into the local database.
            <br><b>3. Students connect</b> to the laptop's WiFi hotspot and open its local address in
            a browser, log in as normal, and take the exam — the ordinary CBT screens work unchanged.
            <br><b>4. When the laptop regains internet</b>, click "Sync Now" (or just leave this page
            open — it retries quietly in the background) to push finished sessions back to the cloud.
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">📥 Import Package (on the offline/LAN laptop)</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('cbt.lan.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="package" accept="application/json" class="form-control" required style="margin-bottom:10px">
            <button type="submit" class="btn btn-primary">📥 Import Package</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">📤 Export / Sync (per exam)</span></div>
    <div class="card-body">
        @forelse($exams as $exam)
        <div class="exam-row" id="exam-row-{{ $exam->id }}">
            <div>
                <div class="exam-row-title">{{ $exam->title }}</div>
                <div class="exam-row-meta">
                    {{ optional($exam->questionBank->subject ?? null)->name }} ·
                    {{ optional($exam->classArm)->name }}
                    @if($exam->lan_exported_at)
                        · exported {{ $exam->lan_exported_at->diffForHumans() }}
                    @endif
                    @if($pendingCounts[$exam->id] ?? 0)
                        <span class="pill">{{ $pendingCounts[$exam->id] }} pending sync</span>
                    @elseif($exam->lan_sync_token)
                        <span class="pill pill-ok">all synced</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <a href="{{ route('cbt.lan.export', $exam) }}" class="btn btn-ghost">📤 Export Package</a>
                @if($exam->lan_sync_token)
                <button type="button" class="btn btn-primary" onclick="syncExam({{ $exam->id }})">🔄 Sync Now</button>
                <span class="sync-status" id="sync-status-{{ $exam->id }}"></span>
                @endif
            </div>
        </div>
        @empty
        <p style="font-size:13px;color:var(--slate)">No exams yet — create one under the Exams tab first.</p>
        @endforelse
    </div>
</div>

<script>
function syncExam(examId) {
    var el = document.getElementById('sync-status-' + examId);
    el.textContent = 'syncing…';
    fetch('/cbt/exams/' + examId + '/lan-sync', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if (data.status === 'synced') el.textContent = '✓ synced ' + data.count + ' just now';
        else if (data.status === 'nothing_to_sync') el.textContent = '✓ up to date';
        else if (data.status === 'offline') el.textContent = 'no internet yet — will retry';
        else el.textContent = data.message || data.status;
    })
    .catch(function(){ el.textContent = 'no internet yet — will retry'; });
}

// Quietly retry every 30s for any exam that has a sync token — this is what
// makes sync "automatic" once the LAN laptop regains internet.
document.addEventListener('DOMContentLoaded', function() {
    var buttons = document.querySelectorAll('[onclick^="syncExam("]');
    var examIds = Array.from(buttons).map(function(b) {
        return parseInt(b.getAttribute('onclick').match(/\d+/)[0], 10);
    });
    if (!examIds.length) return;
    setInterval(function() { examIds.forEach(syncExam); }, 30000);
});
</script>
@endsection
