@extends('layouts.super')
@section('title', 'Provision School')
@section('page-title', 'Provision New School')

@push('styles')
<style>
    .form-page { width:100%; max-width:none; }
    .breadcrumb { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo);text-decoration:none;font-weight:500; }
    .breadcrumb svg { width:14px;height:14px; }
    .page-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin-bottom:16px;overflow:hidden; }
    .card-header { padding:14px 24px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:24px; }
    .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
    .form-group { display:flex;flex-direction:column;gap:6px; }
    .form-label { font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em; }
    .form-label span { color:var(--crimson); }
    .form-control { padding:10px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms;width:100%; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .is-invalid { border-color:var(--crimson) !important; }
    .invalid-feedback { font-size:12px;color:var(--crimson);margin-top:2px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    @media(max-width:1100px) { .page-grid { grid-template-columns:1fr; } }
    @media(max-width:768px) { .form-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="form-page">
    <div class="breadcrumb">
        <a href="{{ route('super.dashboard') }}">Super Admin</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        <a href="{{ route('super.tenants') }}">Schools</a>
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        Provision New School
    </div>

    @if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('super.tenants.store') }}">
        @csrf
        <div class="page-grid">
            <div class="card">
                <div class="card-header">School Information</div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">School Name <span>*</span></label>
                            <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   value="{{ old('name') }}" placeholder="e.g. Greenfield Academy">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug <span>*</span></label>
                            <input type="text" name="slug" class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
                                   value="{{ old('slug') }}" placeholder="e.g. greenfield-academy">
                            <div style="font-size:11px;color:var(--slate-light)">Used for /school/your-school and /apply/your-school. Lowercase letters, numbers and hyphens only.</div>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Local Subdomain</label>
                            <input type="text" name="subdomain" class="form-control {{ $errors->has('subdomain') ? 'is-invalid' : '' }}"
                                   value="{{ old('subdomain') }}" placeholder="e.g. greenfield">
                            <div style="font-size:11px;color:var(--slate-light)">Optional. Used locally as subdomain.educore.test when configured on this PC.</div>
                            @error('subdomain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">School Email <span>*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="info@school.ng">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08012345678">
                        </div>
                        <div class="form-group" style="grid-column:span 2">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="School address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subscription Plan <span>*</span></label>
                            <select name="plan_id" class="form-control {{ $errors->has('plan_id') ? 'is-invalid' : '' }}" required>
                                <option value="">Select subscription plan...</option>
                                @forelse($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ (string) old('plan_id') === (string) $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - Monthly NGN {{ number_format($plan->monthly_price, 2) }} / Annual NGN {{ number_format($plan->annual_price, 2) }}
                                    </option>
                                @empty
                                    <option value="" disabled>No active subscription plans available</option>
                                @endforelse
                            </select>
                            @error('plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Billing Cycle <span>*</span></label>
                            <select name="billing_cycle" class="form-control {{ $errors->has('billing_cycle') ? 'is-invalid' : '' }}" required>
                                <option value="annual" {{ old('billing_cycle', 'annual') === 'annual' ? 'selected' : '' }}>Annual</option>
                                <option value="monthly" {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                            @error('billing_cycle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subscription Expires <span>*</span></label>
                            <input type="date" name="subscription_expires_at" class="form-control"
                                   value="{{ old('subscription_expires_at', now()->addYear()->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">School Admin Account</div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Admin Full Name <span>*</span></label>
                            <input type="text" name="admin_name" class="form-control" value="{{ old('admin_name') }}" placeholder="School Administrator">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admin Email <span>*</span></label>
                            <input type="email" name="admin_email" class="form-control {{ $errors->has('admin_email') ? 'is-invalid' : '' }}"
                                   value="{{ old('admin_email') }}" placeholder="admin@school.ng">
                            @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admin Password <span>*</span></label>
                            <input type="password" name="admin_password" class="form-control {{ $errors->has('admin_password') ? 'is-invalid' : '' }}"
                                   placeholder="Strong password">
                            <div style="font-size:11px;color:var(--slate-light)">Password is not displayed again and is not sent by email.</div>
                            @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Employment Start Date <span>*</span></label>
                            <input type="date" name="admin_employment_started_at" class="form-control {{ $errors->has('admin_employment_started_at') ? 'is-invalid' : '' }}"
                                   value="{{ old('admin_employment_started_at', now()->format('Y-m-d')) }}">
                            @error('admin_employment_started_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px">
            <button type="submit" class="btn btn-primary">Provision School</button>
            <a href="{{ route('super.tenants') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
