@extends('layouts.app')
@section('title','Payroll')
@section('page-title','Payroll')
@push('styles')
<style>
.ph{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}tbody tr:hover td{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:12.5px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-sm{padding:4px 10px;font-size:11px}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-draft{background:#F1F5F9;color:var(--slate)}.b-approved{background:#FFFBEB;color:var(--amber)}.b-paid{background:#ECFDF5;color:var(--emerald)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<div class="ph">
    <div><h2 style="font-size:18px;font-weight:700">Payroll Management</h2></div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('payroll.salary') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">Salary Settings</a>
        <a href="{{ route('payroll.staff-deductions') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">Staff Deductions</a>
        <a href="{{ route('payroll.tax-bands') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">Tax Bands</a>
        <a href="{{ route('payroll.create') }}" class="btn btn-p">+ Generate Payroll</a>
    </div>
</div>
<div class="card">
    <div class="ch">Payroll Periods</div>
    <div class="tbl"><table>
        <thead><tr><th>Period</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Payment Date</th><th></th></tr></thead>
        <tbody>
        @forelse($periods as $period)
        <tr>
            <td><strong>{{ $period->title }}</strong><br><span style="font-size:11px;color:var(--slate-light)">{{ \Carbon\Carbon::parse($period->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }}</span></td>
            <td>₦{{ number_format($period->total_gross) }}</td>
            <td style="color:var(--crimson)">₦{{ number_format($period->total_deductions) }}</td>
            <td style="font-weight:700;color:var(--emerald)">₦{{ number_format($period->total_net) }}</td>
            <td><span class="badge b-{{ $period->status }}">{{ ucfirst($period->status) }}</span></td>
            <td style="font-size:11px">{{ $period->payment_date ? \Carbon\Carbon::parse($period->payment_date)->format('d M Y') : '—' }}</td>
            <td><a href="{{ route('payroll.show',$period) }}" class="btn btn-p btn-sm">View</a></td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--slate-light)">No payroll periods yet. Click "Generate Payroll" to start.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $periods->links() }}
</div>
@endsection