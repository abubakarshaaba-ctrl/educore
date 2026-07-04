@extends('layouts.app')
@section('title','Bulk Student Upload')
@section('page-title','Bulk Student Upload')

@push('styles')
<style>
.upload-card{background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden;width:100%}
.card-head{padding:13px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700}
.card-body{padding:24px}
.drop-zone{border:2px dashed #CBD5E1;border-radius:12px;padding:40px 20px;text-align:center;cursor:pointer;transition:all 200ms;background:#F8FAFC}
.drop-zone:hover,.drop-zone.drag-over{border-color:var(--indigo);background:#EFF6FF}
.drop-icon{font-size:40px;margin-bottom:12px}
.drop-title{font-size:15px;font-weight:700;color:var(--midnight);margin-bottom:4px}
.drop-sub{font-size:13px;color:var(--slate-light)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;transition:all 150ms;text-decoration:none}
.btn-primary{background:var(--indigo);color:white}
.btn-success{background:#059669;color:white}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.preview-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:16px}
.preview-table th{padding:7px 10px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);white-space:nowrap}
.preview-table td{padding:7px 10px;border-bottom:1px solid var(--border);color:#334155}
.preview-table tr.error-row td{background:#FEF2F2}
.status-badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px}
.badge-ok{background:#ECFDF5;color:#059669}
.badge-err{background:#FEF2F2;color:#DC2626}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;font-size:13px;color:#059669;margin-bottom:16px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:13px;color:#DC2626;margin-bottom:16px}
.step-pills{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
.step-pill{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#F1F5F9;color:var(--slate)}
.step-pill.active{background:var(--indigo);color:white}
.step-pill.done{background:#ECFDF5;color:#059669}
</style>
@endpush

@section('content')
<div style="margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <a href="{{ route('students.index') }}" class="btn btn-ghost" style="padding:7px 14px;font-size:12px">← Students</a>
    <a href="{{ route('students.bulk-upload.template') }}" class="btn btn-ghost" style="padding:7px 14px;font-size:12px">⬇ Download Template</a>
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
    <div class="card-head">
        <span class="card-title">📥 Import Students from Excel / CSV</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('students.bulk-upload.import') }}" enctype="multipart/form-data" id="importForm">
            @csrf

            <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                <div class="drop-icon">📊</div>
                <div class="drop-title" id="dropTitle">Drop your Excel/CSV file here</div>
                <div class="drop-sub">or click to browse &nbsp;·&nbsp; .xlsx, .csv &nbsp;·&nbsp; Max 5MB</div>
                <input type="file" id="fileInput" name="file" accept=".xlsx,.csv,.xls" style="display:none" required>
            </div>

            <div style="margin-top:20px">
                <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);margin-bottom:5px">
                    Default Class Arm (optional — overridden by file column)
                </label>
                <select name="default_class_arm_id" style="width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none">
                    <option value="">— Assign individually via file —</option>
                    @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-top:16px;padding:14px 16px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;font-size:12px;color:#92400E">
                <strong>Required columns:</strong> first_name, last_name &nbsp;·&nbsp;
                <strong>Optional:</strong> middle_name, gender, date_of_birth, admission_number, state_of_origin, religion, class_arm (e.g. "JSS 1 A"), guardian_name, guardian_phone, guardian_relationship
            </div>

            <div style="margin-top:20px;display:flex;gap:10px">
                <button type="submit" class="btn btn-success" style="flex:1;justify-content:center" id="importBtn">
                    ⚡ Import Students
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Results table --}}
@if(session('preview'))
<div class="upload-card" style="margin-top:20px;max-width:100%">
    <div class="card-head">
        <span class="card-title">Import Results — {{ count(session('preview')) }} rows</span>
    </div>
    <div style="overflow-x:auto">
        <table class="preview-table">
            <thead><tr>
                <th>#</th><th>First Name</th><th>Last Name</th><th>Class</th>
                <th>Adm No</th><th>Status</th><th>Note</th>
            </tr></thead>
            <tbody>
            @foreach(session('preview') as $i => $row)
            <tr class="{{ $row['status']==='error' ? 'error-row' : '' }}">
                <td>{{ $i+1 }}</td>
                <td>{{ $row['first_name'] }}</td>
                <td>{{ $row['last_name'] }}</td>
                <td>{{ $row['class'] ?? '—' }}</td>
                <td>{{ $row['admission_number'] ?? '—' }}</td>
                <td><span class="status-badge {{ $row['status']==='ok' ? 'badge-ok' : 'badge-err' }}">
                    {{ $row['status']==='ok' ? 'Imported' : 'Failed' }}
                </span></td>
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
