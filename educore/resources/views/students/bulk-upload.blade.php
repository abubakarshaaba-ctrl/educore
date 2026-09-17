@extends('layouts.app')
@section('title','Bulk Student Upload')
@section('page-title','Bulk Student Upload')

@push('styles')
<style>
.upload-actions{margin-bottom:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.upload-card{background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;width:100%;min-width:0}
.card-head{padding:13px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;gap:10px}
.card-title{font-size:13px;font-weight:700;overflow-wrap:anywhere}
.card-body{padding:24px;min-width:0}
.drop-zone{border:2px dashed #CBD5E1;border-radius:12px;padding:40px 20px;text-align:center;cursor:pointer;transition:all 200ms;background:#F8FAFC;min-width:0}
.drop-zone:hover,.drop-zone.drag-over{border-color:var(--indigo);background:#EFF6FF}
.drop-icon{font-size:40px;margin-bottom:12px}
.drop-title{font-size:15px;font-weight:700;color:var(--midnight);margin-bottom:4px;overflow-wrap:anywhere}
.drop-sub{font-size:13px;color:var(--slate-light);line-height:1.5;overflow-wrap:anywhere}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 20px;min-height:40px;font-size:13px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;transition:all 150ms;text-decoration:none;max-width:100%}
.btn-primary{background:var(--indigo);color:white}
.btn-success{background:#059669;color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.select-control{box-sizing:border-box;width:100%;min-width:0;max-width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
.upload-note{margin-top:16px;padding:14px 16px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;font-size:12px;color:#92400E;line-height:1.55;overflow-wrap:anywhere}
.preview-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.preview-table{width:100%;min-width:760px;border-collapse:collapse;font-size:12px;margin-top:0}
.preview-table th{padding:7px 10px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);white-space:nowrap}
.preview-table td{padding:7px 10px;border-bottom:1px solid var(--border);color:#334155;overflow-wrap:anywhere}
.preview-table tr.error-row td{background:#FEF2F2}
.status-badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;white-space:nowrap}
.badge-ok{background:#ECFDF5;color:#059669}
.badge-err{background:#FEF2F2;color:#DC2626}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;font-size:13px;color:#059669;margin-bottom:16px;overflow-wrap:anywhere}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:13px;color:#DC2626;margin-bottom:16px;overflow-wrap:anywhere}
.alert-error ul{padding-left:18px}
.step-pills{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
.step-pill{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#F1F5F9;color:var(--slate)}
.step-pill.active{background:var(--indigo);color:white}
.step-pill.done{background:#ECFDF5;color:#059669}
@media(max-width:640px){
    .upload-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .upload-actions .btn{width:100%;padding-inline:10px;font-size:12px}
    .card-head{padding:12px 14px}
    .card-body{padding:14px}
    .drop-zone{padding:28px 14px}
    .drop-icon{font-size:34px;margin-bottom:8px}
    .drop-title{font-size:14px}
    .drop-sub{font-size:12px}
    .step-pills{display:grid;grid-template-columns:1fr;margin-bottom:16px}
    .step-pill{justify-content:center;text-align:center}
    .upload-note{padding:12px 13px}
    .preview-table{min-width:700px}
    .alert-success,.alert-error{padding:10px 12px;font-size:12px}
}
@media(max-width:380px){
    .upload-actions{grid-template-columns:1fr}
    .drop-zone{padding:22px 10px}
}
</style>
@endpush

@section('content')
<div class="upload-actions">
    <a href="{{ route('students.index') }}" class="btn btn-ghost">← Students</a>
    <a href="{{ route('students.bulk-upload.template') }}" class="btn btn-ghost">⬇ Download Template</a>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif
@if(session('errors_list'))
<div class="alert-error">
    <strong>{{ session('imported') }} imported, {{ count(session('errors_list')) }} failed:</strong>
    <ul style="margin:6px 0 0 16px">
        @foreach(session('errors_list') as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<div class="step-pills">
    <div class="step-pill done">① Download Template</div>
    <div class="step-pill done">② Fill Student Data</div>
    <div class="step-pill active">③ Upload & Import</div>
</div>

<div class="upload-card">
    <div class="card-head"><span class="card-title">📥 Import Students from Excel / CSV</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('students.bulk-upload.import') }}" enctype="multipart/form-data" id="importForm">
            @csrf
            <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                <div class="drop-icon">📊</div>
                <div class="drop-title" id="dropTitle">Drop your Excel/CSV file here</div>
                <div class="drop-sub">or click to browse · .xlsx, .csv · Max 5MB</div>
                <input type="file" id="fileInput" name="file" accept=".xlsx,.csv,.xls" style="display:none" required>
            </div>

            <div style="margin-top:20px">
                <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);margin-bottom:5px">
                    Default Class Arm (optional — overridden by file column)
                </label>
                <select name="default_class_arm_id" class="select-control">
                    <option value="">— Assign individually via file —</option>
                    @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="upload-note">
                <strong>Required columns:</strong> first_name, last_name ·
                <strong>Optional:</strong> middle_name, gender, date_of_birth, admission_number, state_of_origin, religion, class_arm (e.g. "JSS 1 A"), guardian_name, guardian_phone, guardian_relationship
            </div>

            <div style="margin-top:20px;display:flex;gap:10px">
                <button type="submit" class="btn btn-success" style="flex:1" id="importBtn">⚡ Import Students</button>
            </div>
        </form>
    </div>
</div>

@if(session('preview'))
<div class="upload-card" style="margin-top:20px;max-width:100%">
    <div class="card-head"><span class="card-title">Import Results — {{ count(session('preview')) }} rows</span></div>
    <div class="preview-wrap">
        <table class="preview-table">
            <thead><tr><th>#</th><th>First Name</th><th>Last Name</th><th>Class</th><th>Adm No</th><th>Status</th><th>Note</th></tr></thead>
            <tbody>
            @foreach(session('preview') as $i => $row)
            <tr class="{{ $row['status']==='error' ? 'error-row' : '' }}">
                <td>{{ $i+1 }}</td>
                <td>{{ $row['first_name'] }}</td>
                <td>{{ $row['last_name'] }}</td>
                <td>{{ $row['class'] ?? '—' }}</td>
                <td>{{ $row['admission_number'] ?? '—' }}</td>
                <td><span class="status-badge {{ $row['status']==='ok' ? 'badge-ok' : 'badge-err' }}">{{ $row['status']==='ok' ? 'Imported' : 'Failed' }}</span></td>
                <td style="color:{{ $row['status']==='ok' ? '#059669' : '#DC2626' }}">{{ $row['note'] }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
const drop = document.getElementById('dropZone');
const input = document.getElementById('fileInput');
input.addEventListener('change', () => {
    if (input.files[0]) {
        document.getElementById('dropTitle').textContent = '📎 ' + input.files[0].name;
        drop.style.borderColor = '#2563EB';
        drop.style.background  = '#EFF6FF';
    }
});
drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag-over'); });
drop.addEventListener('dragleave', () => drop.classList.remove('drag-over'));
drop.addEventListener('drop', e => {
    e.preventDefault();
    drop.classList.remove('drag-over');
    input.files = e.dataTransfer.files;
    if (input.files[0]) {
        document.getElementById('dropTitle').textContent = '📎 ' + input.files[0].name;
    }
});
</script>
@endpush