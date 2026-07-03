@extends('layouts.app')
@section('title','Salary Settings')
@section('page-title','Salary Settings')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:8px 10px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
.fc{padding:7px 9px;font-size:12px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms}
.fc:focus{border-color:var(--indigo);background:white}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-sm{padding:4px 10px;font-size:11px}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:14px}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">{{ session('success') }}</div>@endif
<a href="{{ route('payroll.index') }}" class="back">← Back to Payroll</a>
<div class="card" style="overflow-x:auto">
    <div class="ch">Configure Staff Salaries</div>
    <table style="min-width:1100px">
        <thead><tr><th>Staff</th><th>Role</th><th>Basic (₦)</th><th>Housing (₦)</th><th>Transport (₦)</th><th>Other (₦)</th><th>Annual Rent (₦)</th><th>Bank</th><th>Acct No.</th><th>Acct Name</th><th>TIN</th><th>BVN</th><th>NIN</th><th></th></tr></thead>
        <tbody>
        @foreach($staff as $s)
        @php $ss = $settings[$s->id] ?? null; @endphp
        <form method="POST" action="{{ route('payroll.salary.save') }}">
        @csrf
        <input type="hidden" name="staff_id" value="{{ $s->id }}">
        <tr>
            <td>
                <strong>{{ $s->name }}</strong>
                @if(optional($ss)->bank_details_locked)
                    <div style="font-size:9px;color:var(--slate-light)" title="Staff set these themselves and they're locked from their side">🔒 self-set</div>
                @endif
            </td>
            <td style="font-size:11px;text-transform:capitalize">{{ str_replace('_',' ',$s->role) }}</td>
            <td><input type="number" name="basic_salary" class="fc" value="{{ optional($ss)->basic_salary ?? 0 }}" step="0.01" min="0" style="width:90px"></td>
            <td><input type="number" name="housing_allowance" class="fc" value="{{ optional($ss)->housing_allowance ?? 0 }}" step="0.01" min="0" style="width:80px"></td>
            <td><input type="number" name="transport_allowance" class="fc" value="{{ optional($ss)->transport_allowance ?? 0 }}" step="0.01" min="0" style="width:80px"></td>
            <td><input type="number" name="other_allowances" class="fc" value="{{ optional($ss)->other_allowances ?? 0 }}" step="0.01" min="0" style="width:80px"></td>
            <td><input type="number" name="annual_rent_paid" class="fc" value="{{ optional($ss)->annual_rent_paid ?? 0 }}" step="0.01" min="0" style="width:90px" title="Used for rent relief on PAYE tax"></td>
            <td><input type="text" name="bank_name" class="fc" value="{{ optional($ss)->bank_name }}" placeholder="Bank" style="width:100px"></td>
            <td><input type="text" name="account_number" class="fc" value="{{ optional($ss)->account_number }}" placeholder="0000000000" maxlength="10" style="width:100px"></td>
            <td><input type="text" name="account_name" class="fc" value="{{ optional($ss)->account_name }}" placeholder="Account name" style="width:120px"></td>
            <td><input type="text" name="tax_identification_number" class="fc" value="{{ optional($ss)->tax_identification_number }}" placeholder="TIN" style="width:100px"></td>
            <td><input type="text" name="bvn" class="fc" value="{{ optional($ss)->bvn }}" placeholder="BVN" style="width:100px" maxlength="11"></td>
            <td><input type="text" name="nin" class="fc" value="{{ optional($ss)->nin }}" placeholder="NIN" style="width:100px" maxlength="11"></td>
            <td><button type="submit" class="btn btn-p btn-sm">Save</button></td>
        </tr>
        </form>
        @endforeach
        </tbody>
    </table>
</div>
<div style="font-size:12px;color:var(--slate-light);margin-bottom:16px">
    "Annual Rent" is optional — if filled in, 20% of it (capped at ₦500,000) is applied as rent relief when calculating PAYE tax, per the Nigeria Tax Act 2025.
</div>
<div style="display:flex;gap:10px">
    <a href="{{ route('payroll.staff-deductions') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">Manage Staff Deductions →</a>
    <a href="{{ route('payroll.tax-bands') }}" class="btn" style="background:white;border:1px solid var(--border);color:var(--midnight)">PAYE Tax Bands →</a>
</div>
@endsection