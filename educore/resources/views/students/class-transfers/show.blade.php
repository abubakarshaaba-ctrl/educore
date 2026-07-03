@extends('layouts.app')

@section('title', 'Transfer Request #'.$transfer->id)
@section('page-title', 'Transfer Request')

@push('styles')
<style>
.grid{display:grid;grid-template-columns:1fr 360px;gap:18px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
.info-row{display:flex;justify-content:space-between;gap:16px;padding:9px 0;border-bottom:1px solid #F8FAFC;font-size:13px}
.info-row:last-child{border-bottom:none}
.info-key{color:var(--slate-light);font-weight:500;flex-shrink:0}
.info-val{color:var(--midnight);font-weight:700;text-align:right}
table{width:100%;border-collapse:collapse;font-size:12px}
th{padding:9px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);border-bottom:1px solid var(--border);background:#F8FAFC}
td{padding:9px 12px;border-bottom:1px solid #F8FAFC;color:var(--midnight)}
.badge{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:var(--indigo-bg);color:var(--indigo)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;width:100%}
.btn-success{background:#ECFDF5;color:var(--emerald);border:1px solid #A7F3D0}
.btn-success:hover{background:var(--emerald);color:white}
.btn-danger-outline{background:white;color:var(--crimson);border:1px solid #FECACA}
.btn-danger-outline:hover{background:var(--crimson);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border);width:auto}
.fc{width:100%;padding:10px 13px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;margin-bottom:12px;resize:vertical}
.fc:focus{outline:none;border-color:var(--indigo);background:white}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:16px}
.alert-final{background:#F1F5F9;border:1px solid var(--border);border-radius:8px;padding:14px 16px;font-size:13px;color:var(--slate)}
@media(max-width:960px){.grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div>
        <div style="font-size:16px;font-weight:800;color:var(--midnight)">Transfer Request #{{ $transfer->id }}</div>
        <div style="margin-top:4px"><span class="badge">{{ ucwords(str_replace('_',' ',$transfer->status)) }}</span></div>
    </div>
    <a href="{{ route('students.class-transfers.index') }}" class="btn btn-g" style="width:auto">← Back to Transfers</a>
</div>

@if(session('success'))
<div class="alert-success">✓ {{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="grid">
    <div>
        {{-- Transfer details --}}
        <div class="card">
            <div class="ch">↔ Transfer Details</div>
            <div class="cb">
                <div class="info-row">
                    <span class="info-key">Student</span>
                    <span class="info-val">
                        {{ optional($transfer->student)->full_name }}
                        <div style="font-size:11px;color:var(--slate-light);font-weight:500">{{ optional($transfer->student)->admission_number }}</div>
                    </span>
                </div>
                <div class="info-row"><span class="info-key">Current Class</span><span class="info-val">{{ optional($transfer->fromClassArm)->full_name ?? 'Not set' }}</span></div>
                <div class="info-row"><span class="info-key">Destination Class</span><span class="info-val" style="color:var(--indigo)">{{ optional($transfer->toClassArm)->full_name ?? 'Not set' }}</span></div>
                <div class="info-row"><span class="info-key">Academic Session</span><span class="info-val">{{ optional($transfer->academicSession)->name ?? 'Not set' }}</span></div>
                <div class="info-row"><span class="info-key">Term</span><span class="info-val">{{ optional($transfer->term)->name ?? 'Not set' }}</span></div>
                <div class="info-row"><span class="info-key">Effective Date</span><span class="info-val">{{ optional($transfer->effective_date)->format('M d, Y') }}</span></div>
                <div class="info-row"><span class="info-key">Requested By</span><span class="info-val">{{ optional($transfer->requestedBy)->name ?? 'System' }}</span></div>
                <div class="info-row"><span class="info-key">Reason</span><span class="info-val">{{ $transfer->reason }}</span></div>
                @if($transfer->supporting_document)
                <div class="info-row">
                    <span class="info-key">Supporting Document</span>
                    <span class="info-val"><a href="{{ route('students.class-transfers.document', $transfer) }}" target="_blank" rel="noopener" style="color:var(--indigo)">📄 Download</a></span>
                </div>
                @endif
            </div>
        </div>

        {{-- Audit history --}}
        <div class="card">
            <div class="ch">🕒 Audit History</div>
            <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Date</th><th>Action</th><th>Actor</th><th>Reason</th></tr></thead>
                <tbody>
                @forelse($audits as $audit)
                <tr>
                    <td>{{ optional($audit->created_at)->format('M d, Y H:i') }}</td>
                    <td style="text-transform:capitalize">{{ str_replace('_',' ',$audit->action) }}</td>
                    <td>{{ optional($audit->actor)->name ?? 'System' }}</td>
                    <td>{{ $audit->reason ?: 'Not specified' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:18px;color:var(--slate-light)">No audit records found.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div>
        {{-- Decision history --}}
        <div class="card">
            <div class="ch">📋 Decision History</div>
            <div class="cb">
                <div class="info-row"><span class="info-key">Approved By</span><span class="info-val">{{ optional($transfer->approvedBy)->name ?? 'Not approved' }}</span></div>
                <div class="info-row"><span class="info-key">Approved At</span><span class="info-val">{{ optional($transfer->approved_at)->format('M d, Y H:i') ?? 'Not approved' }}</span></div>
                <div class="info-row"><span class="info-key">Completed At</span><span class="info-val">{{ optional($transfer->completed_at)->format('M d, Y H:i') ?? 'Not completed' }}</span></div>
                <div class="info-row"><span class="info-key">Rejected By</span><span class="info-val">{{ optional($transfer->rejectedBy)->name ?? 'Not rejected' }}</span></div>
                <div class="info-row"><span class="info-key">Rejection Reason</span><span class="info-val">{{ $transfer->rejection_reason ?: 'Not rejected' }}</span></div>
                <div class="info-row"><span class="info-key">Cancelled By</span><span class="info-val">{{ optional($transfer->cancelledBy)->name ?? 'Not cancelled' }}</span></div>
                <div class="info-row"><span class="info-key">Cancellation Reason</span><span class="info-val">{{ $transfer->cancellation_reason ?: 'Not cancelled' }}</span></div>
            </div>
        </div>

        @if($transfer->status === $transferPending)

            @if($canApprove)
            <div class="card">
                <div class="cb">
                    <form method="POST" action="{{ route('students.class-transfers.approve', $transfer) }}">
                        @csrf
                        <button type="submit" class="btn btn-success">✓ Approve and Complete Transfer</button>
                    </form>
                </div>
            </div>
            @endif

            @if($canReject)
            <div class="card">
                <div class="ch">✗ Reject Request</div>
                <div class="cb">
                    <form method="POST" action="{{ route('students.class-transfers.reject', $transfer) }}">
                        @csrf
                        <textarea name="rejection_reason" rows="3" class="fc" required placeholder="Rejection reason"></textarea>
                        <button type="submit" class="btn btn-danger-outline">Reject</button>
                    </form>
                </div>
            </div>
            @endif

            @if($canCancel)
            <div class="card">
                <div class="ch">⊘ Cancel Request</div>
                <div class="cb">
                    <form method="POST" action="{{ route('students.class-transfers.cancel', $transfer) }}">
                        @csrf
                        <textarea name="cancellation_reason" rows="3" class="fc" required placeholder="Cancellation reason"></textarea>
                        <button type="submit" class="btn btn-g" style="width:100%">Cancel Request</button>
                    </form>
                </div>
            </div>
            @endif

        @else
        <div class="alert-final">This transfer request is final and cannot be processed again.</div>
        @endif
    </div>
</div>

@endsection
