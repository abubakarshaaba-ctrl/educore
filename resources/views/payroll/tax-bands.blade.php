@extends('layouts.app')
@section('title','PAYE Tax Bands')
@section('page-title','PAYE Tax Bands')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.cb{padding:18px}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 10px;text-align:left}
tbody td{padding:6px 10px}
.fc{padding:7px 9px;font-size:12px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 16px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer}
.btn-p{background:var(--indigo);color:white}
.btn-gh{background:white;border:1px solid var(--border);color:var(--midnight)}
.btn-del{background:#FEF2F2;color:#DC2626;padding:6px 10px;font-size:11px}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
.alert-w{background:#FFFBEB;border:1px solid #FCD34D;border-radius:8px;padding:14px 16px;font-size:13px;color:#92400E;margin-bottom:16px;line-height:1.6}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:14px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif
<a href="{{ route('payroll.salary') }}" class="back">← Back to Salary Settings</a>

<div class="alert-w">
    <strong>⚠️ Please verify before relying on these for real payroll.</strong><br>
    These default bands reflect the Nigeria Tax Act 2025 (effective 1 Jan 2026) — 0% on the first
    ₦800,000, rising to 25% above ₦50,000,000 — but the exact thresholds between those two points
    were reported inconsistently across the sources available when these defaults were set.
    Please confirm the figures below with your accountant or the official FIRS/NRS PAYE schedule,
    and adjust if needed. Rates apply to <strong>annual</strong> income; pay slips divide the result by 12.
</div>

<div class="card">
    <div class="ch">Progressive Tax Bands {{ $usingDefaults ? '(showing defaults — not yet saved)' : '' }}</div>
    <div class="cb">
        <form method="POST" action="{{ route('payroll.tax-bands.save') }}">
            @csrf
            <div class="tbl"><table id="bandsTable">
                <thead><tr><th style="width:30%">From (₦/year)</th><th style="width:30%">To (₦/year, blank = no limit)</th><th style="width:20%">Rate (%)</th><th></th></tr></thead>
                <tbody id="bandsBody">
                    @foreach($bands as $i => $b)
                    <tr>
                        <td><input type="number" name="bands[{{ $i }}][lower_bound]" class="fc" value="{{ $b->lower_bound }}" step="0.01" min="0" required></td>
                        <td><input type="number" name="bands[{{ $i }}][upper_bound]" class="fc" value="{{ $b->upper_bound }}" step="0.01" min="0" placeholder="No limit"></td>
                        <td><input type="number" name="bands[{{ $i }}][rate_percent]" class="fc" value="{{ $b->rate_percent }}" step="0.01" min="0" max="100" required></td>
                        <td><button type="button" class="btn btn-del" onclick="this.closest('tr').remove()">Remove</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div style="margin-top:10px;display:flex;gap:10px">
                <button type="button" class="btn btn-gh" id="addBandBtn">+ Add Band</button>
                <button type="submit" class="btn btn-p">Save Tax Bands</button>
            </div>
        </form>
    </div>
</div>

<script>
let bandIndex = {{ count($bands) }};
document.getElementById('addBandBtn').addEventListener('click', function () {
    const body = document.getElementById('bandsBody');
    const i = bandIndex++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="number" name="bands[${i}][lower_bound]" class="fc" step="0.01" min="0" required></td>
        <td><input type="number" name="bands[${i}][upper_bound]" class="fc" step="0.01" min="0" placeholder="No limit"></td>
        <td><input type="number" name="bands[${i}][rate_percent]" class="fc" step="0.01" min="0" max="100" required></td>
        <td><button type="button" class="btn btn-del" onclick="this.closest('tr').remove()">Remove</button></td>
    `;
    body.appendChild(tr);
});
</script>
@endsection
