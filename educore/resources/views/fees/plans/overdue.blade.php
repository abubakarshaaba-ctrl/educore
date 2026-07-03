@extends('layouts.app')
@section('title','Overdue Installments')
@section('page-title','Overdue Installments')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-overdue{background:#FEF2F2;color:var(--crimson)}.b-partial{background:#FFFBEB;color:var(--amber)}.b-soon{background:#EFF6FF;color:var(--indigo)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-a{background:var(--amber);color:white}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px;text-align:center}
.sv{font-size:22px;font-weight:800}.sl{font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
</style>
@endpush
@section('content')
<a href="{{ route('fees.plans.index') }}" class="back">← Back to Plans</a>

<div class="stat-row">
    <div class="sc"><div class="sv" style="color:var(--crimson)">{{ $overdue->total() }}</div><div class="sl">Overdue</div></div>
    <div class="sc"><div class="sv" style="color:var(--amber)">{{ $dueSoon->count() }}</div><div class="sl">Due in 7 Days</div></div>
    <div class="sc"><div class="sv" style="color:var(--crimson)">₦{{ number_format($totalOverdue) }}</div><div class="sl">Total Overdue</div></div>
</div>

@if($dueSoon->count())
<div class="card">
    <div class="ch">
        Due in Next 7 Days
        <form method="POST" action="{{ route('fees.plans.reminders') }}">@csrf
            <input type="hidden" name="days" value="7">
            <button type="submit" class="btn btn-a">&#128241; Send Reminders</button>
        </form>
    </div>
    <div class="tbl"><table>
        <thead><tr><th>Student</th><th>Installment</th><th>Due Date</th><th>Amount Due</th><th>Balance</th></tr></thead>
        <tbody>
        @foreach($dueSoon as $inst)
        <tr>
            <td><strong>{{ optional(optional($inst->invoice)->student)->full_name }}</strong></td>
            <td>Installment {{ $inst->installment_number }}</td>
            <td style="color:var(--amber)"><strong>{{ $inst->due_date->format('d M Y') }}</strong></td>
            <td>₦{{ number_format($inst->amount_due) }}</td>
            <td style="font-weight:700">₦{{ number_format($inst->balance) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@endif

<div class="card">
    <div class="ch">Overdue Installments</div>
    <div class="tbl"><table>
        <thead><tr><th>Student</th><th>Invoice</th><th>Installment</th><th>Due Date</th><th>Balance</th><th>Days Late</th><th></th></tr></thead>
        <tbody>
        @forelse($overdue as $inst)
        <tr>
            <td><strong>{{ optional(optional($inst->invoice)->student)->full_name }}</strong></td>
            <td style="font-size:11px;font-family:monospace">{{ optional($inst->invoice)->invoice_number }}</td>
            <td>No. {{ $inst->installment_number }}</td>
            <td style="color:var(--crimson)">{{ $inst->due_date->format('d M Y') }}</td>
            <td style="font-weight:700;color:var(--crimson)">₦{{ number_format($inst->balance) }}</td>
            <td><span class="badge b-overdue">{{ $inst->due_date->diffInDays(now()) }} days</span></td>
            <td><a href="{{ route('fees.invoices.show', $inst->invoice_id) }}" class="btn btn-p">View</a></td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--slate-light)">No overdue installments</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $overdue->links() }}
</div>
@endsection
