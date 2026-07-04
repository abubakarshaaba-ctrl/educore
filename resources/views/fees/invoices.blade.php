@extends('layouts.app')
@section('title', 'Invoices')
@section('page-title', 'Invoices')

@push('styles')
<style>
    .page-tabs { display: flex; gap: 4px; background: white; border: 1px solid var(--border); border-radius: 10px; padding: 4px; margin-bottom: 20px; width: fit-content; }
    .page-tab { padding: 7px 16px; border-radius: 7px; font-size: 13px; font-weight: 500; color: var(--slate); text-decoration: none; transition: all 150ms; }
    .page-tab.active { background: var(--indigo); color: white; }
    .page-tab:hover:not(.active) { background: #F1F5F9; }

    .summary-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
    .summary-card { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px; }
    .summary-label { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
    .summary-value { font-size: 22px; font-weight: 700; color: var(--midnight); letter-spacing: -0.02em; }
    .summary-sub { font-size: 12px; color: var(--slate-light); margin-top: 3px; }

    .filters { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
    .filter-group { display: flex; flex-direction: column; gap: 5px; }
    .filter-label { font-size: 11px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: 0.05em; }
    .filter-control { padding: 7px 12px; font-size: 13px; font-family: inherit; border: 1px solid var(--border); border-radius: 7px; color: var(--midnight); background: #F8FAFC; outline: none; min-width: 180px; }
    .filter-control:focus { border-color: var(--indigo); }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 13px; font-weight: 600; font-family: inherit; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: background 150ms; }
    .btn-primary { background: var(--indigo); color: white; }
    .btn-primary:hover { background: #1D4ED8; }
    .btn-ghost { background: white; color: var(--midnight); border: 1px solid var(--border); }

    .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--emerald); margin-bottom: 16px; }

    .card { background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead th { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 16px; text-align: left; background: #F8FAFC; border-bottom: 1px solid var(--border); }
    tbody td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--midnight); vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #F8FAFC; }

    .badge { display: inline-flex; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .badge-success { background: #ECFDF5; color: var(--emerald); }
    .badge-warning { background: #FFFBEB; color: var(--amber); }
    .badge-error   { background: #FEF2F2; color: var(--crimson); }

    .action-link { font-size: 12px; font-weight: 600; color: var(--indigo); text-decoration: none; }
    .action-link:hover { text-decoration: underline; }

    .progress-bar-wrap { width: 80px; height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden; }
    .progress-bar { height: 100%; border-radius: 3px; background: var(--emerald); }

    .empty-state { text-align: center; padding: 50px 20px; color: var(--slate-light); }
    .empty-state h3 { font-size: 15px; font-weight: 600; color: var(--slate); margin-bottom: 6px; }

    @media(max-width:1024px) { .summary-grid { grid-template-columns: repeat(2,1fr); } }
</style>
@endpush

@section('content')

<div class="page-tabs">
    <a href="{{ route('fees.subaccounts') }}" class="page-tab">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="page-tab">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="page-tab">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="page-tab active">Invoices</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

{{-- Summary --}}
<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-label">Total Billed</div>
        <div class="summary-value">&#8358;{{ number_format($summary['total']) }}</div>
        <div class="summary-sub">All invoices</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Collected</div>
        <div class="summary-value" style="color:var(--emerald)">&#8358;{{ number_format($summary['collected']) }}</div>
        <div class="summary-sub">{{ $summary['paid'] }} paid invoices</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Outstanding</div>
        <div class="summary-value" style="color:var(--crimson)">&#8358;{{ number_format($summary['total'] - $summary['collected']) }}</div>
        <div class="summary-sub">{{ $summary['unpaid'] }} unpaid</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Collection Rate</div>
        <div class="summary-value">{{ $summary['total'] > 0 ? round(($summary['collected'] / $summary['total']) * 100) : 0 }}%</div>
        <div class="summary-sub">Of total billed</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET">
    <div class="filters">
        <div class="filter-group">
            <span class="filter-label">Search</span>
            <input type="text" name="search" class="filter-control" placeholder="Student name or admission no..." value="{{ request('search') }}">
        </div>
        <div class="filter-group">
            <span class="filter-label">Term</span>
            <select name="term_id" class="filter-control">
                <option value="">All Terms</option>
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ request('term_id') == $term->id ? 'selected' : '' }}>
                        {{ $term->name }} — {{ $term->session->name ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Status</span>
            <select name="status" class="filter-control">
                <option value="">All Status</option>
                <option value="unpaid"         {{ request('status') === 'unpaid'         ? 'selected' : '' }}>Unpaid</option>
                <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>Partial</option>
                <option value="paid"           {{ request('status') === 'paid'           ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        @if(request()->hasAny(['search','term_id','status']))
            <a href="{{ route('fees.invoices') }}" class="btn btn-ghost">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div class="card">
    @if($invoices->count())
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Invoice No.</th>
                <th>Student</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Progress</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            @php $pct = $invoice->total_amount > 0 ? ($invoice->amount_paid / $invoice->total_amount) * 100 : 0; @endphp
            <tr>
                <td style="font-family:monospace;font-size:12px">{{ $invoice->invoice_number }}</td>
                <td>
                    <strong>{{ optional($invoice->student)->full_name }}</strong><br>
                    <small style="color:var(--slate-light)">{{ optional($invoice->term)->name }}</small>
                </td>
                <td>&#8358;{{ number_format($invoice->total_amount) }}</td>
                <td style="color:var(--emerald);font-weight:600">&#8358;{{ number_format($invoice->amount_paid) }}</td>
                <td style="color:var(--crimson);font-weight:600">&#8358;{{ number_format($invoice->balance) }}</td>
                <td>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar" style="width:{{ min($pct,100) }}%"></div>
                    </div>
                </td>
                <td>
                    @if($invoice->status === 'paid')
                        <span class="badge badge-success">Paid</span>
                    @elseif($invoice->status === 'partially_paid')
                        <span class="badge badge-warning">Partial</span>
                    @else
                        <span class="badge badge-error">Unpaid</span>
                    @endif
                </td>
                <td><a href="{{ route('fees.invoices.show', $invoice) }}" class="action-link">View →</a></td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    @else
    <div class="empty-state">
        <h3>No invoices found</h3>
        <p>Generate invoices from the Fee Structures tab.</p>
    </div>
    @endif
</div>

@endsection
