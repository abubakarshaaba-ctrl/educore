@extends('layouts.app')
@section('title','Salary Settings')
@section('page-title','Salary Settings')

@push('styles')
<style>
.settings-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;box-shadow:0 1px 3px rgba(15,23,42,.04)}
.settings-head{padding:14px 18px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft);font-size:13px;font-weight:800;color:var(--brand-navy);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.settings-hint{font-size:11px;font-weight:600;color:var(--slate-light)}
.settings-table-wrap{overflow-x:auto}
.settings-table{width:100%;border-collapse:collapse;min-width:1220px}
.settings-table thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:9px 12px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border);white-space:nowrap}
.settings-table tbody td{padding:9px 10px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:middle;color:var(--midnight)}
.settings-table tbody tr:last-child td{border-bottom:none}
.settings-table tbody tr:hover td{background:#FCFDFE}
.staff-name{font-weight:700;color:var(--brand-navy)}
.staff-lock{font-size:9px;color:var(--slate-light);margin-top:3px}
.compact-control{min-height:38px;padding:7px 9px;font-size:12px;font-family:inherit;border:1px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;width:100%;color:var(--midnight);transition:border-color 150ms,box-shadow 150ms}
.compact-control:focus{border-color:var(--brand-gold);box-shadow:0 0 0 3px rgba(215,154,33,.12);background:#fff}
.salary-note{font-size:12px;color:var(--slate);margin-bottom:16px;background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:12px 14px;line-height:1.5}
.settings-actions{display:flex;gap:10px;flex-wrap:wrap}
@media(max-width:640px){.settings-actions .btn{flex:1;justify-content:center;min-width:150px}}
</style>
@endpush

@section('content')
@if(session('success'))
<div class="alert-success" role="status" aria-live="polite">{{ session('success') }}</div>
@endif

<a href="{{ route('payroll.index') }}" class="btn btn-ghost" style="margin-bottom:16px">← Back to Payroll</a>

<div class="settings-card">
    <div class="settings-head">
        <span>Configure Staff Salaries</span>
        <span class="settings-hint">Save each staff row independently</span>
    </div>
    <div class="settings-table-wrap">
        <table class="settings-table">
            <thead>
                <tr>
                    <th>Staff</th><th>Role</th><th>Basic (₦)</th><th>Housing (₦)</th><th>Transport (₦)</th><th>Other (₦)</th><th>Annual Rent (₦)</th><th>Bank</th><th>Acct No.</th><th>Acct Name</th><th>TIN</th><th>BVN</th><th>NIN</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            @foreach($staff as $s)
            @php $ss = $settings[$s->id] ?? null; @endphp
            <form method="POST" action="{{ route('payroll.salary.save') }}">
                @csrf
                <input type="hidden" name="staff_id" value="{{ $s->id }}">
                <tr>
                    <td>
                        <div class="staff-name">{{ $s->name }}</div>
                        @if(optional($ss)->bank_details_locked)
                            <div class="staff-lock" title="Staff set these details themselves">🔒 Staff-managed bank details</div>
                        @endif
                    </td>
                    <td style="text-transform:capitalize">{{ str_replace('_',' ',$s->role) }}</td>
                    <td><input aria-label="Basic salary for {{ $s->name }}" type="number" name="basic_salary" class="compact-control" value="{{ optional($ss)->basic_salary ?? 0 }}" step="0.01" min="0" style="width:92px"></td>
                    <td><input aria-label="Housing allowance for {{ $s->name }}" type="number" name="housing_allowance" class="compact-control" value="{{ optional($ss)->housing_allowance ?? 0 }}" step="0.01" min="0" style="width:82px"></td>
                    <td><input aria-label="Transport allowance for {{ $s->name }}" type="number" name="transport_allowance" class="compact-control" value="{{ optional($ss)->transport_allowance ?? 0 }}" step="0.01" min="0" style="width:82px"></td>
                    <td><input aria-label="Other allowances for {{ $s->name }}" type="number" name="other_allowances" class="compact-control" value="{{ optional($ss)->other_allowances ?? 0 }}" step="0.01" min="0" style="width:82px"></td>
                    <td><input aria-label="Annual rent for {{ $s->name }}" type="number" name="annual_rent_paid" class="compact-control" value="{{ optional($ss)->annual_rent_paid ?? 0 }}" step="0.01" min="0" style="width:94px" title="Used for rent relief on PAYE tax"></td>
                    <td><input aria-label="Bank name for {{ $s->name }}" type="text" name="bank_name" class="compact-control" value="{{ optional($ss)->bank_name }}" placeholder="Bank" style="width:104px"></td>
                    <td><input aria-label="Account number for {{ $s->name }}" type="text" inputmode="numeric" pattern="[0-9]{10}" name="account_number" class="compact-control" value="{{ optional($ss)->account_number }}" placeholder="0000000000" maxlength="10" style="width:104px"></td>
                    <td><input aria-label="Account name for {{ $s->name }}" type="text" name="account_name" class="compact-control" value="{{ optional($ss)->account_name }}" placeholder="Account name" style="width:124px"></td>
                    <td><input aria-label="TIN for {{ $s->name }}" type="text" name="tax_identification_number" class="compact-control" value="{{ optional($ss)->tax_identification_number }}" placeholder="TIN" style="width:104px"></td>
                    <td><input aria-label="BVN for {{ $s->name }}" type="text" inputmode="numeric" name="bvn" class="compact-control" value="{{ optional($ss)->bvn }}" placeholder="BVN" style="width:104px" maxlength="11"></td>
                    <td><input aria-label="NIN for {{ $s->name }}" type="text" inputmode="numeric" name="nin" class="compact-control" value="{{ optional($ss)->nin }}" placeholder="NIN" style="width:104px" maxlength="11"></td>
                    <td><button type="submit" class="btn btn-primary">Save</button></td>
                </tr>
            </form>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="salary-note">
    “Annual Rent” is optional. When supplied, it is used by the existing PAYE calculation logic for rent relief.
</div>

<div class="settings-actions">
    <a href="{{ route('payroll.staff-deductions') }}" class="btn btn-ghost">Manage Staff Deductions →</a>
    <a href="{{ route('payroll.tax-bands') }}" class="btn btn-ghost">PAYE Tax Bands →</a>
</div>
@endsection