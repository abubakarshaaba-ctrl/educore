@extends('layouts.app')
@section('title','Leave Management')
@section('page-title', $isAdmin ? 'Staff Leave Requests' : 'My Leave Requests')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.fr{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;font-family:inherit}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;padding:5px 10px;font-size:11px}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 10px;font-size:11px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border)}
tr:last-child td{border:none}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.b-pending{background:#FFFBEB;color:#D97706}.b-approved{background:#ECFDF5;color:#059669}
.b-rejected{background:#FEF2F2;color:#DC2626}.b-cancelled{background:#F1F5F9;color:#475569}
@media(max-width:700px){.fr{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="card">
    <div class="ch">Request Leave</div>
    <div class="cb">
        <form method="POST" action="{{ route('leave.store') }}">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Leave Type *</label>
                    <select name="leave_type" class="fc" required>
                        <option value="annual">Annual</option><option value="sick">Sick</option>
                        <option value="maternity">Maternity</option><option value="paternity">Paternity</option>
                        <option value="compassionate">Compassionate</option><option value="unpaid">Unpaid</option><option value="other">Other</option>
                    </select>
                </div>
                <div class="fg"><label class="fl">Start Date *</label><input type="date" name="start_date" class="fc" required></div>
                <div class="fg"><label class="fl">End Date *</label><input type="date" name="end_date" class="fc" required></div>
            </div>
            <div class="fg"><label class="fl">Reason</label><textarea name="reason" class="fc" rows="2"></textarea></div>
            <button type="submit" class="btn btn-p">Submit Request</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">{{ $isAdmin ? 'All Leave Requests' : 'My Requests' }}</div>
    <div style="overflow-x:auto"><table>
        <thead><tr>@if($isAdmin)<th>Staff</th>@endif<th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th>Reviewed By</th><th></th></tr></thead>
        <tbody>
        @forelse($requests as $r)
        <tr>
            @if($isAdmin)<td>{{ optional($r->user)->name }}</td>@endif
            <td>{{ ucfirst($r->leave_type) }}</td>
            <td>{{ $r->start_date->format('d M Y') }} – {{ $r->end_date->format('d M Y') }}</td>
            <td>{{ $r->days_requested }}</td>
            <td><span class="badge b-{{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
            <td>{{ optional($r->reviewer)->name ?? '—' }}</td>
            <td>
                <div style="display:flex;gap:5px">
                @if($isAdmin && $r->status === 'pending')
                <form method="POST" action="{{ route('leave.approve', $r) }}">@csrf @method('PATCH')<button class="btn btn-g">Approve</button></form>
                <form method="POST" action="{{ route('leave.reject', $r) }}">@csrf @method('PATCH')<button class="btn btn-danger">Reject</button></form>
                @endif
                @if(!$isAdmin && $r->status === 'pending')
                <form method="POST" action="{{ route('leave.cancel', $r) }}" onsubmit="return confirm('Cancel this request?')">@csrf @method('PATCH')<button class="btn btn-danger">Cancel</button></form>
                @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="{{ $isAdmin ? 7 : 6 }}" style="text-align:center;padding:30px;color:#94A3B8">No leave requests yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div style="padding:14px">{{ $requests->links() }}</div>
</div>
@endsection
