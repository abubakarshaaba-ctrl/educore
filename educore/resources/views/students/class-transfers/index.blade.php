@extends('layouts.app')

@section('title', 'Interclass Transfers')
@section('page-title', 'Interclass Transfers')

@push('styles')
<style>
.stat{background:white;border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center;flex:1;min-width:110px}
.stat .val{font-size:22px;font-weight:900;color:var(--midnight)}
.stat .lbl{font-size:10.5px;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px 20px}
.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.04em}
.fc{border:1px solid var(--border);border-radius:8px;padding:9px 12px;font:inherit;font-size:13px;min-width:160px;background:#F8FAFC}
.fc:focus{outline:none;border-color:var(--indigo);background:white}
.filters-grid{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-sm{padding:6px 12px;font-size:11px}
table{width:100%;border-collapse:collapse;font-size:12.5px}
th{padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);border-bottom:1px solid var(--border);background:#F8FAFC}
td{padding:11px 14px;border-bottom:1px solid #F8FAFC;color:var(--midnight)}
.badge{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:var(--indigo-bg);color:var(--indigo)}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:16px}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div style="font-size:12px;color:var(--slate-light)">Move active students between class arms within this school.</div>
    @if($canRequest)
    <a href="{{ route('students.class-transfers.create') }}" class="btn btn-p">+ Request Transfer</a>
    @endif
</div>

@if(session('success'))
<div class="alert-success">✓ {{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert-error">{{ $errors->first() }}</div>
@endif

{{-- Status summary cards --}}
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    @foreach($statuses as $status)
    <div class="stat">
        <div class="val">{{ $summary[$status] ?? 0 }}</div>
        <div class="lbl">{{ str_replace('_',' ',$status) }}</div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px">
    <div class="cb">
        <form method="GET" action="{{ route('students.class-transfers.index') }}" class="filters-grid">
            <div class="fg">
                <label class="fl">Search</label>
                <input type="search" name="search" class="fc" value="{{ $filters['search'] ?? '' }}" placeholder="Name or admission number">
            </div>
            <div class="fg">
                <label class="fl">Status</label>
                <select name="status" class="fc">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label class="fl">Class Arm</label>
                <select name="class_arm_id" class="fc">
                    <option value="">All classes</option>
                    @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" @selected((string)($filters['class_arm_id'] ?? '') === (string)$arm->id)>{{ $arm->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label class="fl">Session</label>
                <select name="academic_session_id" class="fc">
                    <option value="">All sessions</option>
                    @foreach($sessions as $session)
                    <option value="{{ $session->id }}" @selected((string)($filters['academic_session_id'] ?? '') === (string)$session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label class="fl">Term</label>
                <select name="term_id" class="fc">
                    <option value="">All terms</option>
                    @foreach($terms as $term)
                    <option value="{{ $term->id }}" @selected((string)($filters['term_id'] ?? '') === (string)$term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-p">Filter</button>
            <a href="{{ route('students.class-transfers.index') }}" class="btn btn-g">Reset</a>
        </form>
    </div>
</div>

{{-- Results table --}}
<div class="card">
    <div class="ch">↔ Transfer Requests</div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>Student</th><th>From</th><th>To</th><th>Session / Term</th><th>Effective</th><th>Status</th><th>Requested By</th><th></th></tr>
        </thead>
        <tbody>
        @forelse($transfers as $transfer)
        <tr>
            <td>
                <div style="font-weight:700">{{ optional($transfer->student)->full_name }}</div>
                <div style="font-size:11px;color:var(--slate-light);font-family:monospace">{{ optional($transfer->student)->admission_number }}</div>
            </td>
            <td>{{ optional($transfer->fromClassArm)->full_name ?? 'Not set' }}</td>
            <td style="font-weight:700;color:var(--indigo)">{{ optional($transfer->toClassArm)->full_name ?? 'Not set' }}</td>
            <td>
                <div>{{ optional($transfer->academicSession)->name ?? 'Not set' }}</div>
                <div style="font-size:11px;color:var(--slate-light)">{{ optional($transfer->term)->name ?? 'Not set' }}</div>
            </td>
            <td>{{ optional($transfer->effective_date)->format('M d, Y') }}</td>
            <td><span class="badge">{{ ucwords(str_replace('_',' ',$transfer->status)) }}</span></td>
            <td style="font-size:12px">{{ optional($transfer->requestedBy)->name ?? 'System' }}</td>
            <td><a href="{{ route('students.class-transfers.show', $transfer) }}" class="btn btn-g btn-sm">View</a></td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--slate-light)">No interclass transfer records found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div style="padding:14px 20px">{{ $transfers->links() }}</div>
</div>

@endsection
