@extends('layouts.app')
@section('title','Generate Payroll')
@section('page-title','Generate Payroll')

@push('styles')
<style>
.generate-wrap{max-width:860px}
.generate-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;box-shadow:0 1px 3px rgba(15,23,42,.04)}
.generate-head{padding:14px 18px;border-bottom:1px solid var(--border);background:var(--brand-navy-soft);font-size:14px;font-weight:800;color:var(--brand-navy)}
.generate-body{padding:20px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:15px}
.form-label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.form-control{width:100%;min-height:42px;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;color:var(--midnight)}
.form-control:focus{border-color:var(--brand-gold);box-shadow:0 0 0 3px rgba(215,154,33,.14);background:#fff}
.info-box{background:var(--brand-navy-soft);border:1px solid #C9D8EC;border-radius:10px;padding:13px 15px;font-size:13px;color:var(--brand-navy);margin-bottom:16px;line-height:1.5}
.info-box a{font-weight:700;color:var(--brand-navy)}
.generate-actions{display:flex;gap:10px;flex-wrap:wrap}
@media(max-width:640px){.form-row{grid-template-columns:1fr}.generate-body{padding:16px}.generate-actions .btn{flex:1;justify-content:center}}
</style>
@endpush

@section('content')
<div class="generate-wrap">
    <a href="{{ route('payroll.index') }}" class="btn btn-ghost" style="margin-bottom:16px">← Back to Payroll</a>

    <div class="info-box" role="note">
        Payroll is calculated from each staff member's configured salary and deduction settings. Review <a href="{{ route('payroll.salary') }}">Salary Settings</a> before generating a new period.
    </div>

    <form method="POST" action="{{ route('payroll.generate') }}">
        @csrf
        <div class="generate-card">
            <div class="generate-head">New Payroll Period</div>
            <div class="generate-body">
                <div class="form-group">
                    <label for="payroll-title" class="form-label">Payroll Title *</label>
                    <input id="payroll-title" type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="e.g. September 2026 Staff Salary" autocomplete="off">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="period-start" class="form-label">Period Start *</label>
                        <input id="period-start" type="date" name="period_start" class="form-control" required value="{{ old('period_start', date('Y-m-01')) }}">
                    </div>
                    <div class="form-group">
                        <label for="period-end" class="form-label">Period End *</label>
                        <input id="period-end" type="date" name="period_end" class="form-control" required value="{{ old('period_end', date('Y-m-t')) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="generate-actions">
            <button type="submit" class="btn btn-primary">Generate Payroll</button>
            <a href="{{ route('payroll.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection