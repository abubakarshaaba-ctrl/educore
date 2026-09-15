@extends('layouts.app')
@section('title', 'Invoices')
@section('page-title', 'Invoices')

@push('styles')
<style>
    .invoice-toolbar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
    .invoice-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
    .invoice-kpi{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px}
    .invoice-kpi-label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
    .invoice-kpi-value{font-size:22px;font-weight:800;color:var(--midnight);letter-spacing:-.02em}
    .invoice-kpi-value.success{color:var(--emerald)}
    .invoice-kpi-value.danger{color:var(--crimson)}
    .invoice-kpi-sub{font-size:12px;color:var(--slate-light);margin-top:3px}
    .invoice-filters{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
    .invoice-filter{display:flex;flex-direction:column;gap:6px;min-width:180px}
    .invoice-filter.search{min-width:min(300px,100%);flex:1}
    .invoice-progress{width:88px;height:7px;background:#E2E8F0;border-radius:999px;overflow:hidden}
    .invoice-progress>span{display:block;height:100%;border-radius:999px;background:var(--emerald)}
    .invoice-number{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12px}
    .invoice-student small{display:block;color:var(--slate-light);margin-top:2px}
    .invoice-money-paid{color:var(--emerald);font-weight:700}
    .invoice-money-balance{color:var(--crimson);font-weight:700}
    @media(max-width:1024px){.invoice-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:640px){.invoice-summary{grid-template-columns:1fr}.invoice-filter,.invoice-filter.search{min-width:100%;width:100%}.invoice-filters .btn{width:100%;justify-content:center}}
</style>
@endpush

@section('content')
<div class="page-tabs invoice-toolbar" role="navigation" aria-label="Fee management sections">
    <a href="{{ route('fees.subaccounts') }}" class="page-tab">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="page-tab">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="page-tab">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="page-tab active" aria-current="page">Invoices</a>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

<div class="invoice-summary" aria-label="Invoice summary">
    <div class="invoice-kpi">
        <div class="invoice-kpi-label">Total billed</div>
        <div class="invoice-kpi-value">&#8358;{{ number_format($summary['total']) }}</div>
        <div class="invoice-kpi-sub">All invoices</div>
    </div>
    <div class="invoice-kpi">
        <div class="invoice-kpi-label">Collected</div>
        <div class="invoice-kpi-value success">&#8358;{{ number_format($summary['collected']) }}</div>
        <div class="invoice-kpi-sub">{{ $summary['paid'] }} paid invoices</div>
    </div>
    <div class="invoice-kpi">
        <div class="invoice-kpi-label">Outstanding</div>
        <div class="invoice-kpi-value danger">&#8358;{{ number_format($summary['total'] - $summary['collected']) }}</div>
        <div class="invoice-kpi-sub">{{ $summary['unpaid'] }} unpaid</div>
    </div>
    <div class="invoice-kpi">
        <div class="invoice-kpi-label">Collection rate</div>
        <div class="invoice-kpi-value">{{ $summary['total'] > 0 ? round(($summary['collected'] / $summary['total']) * 100) : 0 }}%</div>
        <div class="invoice-kpi-sub">Of total billed</div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="cb">
        <form method="GET" class="invoice-filters" aria-label="Filter invoices">
            <div class="invoice-filter search">
                <label class="form-label" for="invoice-search">Search</label>
                <input id="invoice-search" type="search" name="search" class="form-control" placeholder="Student name or admission no..." value="{{ request('search') }}">
            </div>
            <div class="invoice-filter">
                <label class="form-label" for="invoice-term">Term</label>
                <select id="invoice-term" name="term_id" class="form-control">
                    <option value="">All terms</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ request('term_id') == $term->id ? 'selected' : '' }}>
                            {{ $term->name }} — {{ $term->session->name ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="invoice-filter">
                <label class="form-label" for="invoice-status">Status</label>
                <select id="invoice-status" name="status" class="form-control">
                    <option value="">All statuses</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>Partial</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['search','term_id','status']))
                <a href="{{ route('fees.invoices') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="ch">
        <div>
            <strong>Invoice register</strong>
            <div style="font-size:12px;color:var(--slate);margin-top:2px">Review billing, collection progress and outstanding balances.</div>
        </div>
    </div>

    @if($invoices->count())
        <div class="tbl">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Invoice no.</th>
                        <th scope="col">Student</th>
                        <th scope="col">Total</th>
                        <th scope="col">Paid</th>
                        <th scope="col">Balance</th>
                        <th scope="col">Progress</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        @php $pct = $invoice->total_amount > 0 ? ($invoice->amount_paid / $invoice->total_amount) * 100 : 0; @endphp
                        <tr>
                            <td class="invoice-number">{{ $invoice->invoice_number }}</td>
                            <td class="invoice-student">
                                <strong>{{ optional($invoice->student)->full_name }}</strong>
                                <small>{{ optional($invoice->term)->name }}</small>
                            </td>
                            <td>&#8358;{{ number_format($invoice->total_amount) }}</td>
                            <td class="invoice-money-paid">&#8358;{{ number_format($invoice->amount_paid) }}</td>
                            <td class="invoice-money-balance">&#8358;{{ number_format($invoice->balance) }}</td>
                            <td>
                                <div class="invoice-progress" role="progressbar" aria-label="Payment progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(round($pct),100) }}">
                                    <span style="width:{{ min($pct,100) }}%"></span>
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
                            <td><a href="{{ route('fees.invoices.show', $invoice) }}" class="btn btn-ghost">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <h3>No invoices found</h3>
            <p>{{ request()->hasAny(['search','term_id','status']) ? 'Try adjusting your filters.' : 'Generate invoices from Fee Structures to begin billing students.' }}</p>
            @if(request()->hasAny(['search','term_id','status']))
                <a href="{{ route('fees.invoices') }}" class="btn btn-ghost">Clear filters</a>
            @endif
        </div>
    @endif
</div>
@endsection
