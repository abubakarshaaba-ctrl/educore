@extends('layouts.app')
@section('title','Fee Reminders')
@section('page-title','Fee Reminder System')

@push('styles')
<style>
    .reminder-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}
    .reminder-stat{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
    .reminder-stat-label{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em}
    .reminder-stat-value{margin-top:5px;font-size:23px;font-weight:800;letter-spacing:-.02em;color:var(--brand-navy)}
    .reminder-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,380px);gap:20px;align-items:start}
    .reminder-card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,.05);overflow:hidden;margin-bottom:16px}
    .reminder-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 18px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft)}
    .reminder-head-title{font-size:14px;font-weight:800;color:var(--brand-navy)}
    .reminder-head-sub{margin-top:3px;font-size:12px;color:var(--slate)}
    .reminder-body{padding:18px}
    .select-all{display:inline-flex;align-items:center;gap:7px;min-height:36px;font-size:12px;font-weight:700;color:var(--brand-navy);cursor:pointer}
    .reminder-checkbox{width:16px;height:16px;accent-color:var(--brand-gold-dark);cursor:pointer}
    .reminder-table-wrap{overflow-x:auto}
    .reminder-table{width:100%;min-width:760px;border-collapse:collapse}
    .reminder-table th{padding:9px 14px;text-align:left;font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;background:#F8FAFC;border-bottom:1px solid var(--border)}
    .reminder-table td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle}
    .reminder-table tbody tr:hover td{background:#FAFCFF}.reminder-table tbody tr:last-child td{border-bottom:0}
    .guardian-meta{font-size:11px;line-height:1.5;color:var(--slate)}
    .money-paid{color:var(--emerald);font-weight:700}.money-balance{color:var(--crimson);font-weight:800}
    .reminder-pagination{padding:12px 16px;border-top:1px solid var(--border)}
    .form-group{margin-bottom:14px}.form-label{display:block;margin-bottom:6px;font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
    .form-control{width:100%;min-height:44px;padding:9px 12px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;color:var(--midnight);font:inherit;font-size:13px;outline:none}
    .form-control:focus{background:#fff;border-color:var(--brand-gold-dark);box-shadow:0 0 0 3px rgba(215,154,33,.16)}
    .btn-primary-reminder,.btn-secondary-reminder{display:inline-flex;align-items:center;justify-content:center;width:100%;min-height:44px;padding:9px 16px;border-radius:9px;font:inherit;font-size:13px;font-weight:800;cursor:pointer;text-decoration:none}
    .btn-primary-reminder{border:1px solid var(--brand-gold);background:var(--brand-gold);color:var(--brand-navy)}
    .btn-primary-reminder:hover{background:var(--brand-gold-dark);border-color:var(--brand-gold-dark)}
    .btn-secondary-reminder{border:1px solid var(--border);background:#fff;color:var(--brand-navy)}
    .btn-secondary-reminder:hover{background:var(--brand-navy-soft)}
    .reminder-help{margin:0 0 14px;font-size:12px;line-height:1.55;color:var(--slate)}
    .alert-success{margin-bottom:16px;padding:12px 16px;border:1px solid #A7F3D0;border-radius:9px;background:#ECFDF5;color:#047857;font-size:13px}
    .empty-state{padding:40px 20px;text-align:center;color:var(--slate-light)}
    .empty-state strong{display:block;margin-bottom:4px;color:var(--slate);font-size:14px}
    @media(max-width:1024px){.reminder-grid{grid-template-columns:1fr}}
    @media(max-width:720px){.reminder-stats{grid-template-columns:1fr}.reminder-head{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="alert-success" role="status">{{ session('success') }}</div>
@endif

<div class="reminder-stats">
    <div class="reminder-stat">
        <div class="reminder-stat-label">Total Outstanding</div>
        <div class="reminder-stat-value" style="color:var(--crimson)">₦{{ number_format($stats['total_outstanding']) }}</div>
    </div>
    <div class="reminder-stat">
        <div class="reminder-stat-label">Unpaid Invoices</div>
        <div class="reminder-stat-value" style="color:var(--amber)">{{ $stats['invoices_unpaid'] }}</div>
    </div>
    <div class="reminder-stat">
        <div class="reminder-stat-label">Reminders Sent Today</div>
        <div class="reminder-stat-value" style="color:var(--emerald)">{{ $stats['reminders_sent'] }}</div>
    </div>
</div>

<div class="reminder-grid">
    <div>
        <form method="POST" action="{{ route('fees.reminders.send') }}" id="reminder-form">
            @csrf
            <input type="hidden" name="channel" id="channel-input" value="sms">
            <input type="hidden" name="message" value="">

            <div class="reminder-card">
                <div class="reminder-head">
                    <div>
                        <div class="reminder-head-title">Outstanding Invoices</div>
                        <div class="reminder-head-sub">Select the invoices whose guardians should receive a reminder.</div>
                    </div>
                    <label class="select-all" for="select-all">
                        <input type="checkbox" id="select-all" class="reminder-checkbox"> Select all
                    </label>
                </div>

                @if($outstanding->count())
                <div class="reminder-table-wrap">
                    <table class="reminder-table">
                        <thead>
                            <tr>
                                <th scope="col" aria-label="Select"></th>
                                <th scope="col">Student</th>
                                <th scope="col">Guardian</th>
                                <th scope="col">Billed</th>
                                <th scope="col">Paid</th>
                                <th scope="col">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($outstanding as $inv)
                            @php $bal = $inv->total_amount - $inv->amount_paid; $guardian = optional($inv->student)->guardians?->first(); @endphp
                            <tr>
                                <td><input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" class="reminder-checkbox inv-chk" aria-label="Select invoice for {{ optional($inv->student)->full_name }}"></td>
                                <td><strong>{{ optional($inv->student)->full_name }}</strong></td>
                                <td>
                                    <div class="guardian-meta">
                                        {{ optional($guardian)->name ?: 'No guardian name' }}<br>
                                        {{ optional($guardian)->phone ?: 'No phone number' }}
                                    </div>
                                </td>
                                <td>₦{{ number_format($inv->total_amount) }}</td>
                                <td class="money-paid">₦{{ number_format($inv->amount_paid) }}</td>
                                <td class="money-balance">₦{{ number_format($bal) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <div class="empty-state"><strong>All fees are up to date</strong>No outstanding invoices require reminders.</div>
                @endif

                @if($outstanding->hasPages())
                    <div class="reminder-pagination">{{ $outstanding->links() }}</div>
                @endif
            </div>
        </form>
    </div>

    <div>
        <div class="reminder-card">
            <div class="reminder-head">
                <div>
                    <div class="reminder-head-title">Send Selected Reminders</div>
                    <div class="reminder-head-sub">Uses the invoices selected in the table.</div>
                </div>
            </div>
            <div class="reminder-body">
                <div class="form-group">
                    <label class="form-label" for="channel-select">Channel</label>
                    <select class="form-control" id="channel-select" onchange="document.getElementById('channel-input').value=this.value">
                        <option value="sms">SMS</option>
                        <option value="email">Email</option>
                        <option value="both">SMS and Email</option>
                    </select>
                </div>
                <p class="reminder-help">Only guardians linked to the selected outstanding invoices will be contacted.</p>
                <button type="submit" form="reminder-form" class="btn-primary-reminder">Send to Selected</button>
            </div>
        </div>

        <div class="reminder-card">
            <div class="reminder-head">
                <div>
                    <div class="reminder-head-title">Bulk Send</div>
                    <div class="reminder-head-sub">Contact every guardian with an unpaid invoice in the selected scope.</div>
                </div>
            </div>
            <div class="reminder-body">
                <form method="POST" action="{{ route('fees.reminders.bulk') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="bulk-term">Term</label>
                        <select name="term_id" id="bulk-term" class="form-control">
                            <option value="">All Terms</option>
                            @foreach($terms as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="bulk-channel">Channel</label>
                        <select name="channel" id="bulk-channel" class="form-control">
                            <option value="sms">SMS</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <p class="reminder-help">Bulk send can contact many guardians at once. Confirm the selected term and channel before proceeding.</p>
                    <button type="submit" class="btn-secondary-reminder" onclick="return confirm('Send to ALL guardians with outstanding fees?')">Send to All Unpaid</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const selectAll = document.getElementById('select-all');
if (selectAll) {
    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.inv-chk').forEach(cb => cb.checked = this.checked);
    });
}
</script>
@endsection
