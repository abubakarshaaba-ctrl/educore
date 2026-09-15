@extends('layouts.app')
@section('title', 'Fee Categories')
@section('page-title', 'Fee Setup')

@push('styles')
<style>
    .fee-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
    .fee-category-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,380px);gap:20px;align-items:start}
    .fee-category-count{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:28px;padding:0 9px;border-radius:999px;background:var(--indigo-bg);color:var(--midnight);font-size:12px;font-weight:800}
    .fee-category-form .form-group{margin-bottom:16px}
    .fee-checkbox{display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--border);border-radius:10px;background:#F8FAFC;color:var(--midnight);font-size:13px;line-height:1.5}
    .fee-checkbox input{margin-top:2px}
    .fee-route{font-size:12px;color:var(--slate)}
    @media(max-width:1024px){.fee-category-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="page-tabs fee-tabs" role="navigation" aria-label="Fee management sections">
    <a href="{{ route('fees.subaccounts') }}" class="page-tab">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="page-tab active" aria-current="page">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="page-tab">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="page-tab">Invoices</a>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
@endif

<div class="fee-category-grid">
    <div class="card">
        <div class="ch">
            <div>
                <strong>Fee categories</strong>
                <div style="font-size:12px;color:var(--slate);margin-top:2px">Define how school charges are grouped and routed.</div>
            </div>
            <span class="fee-category-count" aria-label="{{ $categories->count() }} categories">{{ $categories->count() }}</span>
        </div>
        @if($categories->count())
            <div class="tbl">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Routes to</th>
                            <th scope="col">Requirement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $cat)
                            <tr>
                                <td><strong>{{ $cat->name }}</strong></td>
                                <td class="fee-route">{{ optional($cat->subaccount)->purpose_name ?? '—' }}</td>
                                <td>
                                    @if($cat->is_mandatory)
                                        <span class="badge badge-success">Mandatory</span>
                                    @else
                                        <span class="badge badge-warning">Optional</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3>No fee categories yet</h3>
                <p>Create the first category using the form beside this list.</p>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="ch">
            <div>
                <strong>New fee category</strong>
                <div style="font-size:12px;color:var(--slate);margin-top:2px">Connect each charge type to the correct school bank account.</div>
            </div>
        </div>
        <div class="cb fee-category-form">
            @if($subaccounts->isEmpty())
                <div class="alert alert-warning" role="status">
                    Add a bank account before creating fee categories.
                </div>
                <a href="{{ route('fees.subaccounts') }}" class="btn btn-primary" style="width:100%;justify-content:center">Add bank account</a>
            @else
                <form method="POST" action="{{ route('fees.categories.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="fee-category-name">Category name <span aria-hidden="true">*</span></label>
                        <input id="fee-category-name" type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Tuition Fee, PTA Levy" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fee-category-account">Routes to bank account <span aria-hidden="true">*</span></label>
                        <select id="fee-category-account" name="school_bank_subaccount_id" class="form-control" required>
                            <option value="">Select account</option>
                            @foreach($subaccounts as $sub)
                                <option value="{{ $sub->id }}" {{ old('school_bank_subaccount_id') == $sub->id ? 'selected' : '' }}>
                                    {{ $sub->purpose_name }} — {{ $sub->bank_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="fee-checkbox">
                            <input type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory', '1') ? 'checked' : '' }}>
                            <span><strong>Mandatory for all students</strong><br><span style="color:var(--slate)">Include this category by default when applicable fee structures are generated.</span></span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Create category</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
