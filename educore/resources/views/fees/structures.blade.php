@extends('layouts.app')
@section('title', 'Fee Structures')
@section('page-title', 'Fee Setup')

@push('styles')
<style>
    .fee-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;padding:5px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .fee-tab{min-height:40px;display:inline-flex;align-items:center;padding:8px 15px;border-radius:8px;font-size:13px;font-weight:650;color:var(--slate);text-decoration:none;transition:.15s ease}
    .fee-tab:hover{background:var(--brand-navy-soft);color:var(--brand-navy)}
    .fee-tab.active{background:var(--brand-navy);color:#fff}
    .fee-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(320px,.8fr);gap:20px;align-items:start}
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
    .ec-btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 16px;border-radius:9px;border:1px solid transparent;font:inherit;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:.15s ease}
    .ec-btn-primary{background:var(--brand-gold);color:var(--brand-navy);border-color:var(--brand-gold)}
    .ec-btn-primary:hover{background:var(--brand-gold-dark);border-color:var(--brand-gold-dark)}
    .ec-btn-block{width:100%}
    .ec-alert{padding:12px 15px;border-radius:10px;font-size:13px;margin-bottom:16px}
    .ec-alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#047857}
    .ec-alert-error{background:#FEF2F2;border:1px solid #FECACA;color:var(--crimson)}
    .ec-note{padding:12px 14px;border-radius:10px;background:var(--brand-gold-light);border:1px solid rgba(215,154,33,.3);color:var(--brand-navy);font-size:12px;line-height:1.55}
    .ec-table-wrap{overflow-x:auto}
    .ec-table{width:100%;border-collapse:collapse}
    .ec-table th{padding:11px 16px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;text-align:left;white-space:nowrap}
    .ec-table td{padding:13px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight);vertical-align:middle}
    .ec-table tbody tr:last-child td{border-bottom:0}
    .ec-table tbody tr:hover{background:#FCFDFE}
    .amount{font-weight:800;color:var(--brand-navy);white-space:nowrap}
    .ec-empty{padding:44px 20px;text-align:center;color:var(--slate)}
    .ec-empty strong{display:block;color:var(--midnight);font-size:15px;margin-bottom:6px}
    @media(max-width:980px){.fee-grid{grid-template-columns:1fr}}
    @media(max-width:640px){.fee-tabs{width:100%}.fee-tab{flex:1;justify-content:center;min-width:130px}.ec-card-head,.ec-card-body{padding-left:16px;padding-right:16px}}
</style>
@endpush

@section('content')
<div class="fee-tabs" aria-label="Fee setup navigation">
    <a href="{{ route('fees.subaccounts') }}" class="fee-tab">Bank Accounts</a>
    <a href="{{ route('fees.categories') }}" class="fee-tab">Fee Categories</a>
    <a href="{{ route('fees.structures') }}" class="fee-tab active" aria-current="page">Fee Structures</a>
    <a href="{{ route('fees.invoices') }}" class="fee-tab">Invoices</a>
</div>

@if(session('success'))
    <div class="ec-alert ec-alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="ec-alert ec-alert-error" role="alert">{{ $errors->first() }}</div>
@endif

<div class="fee-grid">
    <section class="ec-card" aria-labelledby="fee-structures-heading">
        <div class="ec-card-head">
            <span class="ec-card-title" id="fee-structures-heading">Fee Structures</span>
            <span style="font-size:12px;color:var(--slate)">{{ $structures->count() }} configured</span>
        </div>

        @if($structures->count())
            <div class="ec-table-wrap">
                <table class="ec-table">
                    <thead>
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Class</th>
                            <th scope="col">Term</th>
                            <th scope="col">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($structures as $s)
                            <tr>
                                <td><strong>{{ $s->feeCategory->name }}</strong></td>
                                <td>{{ $s->classLevel->name }}</td>
                                <td>{{ $s->term->name }} — {{ $s->term->session->name ?? '' }}</td>
                                <td class="amount">&#8358;{{ number_format($s->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ec-empty">
                <strong>No fee structures yet</strong>
                Add the first category, class, term and amount combination using the form.
            </div>
        @endif
    </section>

    <div class="ec-stack">
        <section class="ec-card" aria-labelledby="add-structure-heading">
            <div class="ec-card-head"><span class="ec-card-title" id="add-structure-heading">Add Fee Structure</span></div>
            <div class="ec-card-body">
                <form method="POST" action="{{ route('fees.structures.store') }}" class="ec-stack">
                    @csrf
                    <div class="ec-field">
                        <label class="ec-label" for="fee_category_id">Fee Category <span class="ec-required">*</span></label>
                        <select id="fee_category_id" name="fee_category_id" class="ec-control" required>
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('fee_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ec-field">
                        <label class="ec-label" for="class_level_id">Class Level <span class="ec-required">*</span></label>
                        <select id="class_level_id" name="class_level_id" class="ec-control" required>
                            <option value="">Select class</option>
                            @foreach($classLevels as $level)
                                <option value="{{ $level->id }}" {{ old('class_level_id') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ec-field">
                        <label class="ec-label" for="term_id">Term <span class="ec-required">*</span></label>
                        <select id="term_id" name="term_id" class="ec-control" required>
                            <option value="">Select term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ old('term_id') == $term->id ? 'selected' : '' }}>
                                    {{ $term->name }} — {{ $term->session->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ec-field">
                        <label class="ec-label" for="amount">Amount (&#8358;) <span class="ec-required">*</span></label>
                        <input id="amount" type="number" name="amount" class="ec-control" value="{{ old('amount') }}" placeholder="e.g. 45000" min="1" required inputmode="decimal">
                    </div>
                    <button type="submit" class="ec-btn ec-btn-primary ec-btn-block">Save Structure</button>
                </form>
            </div>
        </section>

        <section class="ec-card" aria-labelledby="generate-invoices-heading">
            <div class="ec-card-head"><span class="ec-card-title" id="generate-invoices-heading">Generate Invoices</span></div>
            <div class="ec-card-body">
                <div class="ec-note" style="margin-bottom:16px">Generate invoices for all active students in the selected class level and term. Existing server-side duplicate protections remain authoritative.</div>
                <form method="POST" action="{{ route('fees.invoices.generate') }}" class="ec-stack">
                    @csrf
                    <div class="ec-field">
                        <label class="ec-label" for="generate_term_id">Term <span class="ec-required">*</span></label>
                        <select id="generate_term_id" name="term_id" class="ec-control" required>
                            <option value="">Select term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }} — {{ $term->session->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ec-field">
                        <label class="ec-label" for="generate_class_level_id">Class Level <span class="ec-required">*</span></label>
                        <select id="generate_class_level_id" name="class_level_id" class="ec-control" required>
                            <option value="">Select class</option>
                            @foreach($classLevels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="ec-btn ec-btn-primary ec-btn-block">Generate Invoices</button>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
