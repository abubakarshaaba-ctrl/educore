@extends('layouts.app')
@section('title','Student Transfers')
@section('page-title','Student Transfers')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.pg{display:grid;grid-template-columns:1fr 360px;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:16px}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms;width:100%;justify-content:center}
.btn-p{background:var(--indigo);color:white}.btn-g{background:var(--emerald);color:white}.btn-r{background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA}.btn-sm{padding:4px 10px;font-size:11px;width:auto}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-requested{background:#EFF6FF;color:var(--indigo)}.b-approved{background:#ECFDF5;color:var(--emerald)}.b-rejected{background:#FEF2F2;color:var(--crimson)}.b-completed{background:#F0FDF4;color:var(--emerald)}
.tabs{display:flex;gap:4px;margin-bottom:14px}
.tab{padding:8px 16px;font-size:13px;font-weight:600;border-radius:8px;background:white;border:1px solid var(--border);cursor:pointer;text-decoration:none;color:var(--slate)}
.tab.on{background:var(--indigo);color:white;border-color:var(--indigo)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:12.5px;color:var(--indigo);margin-bottom:16px}
@media(max-width:768px){.pg{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="info-box">
    🔁 Student transfers allow a student to move from one school to another in the SMS platform. The receiving school must approve the transfer before the student's records are moved.
</div>

<div class="pg">
  <div>
    <div class="card">
      <div class="ch">Incoming Transfer Requests</div>
      <div class="tbl"><table>
        <thead><tr><th>Student</th><th>From School</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($incoming as $transfer)
        <tr>
            <td><strong>{{ $transfer->student_name }}</strong><br><span style="font-size:11px;color:var(--slate-light)">{{ $transfer->admission_number }}</span></td>
            <td style="font-size:12px">School #{{ $transfer->from_tenant_id }}</td>
            <td style="font-size:11px">{{ \Carbon\Carbon::parse($transfer->created_at)->format('d M Y') }}</td>
            <td><span class="badge b-{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span></td>
            <td>
                @if($transfer->status === 'requested')
                <div style="display:flex;gap:6px">
                    <form method="POST" action="{{ route('students.transfers.approve', $transfer) }}" style="display:inline">@csrf
                        <button type="submit" class="btn btn-g btn-sm">Accept</button>
                    </form>
                    <form method="POST" action="{{ route('students.transfers.reject', $transfer) }}" style="display:inline">@csrf
                        <button type="submit" class="btn btn-r btn-sm">Reject</button>
                    </form>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--slate-light)">No incoming transfers</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div>

    <div class="card">
      <div class="ch">Outgoing Transfer Requests</div>
      <div class="tbl"><table>
        <thead><tr><th>Student</th><th>To School</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($outgoing as $transfer)
        <tr>
            <td><strong>{{ $transfer->student_name }}</strong></td>
            <td style="font-size:12px">School #{{ $transfer->to_tenant_id }}</td>
            <td style="font-size:11px">{{ \Carbon\Carbon::parse($transfer->created_at)->format('d M Y') }}</td>
            <td><span class="badge b-{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--slate-light)">No outgoing transfers</td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="ch">Request Transfer</div>
      <div class="cb">
        <form method="POST" action="{{ route('students.transfers.request') }}">
        @csrf
        <div class="fg"><label class="fl">Student *</label>
            <select name="student_id" class="fc" required>
                <option value="">Select student</option>
                @foreach($activeStudents as $s)
                    <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg"><label class="fl">Transfer To (School) *</label>
            <select name="to_tenant_id" class="fc" required>
                <option value="">Select receiving school</option>
                @foreach($tenants as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg"><label class="fl">Reason</label><textarea name="reason" class="fc" rows="3" placeholder="Why is the student transferring?"></textarea></div>
        <button type="submit" class="btn btn-p">Submit Transfer Request</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection