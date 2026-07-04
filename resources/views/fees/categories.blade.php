@extends('layouts.app')
@section('title', 'Fee Categories')
@section('page-title', 'Fee Setup')

@push('styles')
<style>
    .page-tabs { display: flex; gap: 4px; background: white; border: 1px solid var(--border); border-radius: 10px; padding: 4px; margin-bottom: 20px; width: fit-content; }
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
    .form-control { width: 100%; padding: 9px 12px; font-size: 13px; font-family: inherit; border: 1px solid var(--border); border-radius: 8px; color: var(--midnight); background: #F8FAFC; outline: none; transition: border-color 200ms; }
    .form-control:focus { border-color: var(--indigo); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: white; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; font-size: 13px; font-weight: 600; font-family: inherit; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: background 150ms; }
    .btn-primary { background: var(--indigo); color: white; width: 100%; justify-content: center; }
    .btn-primary:hover { background: #1D4ED8; }
    .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--emerald); margin-bottom: 16px; }
    .alert-error { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--crimson); margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    thead th { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 16px; text-align: left; background: #F8FAFC; border-bottom: 1px solid var(--border); }
    tbody td { padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--midnight); }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #F8FAFC; }
    .badge { display: inline-flex; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .badge-success { background: #ECFDF5; color: var(--emerald); }
    .badge-warning { background: #FFFBEB; color: var(--amber); }
    .badge-info { background: var(--indigo-bg); color: var(--indigo); }
    .checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--midnight); }
    .empty-state { text-align: center; padding: 40px 20px; color: var(--slate-light); font-size: 13px; }
    @media(max-width:1024px) { .two-col { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('fees.subaccounts') }}" class="page-tab">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="page-tab active">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="page-tab">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="page-tab">Invoices</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="two-col">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Fee Categories</span>
            <span class="badge badge-info">{{ $categories->count() }}</span>
        </div>
        @if($categories->count())
        <div class="tbl"><table>
            <thead><tr><th>Category</th><th>Routes To</th><th>Mandatory</th></tr></thead>
            <tbody>
                @foreach($categories as $cat)
                <tr>
                    <td><strong>{{ $cat->name }}</strong></td>
                    <td style="font-size:12px;color:var(--slate)">{{ optional($cat->subaccount)->purpose_name ?? '—' }}</td>
                    <td>
                        @if($cat->is_mandatory)
                            <span class="badge badge-success">Yes</span>
                        @else
                            <span class="badge badge-warning">Optional</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @else
        <div class="empty-state">No fee categories yet. Create one →</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">New Fee Category</span></div>
        <div class="card-body">
            @if($subaccounts->isEmpty())
                <div class="alert-error">You must add a bank account first before creating fee categories.</div>
            @else
            <form method="POST" action="{{ route('fees.categories.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Category Name <span>*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Tuition Fee, PTA Levy">
                </div>
                <div class="form-group">
                    <label class="form-label">Routes to Bank Account <span>*</span></label>
                    <select name="school_bank_subaccount_id" class="form-control">
                        <option value="">Select account</option>
                        @foreach($subaccounts as $sub)
                            <option value="{{ $sub->id }}" {{ old('school_bank_subaccount_id') == $sub->id ? 'selected' : '' }}>
                                {{ $sub->purpose_name }} — {{ $sub->bank_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory', '1') ? 'checked' : '' }}>
                        Mandatory for all students
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Create Category</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
