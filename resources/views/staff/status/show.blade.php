@extends('layouts.app')
@section('title', 'Staff Lifecycle Status')
@section('page-title', 'Staff Lifecycle Status')

@push('styles')
<style>
.page-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:18px}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-header{padding:14px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);font-weight:700;color:var(--midnight)}
.card-body{padding:18px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate)}
.control{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:13px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 14px;border-radius:8px;border:1px solid var(--border);font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}
.btn-primary{background:var(--indigo);color:#fff;border-color:var(--indigo)}
.btn-danger{background:#FEF2F2;color:var(--crimson);border-color:#FECACA}
.btn-ghost{background:#fff;color:var(--midnight)}
.alert{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson)}
.alert-info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1D4ED8}
.timeline{display:grid;gap:10px}
.timeline-item{border:1px solid var(--border);border-radius:10px;padding:12px}
@media(max-width:768px){.grid{grid-template-columns:1fr}.page-head{display:block}}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--slate);margin-bottom:4px">
            <a href="{{ route('staff.show', $staff) }}" style="color:var(--indigo);text-decoration:none">Staff</a> / {{ $staff->name }}
        </div>
        <h1 style="font-size:20px;font-weight:800;color:var(--midnight)">Employment Status</h1>
        <div style="font-size:13px;color:var(--slate)">Current status: <strong>{{ $staff->employmentStatusLabel() }}</strong></div>
    </div>
    <a href="{{ route('staff.show', $staff) }}" class="btn btn-ghost">Back to Profile</a>
</div>

@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
@if(session('success'))<div class="alert" style="background:#ECFDF5;border:1px solid #A7F3D0;color:#047857">{{ session('success') }}</div>@endif

<div class="card">
    <div class="card-header">Change Staff Lifecycle Status</div>
    <div class="card-body">
        <div class="alert alert-info">
            This action takes effect immediately. Exited staff will have login disabled and will be removed from future payroll, attendance and assignment selectors.
        </div>
        <div class="alert alert-info">
            Current operational impact:
            active salary settings {{ $impactSummary['salary_settings'] ?? 0 }},
            class/form-teacher assignments {{ $impactSummary['class_teacher_assignments'] ?? 0 }},
            active subject assignments {{ $impactSummary['subject_assignments'] ?? 0 }},
            timetable periods {{ $impactSummary['timetable_periods'] ?? 0 }},
            transport assignments {{ $impactSummary['transport_assignments'] ?? 0 }}.
            These records are preserved for history. Where a module has no effective-date fields, reassign future duties manually after exit.
        </div>

        @if($allowedDestinations)
        <form method="POST" action="{{ route('staff.status.update', $staff) }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div class="field">
                    <label class="label">New Status</label>
                    <select name="new_status" class="control" required>
                        <option value="">Select status...</option>
                        @foreach($allowedDestinations as $status)
                            <option value="{{ $status }}" @selected(old('new_status') === $status)>{{ $statusLabels[$status] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label">Effective / Last Working Date</label>
                    <input type="date" name="effective_date" class="control" value="{{ old('effective_date', now()->toDateString()) }}" required>
                </div>
            </div>
            <div class="field">
                <label class="label">Reason</label>
                <textarea name="reason" class="control" rows="4" required>{{ old('reason') }}</textarea>
            </div>
            <div class="field">
                <label class="label">Supporting Document</label>
                <input type="file" name="document" class="control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </div>
            <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;color:var(--slate);margin-bottom:14px">
                <input type="checkbox" name="confirmation" value="1" required>
                I understand this immediately disables this staff member's login and future active staff eligibility.
            </label>
            <button type="submit" class="btn btn-danger">Apply Status Change</button>
        </form>
        @else
            <div class="alert alert-info">No direct status change is available from the current employment status.</div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">Status History</div>
    <div class="card-body">
        @if($histories->count())
            <div class="timeline">
                @foreach($histories as $history)
                <div class="timeline-item">
                    <div style="font-weight:700;color:var(--midnight)">
                        {{ $statusLabels[$history->old_status] ?? 'None' }} -> {{ $statusLabels[$history->new_status] ?? $history->new_status }}
                    </div>
                    <div style="font-size:12px;color:var(--slate);margin-top:4px">
                        Effective {{ optional($history->effective_date)->format('d M Y') }} by {{ optional($history->changedBy)->name ?? 'Unknown' }}
                    </div>
                    <div style="font-size:13px;color:var(--midnight);margin-top:8px">{{ $history->reason }}</div>
                    @if($history->document_path)
                        <a href="{{ route('staff.status.document', $history) }}" class="btn btn-ghost" style="margin-top:10px">Download Document</a>
                    @endif
                </div>
                @endforeach
            </div>
        @else
            <div style="font-size:13px;color:var(--slate)">No staff status history has been recorded.</div>
        @endif
    </div>
</div>
@endsection
