@extends('layouts.app')
@section('title', 'Class Arms')
@section('page-title', 'Class Management')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .two-col { display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-sm { padding:5px 10px;font-size:12px; }
    .btn-indigo { background:var(--indigo-bg);color:var(--indigo); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .empty-state { text-align:center;padding:40px;color:var(--slate-light);font-size:13px; }
    @media(max-width:1024px) { .two-col { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('classes.levels') }}" class="page-tab">Class Levels</a>
    <a href="{{ route('classes.arms') }}" class="page-tab active">Class Arms</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="two-col">
    <div class="card">
        <div class="card-header"><span class="card-title">All Class Arms</span></div>
        @if($arms->count())
        <div class="tbl"><table>
            <thead><tr><th>Class</th><th>Students</th><th>Form Tutor</th><th></th></tr></thead>
            <tbody>
                @foreach($arms as $arm)
                <tr>
                    <td><strong>{{ $arm->classLevel->name }} {{ $arm->name }}</strong></td>
                    <td>{{ $arm->students->count() }}</td>
                    <td>{{ optional($arm->formTutor)->name ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('classes.show', $arm) }}" class="btn btn-sm btn-indigo">Manage</a>
                            @if($arm->students->count() === 0)
                            <form method="POST" action="{{ route('classes.arms.destroy', $arm) }}"
                                  onsubmit="return confirm('Delete {{ $arm->classLevel->name }} {{ $arm->name }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" style="background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @else
        <div class="empty-state">No class arms yet. Create one →</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Add Class Arm</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('classes.arms.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Class Level <span>*</span></label>
                    <select name="class_level_id" class="form-control" required>
                        <option value="">Select level</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" {{ old('class_level_id') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Arm Name <span>*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. A, B, Gold, Science">
                </div>
                <div class="form-group">
                    <label class="form-label">Form Tutor</label>
                    <select name="form_tutor_id" class="form-control">
                        <option value="">None assigned</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('form_tutor_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create Class Arm</button>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
function toggleEdit(id) {
    const form = document.getElementById('edit_' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
@endsection
