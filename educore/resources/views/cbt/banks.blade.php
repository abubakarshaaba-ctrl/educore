@extends('layouts.app')
@section('title', 'CBT Question Banks')
@section('page-title', 'CBT Exams')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px; }
    .page-tab { padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;border:1.5px solid var(--border);background:white;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active,.page-tab:hover { background:var(--indigo);border-color:var(--indigo);color:white; }
    
    .two-col { display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start; }
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
    .btn-indigo { background:var(--indigo-bg);color:var(--indigo);border:1px solid #BFDBFE; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight); }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .empty-state { text-align:center;padding:40px;color:var(--slate-light);font-size:13px; }
    @media(max-width:1024px) { .two-col { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('cbt.banks') }}" class="page-tab active">Question Banks</a>
    <a href="{{ route('cbt.exams') }}" class="page-tab">Exams</a>
    <a href="{{ route('cbt.lan') }}" class="page-tab">📡 LAN Mode</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="two-col">
    <div class="card">
        <div class="card-header"><span class="card-title">Question Banks</span></div>
        @if($banks->count())
        <div class="tbl"><table>
            <thead><tr><th>Name</th><th>Subject</th><th>Class</th><th>Questions</th><th></th></tr></thead>
            <tbody>
                @foreach($banks as $bank)
                <tr>
                    <td><strong>{{ $bank->name }}</strong></td>
                    <td>{{ $bank->subject->name }}</td>
                    <td>{{ $bank->classLevel->name }}</td>
                    <td>{{ $bank->questions()->count() }}</td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="{{ route('cbt.questions', $bank) }}" class="btn btn-sm btn-indigo">Manage Questions</a>
                            <a href="{{ route('cbt.banks.edit', $bank) }}" class="btn btn-sm" style="background:#F1F5F9;color:var(--slate);border:1px solid var(--border)">Edit</a>
                            <form method="POST" action="{{ route('cbt.banks.destroy', $bank) }}"
                                  onsubmit="return confirm('DELETE this bank and ALL {{ $bank->questions()->count() }} questions? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm" style="background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @else
        <div class="empty-state">No question banks yet. Create one →</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">New Question Bank</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('cbt.banks.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Bank Name <span>*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. JSS 1 Mathematics Midterm 2025">
                </div>
                <div class="form-group">
                    <label class="form-label">Subject <span>*</span></label>
                    <select name="subject_id" class="form-control">
                        <option value="">Select subject</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ old('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Class Level <span>*</span></label>
                    <select name="class_level_id" class="form-control">
                        <option value="">Select class</option>
                        @foreach($classLevels as $cl)
                            <option value="{{ $cl->id }}" {{ old('class_level_id') == $cl->id ? 'selected' : '' }}>{{ $cl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional description...">{{ old('description') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create Bank</button>
            </form>
        </div>
    </div>
</div>
@endsection
