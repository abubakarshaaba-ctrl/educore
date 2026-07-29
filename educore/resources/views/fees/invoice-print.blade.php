<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
*{box-sizing:border-box}
:root{--navy:#071E45;--gold:#D79A21;--ink:#101828;--muted:#667085;--line:#DCE3ED;--green:#16794B;--red:#B42318}
body{margin:0;background:#EEF2F7;color:var(--ink);font-family:Arial,Helvetica,sans-serif;font-size:13px}
.toolbar{max-width:900px;margin:20px auto 0;display:flex;justify-content:flex-end;gap:8px}
.toolbar button{border:0;border-radius:8px;background:var(--navy);color:white;padding:10px 18px;font-weight:700;cursor:pointer}
.sheet{width:min(900px,calc(100% - 24px));min-height:1120px;margin:12px auto 28px;background:white;padding:44px 52px;box-shadow:0 14px 40px rgba(7,30,69,.12);position:relative;overflow:hidden}
.sheet:before{content:"";position:absolute;inset:0 0 auto;height:8px;background:linear-gradient(90deg,var(--navy) 0 72%,var(--gold) 72%)}
.header{display:flex;align-items:center;justify-content:space-between;gap:24px;padding-bottom:24px;border-bottom:2px solid var(--navy)}
.brand{display:flex;align-items:center;gap:14px}
.logo{width:64px;height:64px;border-radius:14px;border:1px solid var(--line);display:flex;align-items:center;justify-content:center;overflow:hidden;background:#F8FAFC;color:var(--navy);font-size:24px;font-weight:800}
.logo img{width:100%;height:100%;object-fit:contain}
.school{font-size:21px;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:.02em}
.school-meta{font-size:11px;color:var(--muted);line-height:1.7;margin-top:4px;max-width:480px}
.doc-title{text-align:right}.doc-title h1{margin:0;color:var(--navy);font-size:27px;letter-spacing:.08em}.doc-number{color:var(--gold);font-weight:800;margin-top:7px}
.status{display:inline-block;margin-top:8px;padding:5px 10px;border-radius:20px;font-size:10px;font-weight:800;text-transform:uppercase;background:#ECFDF3;color:var(--green)}
.status.due{background:#FFF4ED;color:var(--red)}
.meta{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:12px;margin:28px 0}
.meta-card{background:#F7F9FC;border:1px solid var(--line);border-radius:10px;padding:13px 14px;min-width:0}
.label{font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:5px}
.value{font-size:13px;font-weight:700;color:var(--navy);line-height:1.45}
table{width:100%;border-collapse:collapse}
th{background:var(--navy);color:white;font-size:10px;text-transform:uppercase;letter-spacing:.08em;text-align:left;padding:12px}
td{padding:13px 12px;border-bottom:1px solid var(--line)}
.money{text-align:right;white-space:nowrap}
.summary{width:360px;margin:22px 0 28px auto;border:1px solid var(--line);border-radius:10px;overflow:hidden}
.summary-row{display:flex;justify-content:space-between;gap:20px;padding:10px 14px;background:#F8FAFC;border-bottom:1px solid var(--line)}
.summary-row:last-child{border:0;background:var(--navy);color:white;font-size:15px;font-weight:800}
.summary-row.paid strong{color:var(--green)}
.payments-title{font-size:13px;color:var(--navy);font-weight:800;margin:28px 0 10px;padding-bottom:8px;border-bottom:2px solid var(--gold)}
.payment-grid{display:grid;grid-template-columns:1.1fr 1fr .8fr .8fr;gap:0;border:1px solid var(--line);border-radius:8px;overflow:hidden}
.payment-grid>div{padding:10px;border-bottom:1px solid var(--line);font-size:11px}
.payment-grid .head{font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:800;background:#F8FAFC}
.empty{padding:15px;border:1px dashed var(--line);border-radius:8px;color:var(--muted);text-align:center}
.footer{margin-top:56px;padding-top:16px;border-top:1px solid var(--line);display:flex;justify-content:space-between;align-items:flex-end;gap:20px;color:var(--muted);font-size:10px}
.stamp{width:120px;height:58px;border:2px solid rgba(7,30,69,.28);border-radius:50%;display:flex;align-items:center;justify-content:center;text-align:center;color:rgba(7,30,69,.65);font-size:9px;font-weight:800;text-transform:uppercase;transform:rotate(-5deg)}
@media(max-width:640px){.sheet{padding:32px 20px;min-height:auto}.header{align-items:flex-start;flex-direction:column}.doc-title{text-align:left}.meta{grid-template-columns:1fr}.summary{width:100%}.payment-grid{grid-template-columns:1fr 1fr}.payment-grid .head{display:none}.toolbar{margin-right:12px}.school{font-size:17px}}
@media print{
    @page{size:A4;margin:9mm}
    body{background:white}.toolbar{display:none}.sheet{width:100%;min-height:0;margin:0;padding:24px 28px;box-shadow:none}
}
</style>
</head>
<body>
<div class="toolbar"><button type="button" onclick="window.print()">Print / Save PDF</button></div>
<main class="sheet">
    <header class="header">
        <div class="brand">
            <div class="logo">
                @if($tenant?->logo_path && file_exists(storage_path('app/public/'.$tenant->logo_path)))
                    <img src="{{ asset('storage/'.$tenant->logo_path) }}" alt="">
                @else
                    {{ strtoupper(substr($tenant?->name ?? 'E',0,1)) }}
                @endif
            </div>
            <div>
                <div class="school">{{ $tenant?->name ?? 'School' }}</div>
                <div class="school-meta">
                    {{ $tenant?->address }}
                    @if($tenant?->phone)<br>{{ $tenant->phone }}@endif
                    @if($tenant?->email) · {{ $tenant->email }}@endif
                </div>
            </div>
        </div>
        <div class="doc-title">
            <h1>INVOICE</h1>
            <div class="doc-number">{{ $invoice->invoice_number }}</div>
            <span class="status {{ $invoice->balance > 0 ? 'due' : '' }}">{{ $invoice->balance > 0 ? 'Balance Outstanding' : 'Paid in Full' }}</span>
        </div>
    </header>

    <section class="meta">
        <div class="meta-card">
            <div class="label">Billed To</div>
            <div class="value">{{ $invoice->student?->full_name }}<br>{{ $invoice->student?->admission_number }}</div>
        </div>
        <div class="meta-card">
            <div class="label">Class / Term</div>
            <div class="value">{{ $invoice->student?->currentClassArm?->classLevel?->name }} {{ $invoice->student?->currentClassArm?->name }}<br>{{ $invoice->term?->name }}</div>
        </div>
        <div class="meta-card">
            <div class="label">Due Date</div>
            <div class="value">{{ $invoice->due_date?->format('d F Y') ?? 'Not specified' }}</div>
        </div>
    </section>

    <table>
        <thead><tr><th>Description</th><th class="money">Amount</th></tr></thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr><td>{{ $item->description }}</td><td class="money">₦{{ number_format($item->amount,2) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row"><span>Total billed</span><strong>₦{{ number_format($invoice->total_amount,2) }}</strong></div>
        <div class="summary-row paid"><span>Amount paid</span><strong>₦{{ number_format($invoice->amount_paid,2) }}</strong></div>
        <div class="summary-row"><span>Unpaid balance</span><strong>₦{{ number_format($invoice->balance,2) }}</strong></div>
    </div>

    <div class="payments-title">Payment Record</div>
    @forelse($invoice->transactions as $transaction)
        @if($loop->first)
        <div class="payment-grid">
            <div class="head">Date</div><div class="head">Reference</div><div class="head">Method</div><div class="head money">Amount</div>
        @endif
            <div>{{ $transaction->paid_at?->format('d M Y') }}</div>
            <div>{{ $transaction->gateway_reference }}</div>
            <div>{{ ucfirst(str_replace('_',' ',$transaction->gateway)) }}</div>
            <div class="money">₦{{ number_format($transaction->amount_paid,2) }}</div>
        @if($loop->last)</div>@endif
    @empty
        <div class="empty">No successful payment has been recorded on this invoice.</div>
    @endforelse

    <footer class="footer">
        <div>
            Generated by EduCore School ERP on {{ now()->format('d F Y, g:i a') }}.<br>
            This document records payments received and any balance still outstanding.
        </div>
        <div class="stamp">{{ $tenant?->name }}<br>Accounts Copy</div>
    </footer>
</main>
</body>
</html>
