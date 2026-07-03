@extends('layouts.app')
@section('title', 'Class Management')
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
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .level-card { border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px; }
    .level-name { font-size:15px;font-weight:700;color:var(--midnight); }
    .level-section { font-size:11px;font-weight:600;color:var(--indigo);text-transform:uppercase;letter-spacing:0.05em;margin-top:3px; }
    .level-stats { display:flex;gap:16px;margin-top:10px; }
    .level-stat { font-size:12px;color:var(--slate); }
    .level-stat strong { color:var(--midnight); }
    .arms-list { display:flex;gap:6px;flex-wrap:wrap;margin-top:10px; }
    .arm-chip { font-size:11px;font-weight:600;background:var(--indigo-bg);color:var(--indigo);padding:3px 10px;border-radius:20px; }
    .badge { display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px; }
    .badge-info { background:var(--indigo-bg);color:var(--indigo); }
    .empty-state { text-align:center;padding:40px;color:var(--slate-light);font-size:13px; }
    @media(max-width:1024px) { .two-col { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

{{-- ── Class Metrics ───────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
    <div style="background:white;border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center">
        <div style="font-size:26px;font-weight:800;color:var(--indigo)">{{ $totalLevels ?? $levels->count() }}</div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin-top:3px">Class Levels</div>
    </div>
    <div style="background:white;border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center">
        <div style="font-size:26px;font-weight:800;color:#059669">{{ $totalArms ?? '—' }}</div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin-top:3px">Class Arms</div>
    </div>
    <div style="background:white;border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center">
        <div style="font-size:26px;font-weight:800;color:#D97706">{{ $totalStudents ?? '—' }}</div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);margin-top:3px">Active Students</div>
    </div>
</div>

<div class="page-tabs">
    <a href="{{ route('classes.levels') }}" class="page-tab active">Class Levels</a>
    <a href="{{ route('classes.arms') }}" class="page-tab">Class Arms</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="two-col">
    <div class="card">
        <div class="card-header"><span class="card-title">Class Levels ({{ $levels->count() }})</span></div>
        <div class="card-body">
            @forelse($levels as $level)
            <div class="level-card">
                <div style="display:flex;align-items:start;justify-content:space-between">
                    <div class="level-view" id="view-{{ $level->id }}">
                        <div class="level-name">{{ $level->name }}</div>
                        <div class="level-section">{{ ucfirst(str_replace('_',' ',$level->section)) }}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="badge badge-info">Order {{ $level->order_index }}</span>
                        <button type="button" class="btn btn-ghost" id="editbtn-{{ $level->id }}" style="font-size:11px;padding:4px 9px"
                                onclick="document.getElementById('view-{{ $level->id }}').style.display='none';document.getElementById('edit-{{ $level->id }}').style.display='block';this.style.display='none'">
                            Edit
                        </button>
                    </div>
                </div>

                <form id="edit-{{ $level->id }}" method="POST" action="{{ route('classes.levels.update', $level) }}" style="display:none;margin:10px 0;padding:10px;background:var(--bg-soft,#F8FAFC);border-radius:8px">
                    @csrf
                    @method('PATCH')
                    <div class="form-group" style="margin-bottom:8px">
                        <label class="form-label">Level Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $level->name }}" required>
                    </div>
                    <div class="form-group" style="margin-bottom:8px">
                        <label class="form-label">Section</label>
                        <select name="section" class="form-control" required>
                            @foreach(['creche'=>'Crèche','nursery'=>'Nursery','primary'=>'Primary','junior_secondary'=>'Junior Secondary','senior_secondary'=>'Senior Secondary'] as $val => $label)
                                <option value="{{ $val }}" {{ $level->section === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:8px">
                        <label class="form-label">Order Index</label>
                        <input type="number" name="order_index" class="form-control" value="{{ $level->order_index }}" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size:12px;padding:6px 12px">Save</button>
                    <button type="button" class="btn btn-ghost" style="font-size:12px;padding:6px 12px"
                            onclick="document.getElementById('edit-{{ $level->id }}').style.display='none';document.getElementById('view-{{ $level->id }}').style.display='block';document.getElementById('editbtn-{{ $level->id }}').style.display='inline-block'">
                        Cancel
                    </button>
                </form>

                <div class="level-stats">
                    <div class="level-stat">Arms: <strong>{{ $level->classArms->count() }}</strong></div>
                    <div class="level-stat">Promotion Rule: <strong>{{ $level->promotionRule ? '✓' : '—' }}</strong></div>
                </div>
                <div class="arms-list">
                    @foreach($level->classArms as $arm)
                        <a href="{{ route('classes.show', $arm) }}" class="arm-chip">{{ $level->name }} {{ $arm->name }}</a>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="empty-state">No class levels yet. Add one →</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Add Class Level</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('classes.levels.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Level Name <span>*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. JSS 1, SSS 3, Primary 4">
                </div>
                <div class="form-group">
                    <label class="form-label">Section <span>*</span></label>
                    <select name="section" class="form-control">
                        <option value="">Select section</option>
                        @foreach(['creche'=>'Crèche','nursery'=>'Nursery','primary'=>'Primary','junior_secondary'=>'Junior Secondary','senior_secondary'=>'Senior Secondary'] as $val => $label)
                            <option value="{{ $val }}" {{ old('section') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Order Index <span>*</span></label>
                    <input type="number" name="order_index" class="form-control" value="{{ old('order_index', $levels->count() + 1) }}" min="1">
                    <div style="font-size:11px;color:var(--slate-light);margin-top:3px">Lower number = lower class (e.g. JSS 1 = 7, SSS 3 = 12)</div>
                </div>
                <button type="submit" class="btn btn-primary">Add Class Level</button>
            </form>
        </div>
    </div>
</div>
@endsection
