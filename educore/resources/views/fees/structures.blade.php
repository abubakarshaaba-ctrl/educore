@extends('layouts.app')
@section('title', 'Fee Structures')
@section('page-title', 'Fee Setup')

@push('styles')
<style>
    .page-tabs { display: flex; gap: 4px; background: white; border: 1px solid var(--border); border-radius: 10px; padding: 4px; margin-bottom: 20px; width: fit-content; }
    .page-tab { padding: 7px 16px; border-radius: 7px; font-size: 13px; font-weight: 500; color: var(--slate); text-decoration: none; transition: all 150ms; }
    .page-tab.active { background: var(--indigo); color: white; }
    .page-tab:hover:not(.active) { background: #F1F5F9; }
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
    .amount { font-weight: 700; color: var(--midnight); }
    .empty-state { text-align: center; padding: 40px 20px; color: var(--slate-light); font-size: 13px; }
    @media(max-width:1024px) { .two-col { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('fees.subaccounts') }}" class="page-tab">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="page-tab">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="page-tab active">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="page-tab">Invoices</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="two-col">
    <div class="card">
        <div class="card-header"><span class="card-title">Fee Structures</span></div>
        @if($structures->count())
        <div class="tbl"><table>
            <thead><tr><th>Category</th><th>Class</th><th>Term</th><th>Amount</th></tr></thead>
            <tbody>
                @foreach($structures as $s)
                <tr>
                    <td>{{ $s->feeCategory->name }}</td>
                    <td>{{ $s->classLevel->name }}</td>
                    <td>{{ $s->term->name }} — {{ $s->term->session->name ?? '' }}</td>
                    <td class="amount">&#8358;{{ number_format($s->amount) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @else
        <div class="empty-state">No fee structures set up yet. Add one →</div>
        @endif
    </div>

    <div>
        {{-- Add Structure Form --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Add Fee Structure</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('fees.structures.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Fee Category <span>*</span></label>
                        <select name="fee_category_id" class="form-control">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('fee_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class Level <span>*</span></label>
                        <select name="class_level_id" class="form-control">
                            <option value="">Select class</option>
                            @foreach($classLevels as $level)
                                <option value="{{ $level->id }}" {{ old('class_level_id') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Term <span>*</span></label>
                        <select name="term_id" class="form-control">
                            <option value="">Select term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ old('term_id') == $term->id ? 'selected' : '' }}>
                                    {{ $term->name }} — {{ $term->session->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (&#8358;) <span>*</span></label>
                        <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="e.g. 45000" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Structure</button>
                </form>
            </div>
        </div>

        {{-- Generate Invoices --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Generate Invoices</span></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--slate);margin-bottom:16px">Auto-generate invoices for all active students in a class level for a selected term.</p>
                <form method="POST" action="{{ route('fees.invoices.generate') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Term <span>*</span></label>
                        <select name="term_id" class="form-control">
                            <option value="">Select term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }} — {{ $term->session->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class Level <span>*</span></label>
                        <select name="class_level_id" class="form-control">
                            <option value="">Select class</option>
                            @foreach($classLevels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Invoices</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
