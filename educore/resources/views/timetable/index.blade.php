@extends('layouts.app')
@section('title', 'Timetable')
@section('page-title', 'Timetable')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }

    .two-col { display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-sub { font-size:12px;color:var(--slate);margin-top:2px; }
    .card-body { padding:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-generate { background:linear-gradient(135deg,#059669,#047857);color:white;width:100%;justify-content:center;padding:12px 18px;font-size:14px; }
    .btn-generate:hover { opacity:0.9; }
    .step-list { list-style:none;padding:0;margin:0; }
    .step-item { display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px; }
    .step-item:last-child { border-bottom:none; }
    .step-num { width:26px;height:26px;border-radius:50%;background:var(--indigo);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
    .step-done { background:var(--emerald); }
    .step-text { color:var(--midnight); }
    .step-link { color:var(--indigo);text-decoration:none;font-size:12px;margin-left:auto;white-space:nowrap; }
    .step-link:hover { text-decoration:underline; }
    @media(max-width:768px) { .two-col { grid-template-columns:1fr; } .page-tabs { width:100%;overflow-x:auto; } .page-tab { white-space:nowrap; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    @if(auth()->user()->canManage('timetable'))
    <a href="{{ route('timetable.configure') }}" class="page-tab">1. School Hours</a>
    <a href="{{ route('timetable.frequency') }}" class="page-tab">2. Subject Frequency</a>
    <a href="{{ route('timetable.index') }}" class="page-tab active">3. View / Generate</a>
    @endif
    <a href="{{ route('timetable.teacher') }}" class="page-tab {{ auth()->user()->canManage('timetable') ? '' : 'active' }}">Teacher View</a>
</div>

<div class="two-col">
    {{-- View existing timetable --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">View Class Timetable</div>
            <div class="card-sub">Select a class and session to view the weekly timetable</div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('timetable.view') }}">
                <div class="form-group">
                    <label class="form-label">Class <span>*</span></label>
                    <select name="class_arm_id" class="form-control" required>
                        <option value="">Select class</option>
                        @foreach($classArms as $arm)
                            <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Session <span>*</span></label>
                    <select name="session_id" class="form-control" required>
                        <option value="">Select session</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $s->is_current ? 'selected' : '' }}>
                                {{ $s->name }}{{ $s->is_current ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">View Timetable</button>
            </form>
        </div>
    </div>

    {{-- Auto-generate — admin / principal only --}}
    @if(auth()->user()->canManage('timetable'))
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚡ Auto-Generate Timetable</div>
            <div class="card-sub">Complete all 3 steps before generating</div>
        </div>
        <div class="card-body">
            <ul class="step-list" style="margin-bottom:20px">
                <li class="step-item">
                    <div class="step-num step-done">1</div>
                    <span class="step-text">Configure school hours & periods</span>
                    @if(auth()->user()->canManage('timetable'))
                        <a href="{{ route('timetable.configure') }}" class="step-link">Edit →</a>
                    @endif
                </li>
                <li class="step-item">
                    <div class="step-num step-done">2</div>
                    <span class="step-text">Assign subjects to class + set teacher</span>
                    <a href="{{ route('subjects.index') }}" class="step-link">Edit →</a>
                </li>
                <li class="step-item">
                    <div class="step-num step-done">3</div>
                    <span class="step-text">Set subject frequency (periods/week)</span>
                    @if(auth()->user()->canManage('timetable'))
                        <a href="{{ route('timetable.frequency') }}" class="step-link">Edit →</a>
                    @endif
                </li>
            </ul>

            <form method="POST" action="{{ route('timetable.generate') }}"
                  onsubmit="return confirm('This will clear and regenerate the timetable for this class. Continue?')">
                @csrf
                <div class="form-group">
                    <label class="form-label">Class <span>*</span></label>
                    <select name="class_arm_id" class="form-control" required>
                        <option value="">Select class</option>
                        @foreach($classArms as $arm)
                            <option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Session <span>*</span></label>
                    <select name="session_id" class="form-control" required>
                        <option value="">Select session</option>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $s->is_current ? 'selected' : '' }}>
                                {{ $s->name }}{{ $s->is_current ? ' (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="overwrite" value="1">
                <button type="submit" class="btn btn-generate">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                    Generate Timetable Now
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
