@extends('layouts.app')
@section('title', 'Bank Accounts')
@section('page-title', 'Fee Setup')

@push('styles')
<style>
    .page-tabs { display: flex; gap: 4px; background: white; border: 1px solid var(--border); border-radius: 10px; padding: 4px; margin-bottom: 20px; flex-wrap:wrap; }
    .page-tab { padding: 7px 16px; border-radius: 7px; font-size: 13px; font-weight: 500; color: var(--slate); text-decoration: none; transition: all 150ms; }
    .page-tab.active { background: var(--indigo); color: white; }
    .page-tab:hover:not(.active) { background: #F1F5F9; color: var(--midnight); }

    .two-col { display: grid; grid-template-columns: 1fr 380px; gap: 20px; align-items: start; }

    .card { background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
    .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .card-title { font-size: 14px; font-weight: 600; color: var(--midnight); }
    .card-body { padding: 20px; }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 11px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
    .form-label span { color: var(--crimson); }
    .form-control { width: 100%; padding: 9px 12px; font-size: 13px; font-family: inherit; border: 1px solid var(--border); border-radius: 8px; color: var(--midnight); background: #F8FAFC; outline: none; transition: border-color 200ms, box-shadow 200ms; }
    .form-control:focus { border-color: var(--indigo); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: white; }
    .is-invalid { border-color: var(--crimson) !important; }
    .invalid-feedback { font-size: 12px; color: var(--crimson); margin-top: 4px; }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; font-size: 13px; font-weight: 600; font-family: inherit; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: background 150ms; }
    .btn-primary { background: var(--indigo); color: white; width: 100%; justify-content: center; }
    .btn-primary:hover { background: #1D4ED8; }

    .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--emerald); margin-bottom: 16px; }
    .alert-error { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--crimson); margin-bottom: 16px; }

    table { width: 100%; border-collapse: collapse; min-width: 560px; }
    .table-wrap { overflow-x: auto; }
    thead th { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 16px; text-align: left; background: #F8FAFC; border-bottom: 1px solid var(--border); }
    tbody td { padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--midnight); vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #F8FAFC; }

    .badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .badge-success { background: #ECFDF5; color: var(--emerald); }
    .badge-info { background: var(--indigo-bg); color: var(--indigo); }

    .empty-state { text-align: center; padding: 40px 20px; color: var(--slate-light); font-size: 13px; }

    @media(max-width:1024px) { .two-col { grid-template-columns: 1fr; } }
</style>

@endpush

@section('content')

<div class="page-tabs">
    <a href="{{ route('fees.subaccounts') }}" class="page-tab active">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="page-tab">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="page-tab">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="page-tab">Invoices</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="two-col">
    {{-- List --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Bank Subaccounts</span>
            <span class="badge badge-info">{{ $subaccounts->count() }} accounts</span>
        </div>
        @if($subaccounts->count())
        <div class="table-wrap">
            <div class="tbl"><table>
                <thead>
                    <tr>
                        <th>Purpose</th>
                        <th>Bank</th>
                        <th>Account No.</th>
                        <th>Gateway</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subaccounts as $sub)
                    <tr>
                        <td><strong>{{ $sub->purpose_name }}</strong><br><small style="color:var(--slate-light)">{{ $sub->account_name }}</small></td>
                        <td>{{ $sub->bank_name }}</td>
                        <td style="font-family:monospace">{{ $sub->account_number }}</td>
                        <td><span class="badge badge-info">{{ ucfirst($sub->gateway) }}</span></td>
                        <td><span class="badge badge-success">Active</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
        @else
        <div class="empty-state">No bank accounts added yet. Add your first account →</div>
        @endif
    </div>

    {{-- Form --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Add Bank Account</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('fees.subaccounts.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Purpose Name <span>*</span></label>
                    <input type="text" name="purpose_name" class="form-control {{ $errors->has('purpose_name') ? 'is-invalid' : '' }}" value="{{ old('purpose_name') }}" placeholder="e.g. Tuition Account">
                    @error('purpose_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Account Name <span>*</span></label>
                    <input type="text" name="account_name" class="form-control" value="{{ old('account_name') }}" placeholder="e.g. Greenfield Academy Ltd">
                </div>
                <div class="form-group">
                    <label class="form-label">Bank Name <span>*</span></label>
                    <select name="bank_name" class="form-control">
                        <option value="">Select bank</option>
                        @foreach(['Access Bank','First Bank','GTBank','UBA','Zenith Bank','Fidelity Bank','FCMB','Sterling Bank','Union Bank','Polaris Bank','Wema Bank','Keystone Bank','Heritage Bank','Stanbic IBTC','Standard Chartered'] as $bank)
                            <option value="{{ $bank }}" {{ old('bank_name') === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Account Number <span>*</span></label>
                    <input type="text" name="account_number" class="form-control {{ $errors->has('account_number') ? 'is-invalid' : '' }}" value="{{ old('account_number') }}" placeholder="10-digit NUBAN" maxlength="10">
                    @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Gateway <span>*</span></label>
                    <select name="gateway" class="form-control">
                        <option value="paystack"    {{ old('gateway') === 'paystack'    ? 'selected' : '' }}>Paystack</option>
                        <option value="monnify"     {{ old('gateway') === 'monnify'     ? 'selected' : '' }}>Monnify</option>
                        <option value="flutterwave" {{ old('gateway') === 'flutterwave' ? 'selected' : '' }}>Flutterwave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Gateway Subaccount Code</label>
                    <input type="text" name="gateway_subaccount_code" placeholder="Optional - leave blank if not using payment gateway" class="form-control" value="{{ old('gateway_subaccount_code') }}" placeholder="ACCT_xxxxxx (optional)">
                </div>
                <button type="submit" class="btn btn-primary">Add Bank Account</button>
            </form>
        </div>
    </div>
</div>
@endsection
