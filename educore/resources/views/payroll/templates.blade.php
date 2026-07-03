@extends('layouts.app')
@section('title','Payroll Templates')
@section('page-title','Payroll Templates')

@push('styles')
<style>
.tpl-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:13px;font-weight:700}
.card-body{padding:18px}
.form-group{margin-bottom:12px}
.form-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);margin-bottom:4px}
.form-control{width:100%;padding:8px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none}
.form-control:focus{border-color:var(--indigo);background:white}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border:none;border-radius:8px;cursor:pointer;transition:all 150ms}
.btn-primary{background:var(--indigo);color:white}.btn-primary:hover{background:#1946C0}
.btn-danger{background:#EF4444;color:white;font-size:11px;padding:5px 10px}
.btn-full{width:100%;justify-content:center}
.deduct-item{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border:1px solid var(--border);border-radius:9px;margin-bottom:8px}
.deduct-name{font-size:13px;font-weight:600}
.deduct-meta{font-size:11px;color:var(--slate-light);margin-top:2px}
.role-card{border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:10px}
.role-title{font-size:13px;font-weight:700;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center}
.role-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.role-stat{text-align:center;background:#F8FAFC;border-radius:8px;padding:8px}
.role-val{font-size:14px;font-weight:800;color:var(--midnight)}
.role-lbl{font-size:10px;color:var(--slate-light);margin-top:2px}
.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.type-badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px}
.tax{background:#FEF2F2;color:#DC2626}.pension{background:#EFF6FF;color:#2563EB}
.loan{background:#FFFBEB;color:#D97706}.other{background:#F0FDF4;color:#059669}
.page-tabs{display:flex;gap:8px;margin-bottom:20px}
.page-tab{padding:8px 18px;font-size:13px;font-weight:600;border-radius:8px;border:1.5px solid var(--border);background:white;cursor:pointer;color:var(--slate);text-decoration:none;transition:all 150ms}
.page-tab.active,.page-tab:hover{background:var(--indigo);border-color:var(--indigo);color:white}
@media(max-width:900px){.tpl-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}.role-grid{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('payroll.index') }}"     class="page-tab">Payroll Periods</a>
    <a href="{{ route('payroll.salary') }}"    class="page-tab">Salary Settings</a>
    <a href="{{ route('payroll.templates') }}" class="page-tab active">Templates & Deductions</a>
</div>

@if(session('success'))<div class="alert-success">✓ {{ session('success') }}</div>@endif

<div class="tpl-grid">

    {{-- ── LEFT: Role Templates ──────────────────────────────────────── --}}
    <div>
        <div class="card">
            <div class="card-head"><span class="card-title">👔 Role Payroll Templates</span></div>
            <div class="card-body">
                <p style="font-size:12px;color:var(--slate-light);margin-bottom:14px">
                    Set default salary structure per role. When generating payroll, these values auto-fill for each staff member.
                </p>

                @forelse($roleTemplates as $tpl)
                <div class="role-card">
                    <div class="role-title">
                        <span>{{ $tpl->label ?: ucwords(str_replace('_',' ',$tpl->role)) }}</span>
                        <span style="font-size:12px;color:#059669;font-weight:700">
                            Gross: ₦{{ number_format($tpl->grossSalary()) }}
                        </span>
                    </div>
                    <div class="role-grid">
                        <div class="role-stat"><div class="role-val">₦{{ number_format($tpl->basic_salary) }}</div><div class="role-lbl">Basic</div></div>
                        <div class="role-stat"><div class="role-val">₦{{ number_format($tpl->housing_allowance) }}</div><div class="role-lbl">Housing</div></div>
                        <div class="role-stat"><div class="role-val">₦{{ number_format($tpl->transport_allowance) }}</div><div class="role-lbl">Transport</div></div>
                        <div class="role-stat"><div class="role-val">₦{{ number_format($tpl->other_allowances) }}</div><div class="role-lbl">Others</div></div>
                    </div>
                    @if($tpl->deduction_ids)
                    <div style="margin-top:8px;font-size:11px;color:var(--slate-light)">
                        Deductions: {{ $deductions->whereIn('id',$tpl->deduction_ids??[])->pluck('name')->join(', ') ?: '—' }}
                    </div>
                    @endif
                </div>
                @empty
                <div style="text-align:center;padding:24px;color:var(--slate-light);font-size:13px">No role templates yet</div>
                @endforelse
            </div>
        </div>

        {{-- Add role template --}}
        <div class="card">
            <div class="card-head"><span class="card-title">+ Add / Update Role Template</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('payroll.templates.role.store') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="">— Select —</option>
                            @foreach($roles as $r)
                            <option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Label</label>
                        <input type="text" name="label" class="form-control" placeholder="e.g. Senior Teacher">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Basic Salary (₦) *</label>
                        <input type="number" name="basic_salary" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Housing Allowance (₦)</label>
                        <input type="number" name="housing_allowance" class="form-control" step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Transport Allowance (₦)</label>
                        <input type="number" name="transport_allowance" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Other Allowances (₦)</label>
                        <input type="number" name="other_allowances" class="form-control" step="0.01" min="0" value="0">
                    </div>
                </div>
                @if($deductions->count())
                <div class="form-group">
                    <label class="form-label">Apply Deductions</label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px">
                        @foreach($deductions as $d)
                        <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer">
                            <input type="checkbox" name="deduction_ids[]" value="{{ $d->id }}" style="accent-color:var(--indigo)">
                            {{ $d->label() }}
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
                <button type="submit" class="btn btn-primary btn-full">Save Role Template</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Deduction Templates ────────────────────────────────── --}}
    <div>
        <div class="card">
            <div class="card-head"><span class="card-title">✂️ Deduction Templates</span></div>
            <div class="card-body">
                @forelse($deductions as $d)
                <div class="deduct-item">
                    <div>
                        <div class="deduct-name">{{ $d->name }}</div>
                        <div class="deduct-meta">
                            <span class="type-badge {{ $d->type }}">{{ ucfirst($d->type) }}</span>
                            &nbsp;{{ $d->calc_method === 'percentage' ? $d->value.'%' : '₦'.number_format($d->value,2) }}
                            @if($d->description)<span style="margin-left:6px">· {{ $d->description }}</span>@endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('payroll.templates.deduction.destroy', $d) }}"
                          onsubmit="return confirm('Delete deduction template?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">✕</button>
                    </form>
                </div>
                @empty
                <div style="text-align:center;padding:24px;color:var(--slate-light);font-size:13px">
                    No deduction templates yet
                </div>
                @endforelse
            </div>
        </div>

        {{-- Add deduction --}}
        <div class="card">
            <div class="card-head"><span class="card-title">+ Add Deduction Template</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('payroll.templates.deduction.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. PAYE Tax, NHF, Pension" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-control" required>
                            <option value="tax">Tax (PAYE)</option>
                            <option value="pension">Pension</option>
                            <option value="loan">Loan Repayment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Calculation Method *</label>
                        <select name="calc_method" class="form-control" required>
                            <option value="percentage">Percentage of Gross (%)</option>
                            <option value="fixed">Fixed Amount (₦)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Value *</label>
                    <input type="number" name="value" class="form-control" step="0.01" min="0" required placeholder="e.g. 7.5 for 7.5%">
                </div>
                <div class="form-group">
                    <label class="form-label">Description (optional)</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. Employee pension contribution">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Add Deduction Template</button>
                </form>
            </div>
        </div>

        {{-- PAYE Quick-add tip --}}
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:14px 16px;font-size:12px;color:#92400E">
            <strong>🇳🇬 Standard Nigerian Deductions:</strong><br>
            Pension (Employee) = 8% of gross &nbsp;·&nbsp; NHF = 2.5% of basic &nbsp;·&nbsp; PAYE = varies by income band
        </div>
    </div>

</div>
@endsection
