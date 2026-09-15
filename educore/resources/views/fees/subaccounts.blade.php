@extends('layouts.app')
@section('title', 'Bank Accounts')
@section('page-title', 'Fee Setup')

@push('styles')
<style>
    .fee-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;padding:5px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .fee-tab{min-height:40px;display:inline-flex;align-items:center;padding:8px 15px;border-radius:8px;font-size:13px;font-weight:650;color:var(--slate);text-decoration:none;transition:.15s ease}
    .fee-tab:hover{background:var(--brand-navy-soft);color:var(--brand-navy)}
    .fee-tab.active{background:var(--brand-navy);color:#fff}
    .fee-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(330px,.8fr);gap:20px;align-items:start}
    .ec-card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 6px 20px rgba(15,23,42,.04);overflow:hidden}
    .ec-card-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}
    .ec-card-title{font-size:14px;font-weight:750;color:var(--midnight)}
    .ec-card-body{padding:20px}
    .ec-stack{display:flex;flex-direction:column;gap:16px}
    .ec-field{display:flex;flex-direction:column;gap:6px}
    .ec-label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
    .ec-required{color:var(--crimson)}
    .ec-control{width:100%;min-height:44px;padding:10px 12px;border:1px solid var(--border);border-radius:9px;background:#F8FAFC;color:var(--midnight);font:inherit;font-size:13px;outline:none;transition:.15s ease}
    .ec-control:focus{background:#fff;border-color:var(--brand-gold);box-shadow:0 0 0 3px rgba(215,154,33,.18)}
    .ec-control.is-invalid{border-color:var(--crimson)}
    .ec-help{font-size:12px;color:var(--slate);line-height:1.45}
    .ec-invalid{font-size:12px;color:var(--crimson)}
    .ec-btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 16px;border-radius:9px;border:1px solid transparent;font:inherit;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:.15s ease}
    .ec-btn-primary{background:var(--brand-gold);color:var(--brand-navy);border-color:var(--brand-gold)}
    .ec-btn-primary:hover{background:var(--brand-gold-dark);border-color:var(--brand-gold-dark)}
    .ec-btn-block{width:100%}
    .ec-alert{padding:12px 15px;border-radius:10px;font-size:13px;margin-bottom:16px}
    .ec-alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#047857}
    .ec-alert-error{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson)}
    .ec-count{display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;background:var(--brand-gold-light);color:var(--brand-navy);font-size:11px;font-weight:700}
    .ec-table-wrap{overflow-x:auto}
    .ec-table{width:100%;min-width:620px;border-collapse:collapse}
    .ec-table th{padding:11px 16px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;text-align:left;white-space:nowrap}
    .ec-table td{padding:13px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle}
    .ec-table tbody tr:last-child td{border-bottom:0}
    .ec-table tbody tr:hover{background:#FCFDFE}
    .ec-meta{font-size:11px;color:var(--slate);margin-top:2px}
    .ec-code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12px}
    .ec-badge{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:700}
    .ec-badge-info{background:var(--brand-navy-soft);color:var(--brand-navy)}
    .ec-badge-success{background:#ECFDF5;color:#047857}
    .ec-empty{padding:44px 20px;text-align:center;color:var(--slate)}
    .ec-empty strong{display:block;color:var(--midnight);font-size:15px;margin-bottom:6px}
    @media(max-width:980px){.fee-grid{grid-template-columns:1fr}}
    @media(max-width:640px){.fee-tab{flex:1;justify-content:center;min-width:130px}.ec-card-head,.ec-card-body{padding-left:16px;padding-right:16px}}
</style>
@endpush

@section('content')
<div class="fee-tabs" aria-label="Fee setup navigation">
    <a href="{{ route('fees.subaccounts') }}" class="fee-tab active" aria-current="page">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="fee-tab">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="fee-tab">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="fee-tab">Invoices</a>
</div>

@if(session('success'))
    <div class="ec-alert ec-alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="ec-alert ec-alert-error" role="alert">{{ $errors->first() }}</div>
@endif

<div class="fee-grid">
    <section class="ec-card" aria-labelledby="bank-accounts-heading">
        <div class="ec-card-head">
            <span class="ec-card-title" id="bank-accounts-heading">Bank Accounts</span>
            <span class="ec-count">{{ $subaccounts->count() }} {{ $subaccounts->count() === 1 ? 'account' : 'accounts' }}</span>
        </div>

        @if($subaccounts->count())
            <div class="ec-table-wrap">
                <table class="ec-table">
                    <thead>
                        <tr>
                            <th scope="col">Purpose</th>
                            <th scope="col">Bank</th>
                            <th scope="col">Account No.</th>
                            <th scope="col">Gateway</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subaccounts as $sub)
                            <tr>
                                <td>
                                    <strong>{{ $sub->purpose_name }}</strong>
                                    <div class="ec-meta">{{ $sub->account_name }}</div>
                                </td>
                                <td>{{ $sub->bank_name }}</td>
                                <td class="ec-code">{{ $sub->account_number }}</td>
                                <td><span class="ec-badge ec-badge-info">{{ ucfirst($sub->gateway) }}</span></td>
                                <td><span class="ec-badge ec-badge-success">Active</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ec-empty">
                <strong>No bank accounts yet</strong>
                Add the first collection account using the form.
            </div>
        @endif
    </section>

    <section class="ec-card" aria-labelledby="add-bank-account-heading">
        <div class="ec-card-head"><span class="ec-card-title" id="add-bank-account-heading">Add Bank Account</span></div>
        <div class="ec-card-body">
            <form method="POST" action="{{ route('fees.subaccounts.store') }}" class="ec-stack">
                @csrf
                <div class="ec-field">
                    <label class="ec-label" for="purpose_name">Purpose Name <span class="ec-required">*</span></label>
                    <input id="purpose_name" type="text" name="purpose_name" class="ec-control {{ $errors->has('purpose_name') ? 'is-invalid' : '' }}" value="{{ old('purpose_name') }}" placeholder="e.g. Tuition Account" required>
                    @error('purpose_name')<div class="ec-invalid">{{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="account_name">Account Name <span class="ec-required">*</span></label>
                    <input id="account_name" type="text" name="account_name" class="ec-control" value="{{ old('account_name') }}" placeholder="e.g. Greenfield Academy Ltd" required>
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="bank_name">Bank Name <span class="ec-required">*</span></label>
                    <select id="bank_name" name="bank_name" class="ec-control" required>
                        <option value="">Select bank</option>
                        @foreach(['Access Bank','First Bank','GTBank','UBA','Zenith Bank','Fidelity Bank','FCMB','Sterling Bank','Union Bank','Polaris Bank','Wema Bank','Keystone Bank','Heritage Bank','Stanbic IBTC','Standard Chartered'] as $bank)
                            <option value="{{ $bank }}" {{ old('bank_name') === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="account_number">Account Number <span class="ec-required">*</span></label>
                    <input id="account_number" type="text" name="account_number" class="ec-control {{ $errors->has('account_number') ? 'is-invalid' : '' }}" value="{{ old('account_number') }}" placeholder="10-digit NUBAN" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" required>
                    <div class="ec-help">Enter the 10-digit Nigerian bank account number.</div>
                    @error('account_number')<div class="ec-invalid">{{ $message }}</div>@enderror
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="gateway">Payment Gateway <span class="ec-required">*</span></label>
                    <select id="gateway" name="gateway" class="ec-control" required>
                        <option value="paystack" {{ old('gateway') === 'paystack' ? 'selected' : '' }}>Paystack</option>
                        <option value="monnify" {{ old('gateway') === 'monnify' ? 'selected' : '' }}>Monnify</option>
                        <option value="flutterwave" {{ old('gateway') === 'flutterwave' ? 'selected' : '' }}>Flutterwave</option>
                    </select>
                </div>

                <div class="ec-field">
                    <label class="ec-label" for="gateway_subaccount_code">Gateway Subaccount Code</label>
                    <input id="gateway_subaccount_code" type="text" name="gateway_subaccount_code" class="ec-control" value="{{ old('gateway_subaccount_code') }}" placeholder="e.g. ACCT_xxxxxx">
                    <div class="ec-help">Optional. Leave blank when the selected payment gateway does not require a subaccount code.</div>
                </div>

                <button type="submit" class="ec-btn ec-btn-primary ec-btn-block">Add Bank Account</button>
            </form>
        </div>
    </section>
</div>
@endsection
