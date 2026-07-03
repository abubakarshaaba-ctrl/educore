@extends('layouts.app')
@section('title','Teacher Allocation — '.$arm->full_name)
@section('page-title','Teacher Subject Allocation')

@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);text-align:left}
td{padding:9px 14px;border-bottom:1px solid var(--border);color:var(--midnight);vertical-align:middle}
tr:hover td{background:#FAFBFF}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;text-transform:uppercase}
.b-compulsory{background:#ECFDF5;color:#059669}.b-elective{background:#EFF6FF;color:#2563EB}
.b-optional{background:#FFFBEB;color:#D97706}.b-not_offered{background:#F1F5F9;color:#94A3B8}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.info-row{background:#F8FAFC;border-radius:8px;padding:12px 16px;display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px}
.ir-item{font-size:13px;color:var(--slate)}.ir-item strong{color:var(--midnight)}
select.ts-sel{font-size:12px;padding:5px 10px;border:1.5px solid var(--border);border-radius:7px;background:#F8FAFC;font-family:inherit;min-width:160px}
select.ts-sel:focus{border-color:var(--indigo)}
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <a href="{{ route('curriculum.arm-tracks') }}" class="btn btn-ghost">← Class Arms</a>
    <h2 style="font-size:16px;font-weight:800;color:var(--midnight)">{{ $arm->full_name }} — Teacher Allocation</h2>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:14px">{{ $errors->first() }}</div>@endif

<div class="info-row">
    <div class="ir-item">Class: <strong>{{ $arm->full_name }}</strong></div>
    <div class="ir-item">Level: <strong>{{ optional($arm->classLevel)->name }}</strong></div>
    <div class="ir-item">Track: <strong>{{ optional($arm->academicTrack)->name ?? 'General' }}</strong></div>
    <div class="ir-item">Eligible Subjects: <strong>{{ $eligibleRules->count() }}</strong></div>
    <div class="ir-item">Teachers Assigned: <strong>{{ $assignments->whereNotNull('teacher_id')->count() }}</strong></div>
</div>

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:9px;padding:11px 14px;font-size:13px;color:#1D4ED8;margin-bottom:16px">
    👨‍🏫 Only subjects offered for <strong>{{ optional($arm->classLevel)->name }}</strong>
    @if($arm->academicTrack) / <strong>{{ $arm->academicTrack->name }}</strong> track @endif
    are shown. To add subjects, first update the <a href="{{ route('curriculum.level-subjects', $arm->class_level_id) }}" style="color:var(--indigo);font-weight:700">class level subject rules</a>.
</div>

<div class="card">
    <div class="ch">Subject → Teacher Assignments</div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Status in Curriculum</th>
                <th>Assigned Teacher</th>
                <th>Session</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @forelse($eligibleRules as $rule)
        @php
            $assignment = $assignments->get($rule->subject_id);
            $teacher    = optional($assignment)->teacher;
        @endphp
        <tr>
            <td>
                <div style="font-weight:600">{{ optional($rule->subject)->name }}</div>
                <div style="font-size:11px;color:var(--slate-light)">{{ optional($rule->subject)->code }}</div>
            </td>
            <td>
                <span class="badge b-{{ $rule->subject_status }}">
                    {{ ucfirst(str_replace('_',' ',$rule->subject_status)) }}
                </span>
            </td>
            <td>
                <form method="POST" action="{{ route('curriculum.arm-teachers.set', $arm->id) }}">
                    @csrf
                    <input type="hidden" name="subject_id" value="{{ $rule->subject_id }}">
                    <input type="hidden" name="is_active" value="1">
                    <select name="teacher_id" class="ts-sel" onchange="this.form.submit()">
                        <option value="">— Unassigned —</option>
                        @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ optional($assignment)->teacher_id == $t->id ? 'selected':'' }}>
                            {{ $t->name }}
                        </option>
                        @endforeach
                    </select>
                </form>
            </td>
            <td style="font-size:12px;color:var(--slate-light)">
                {{ optional(optional($assignment)->session)->name ?? 'All Sessions' }}
            </td>
            <td>
                @if($assignment)
                <form method="POST" action="{{ route('curriculum.arm-teachers.remove', $arm->id) }}">
                    @csrf @method('DELETE')
                    <input type="hidden" name="subject_id" value="{{ $rule->subject_id }}">
                    <button class="btn btn-danger" style="padding:4px 8px;font-size:11px" onclick="return confirm('Remove allocation?')">✕</button>
                </form>
                @else
                <span style="font-size:11px;color:var(--slate-light)">Not assigned</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align:center;padding:40px;color:var(--slate-light)">
                No eligible subjects. <a href="{{ route('curriculum.level-subjects', $arm->class_level_id) }}" style="color:var(--indigo)">Set up subject rules →</a>
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
