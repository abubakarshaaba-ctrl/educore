@extends('layouts.super')
@section('title', 'Edit School')
@section('page-title', 'Edit School')

@push('styles')
<style>
    .form-page{width:100%}
    .breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:18px;flex-wrap:wrap}
    .breadcrumb a{color:var(--indigo);text-decoration:none;font-weight:600}
    .grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;align-items:start}
    .card{background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:16px;overflow:hidden}
    .card-header{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:700;color:var(--midnight)}
    .card-body{padding:20px}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .form-group{display:flex;flex-direction:column;gap:6px}
    .form-group.full{grid-column:1 / -1}
    .form-label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
    .form-label span{color:var(--crimson)}
    .form-control{padding:10px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
    .form-control:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(215,154,33,.12);background:white}
    .is-invalid{border-color:var(--crimson)!important}
    .invalid-feedback{font-size:12px;color:var(--crimson)}
    .hint{font-size:11px;color:var(--slate-light);line-height:1.45}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;font-weight:700;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
    .btn-primary{background:var(--indigo);color:white}
    .btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
    .alert-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px}
    .alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px}
    .url-row{padding:10px 0;border-bottom:1px solid var(--border);font-size:12px}
    .url-row:last-child{border-bottom:none}
    .url-row strong{display:block;color:var(--slate);font-size:10px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
    .url-row a{color:var(--indigo);overflow-wrap:anywhere}
    @media(max-width:900px){.grid,.form-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="form-page">
    <div class="breadcrumb">
        <a href="{{ route('super.dashboard') }}">Super Admin</a>
        <span>/</span>
        <a href="{{ route('super.tenants') }}">Schools</a>
        <span>/</span>
        <a href="{{ route('super.tenant.show', $tenant) }}">{{ $tenant->name }}</a>
        <span>/ Edit</span>
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

    <div class="grid">
        <form method="POST" action="{{ route('super.tenant.update', $tenant) }}">
            @csrf
            @method('PATCH')

            <div class="card">
                <div class="card-header">School Identity</div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">School Name <span>*</span></label>
                            <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $tenant->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status <span>*</span></label>
                            <select name="status" class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', $tenant->status) === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug <span>*</span></label>
                            <input type="text" name="slug" class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}" value="{{ old('slug', $tenant->slug) }}" required>
                            <div class="hint">Controls /school/{slug}, /school/{slug}/login and /apply/{slug}. Reserved platform paths are blocked.</div>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Local Subdomain</label>
                            <input type="text" name="subdomain" class="form-control {{ $errors->has('subdomain') ? 'is-invalid' : '' }}" value="{{ old('subdomain', $tenant->subdomain) }}">
                            <div class="hint">Optional. Lowercase letters, numbers and hyphens only.</div>
                            @error('subdomain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email', $tenant->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}" value="{{ old('phone', $tenant->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" value="{{ old('address', $tenant->address) }}">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subscription Expires</label>
                            <input type="date" name="subscription_expires_at" class="form-control {{ $errors->has('subscription_expires_at') ? 'is-invalid' : '' }}" value="{{ old('subscription_expires_at', optional($tenant->subscription_expires_at)->format('Y-m-d')) }}">
                            @error('subscription_expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Custom Domain</label>
                            <input type="text" name="custom_domain" class="form-control {{ $errors->has('custom_domain') ? 'is-invalid' : '' }}" value="{{ old('custom_domain', $tenant->custom_domain) }}" placeholder="portal.school.local">
                            <div class="hint">Changing this clears verification until it is verified again.</div>
                            @error('custom_domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Branding</div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Motto</label>
                            <input type="text" name="motto" class="form-control {{ $errors->has('motto') ? 'is-invalid' : '' }}" value="{{ old('motto', $tenant->motto) }}">
                            @error('motto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Logo Path</label>
                            <input type="text" name="logo_path" class="form-control {{ $errors->has('logo_path') ? 'is-invalid' : '' }}" value="{{ old('logo_path', $tenant->logo_path) }}" placeholder="storage/logos/school.png">
                            @error('logo_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @foreach(['theme_primary' => 'Primary Colour', 'theme_accent' => 'Accent Colour', 'theme_sidebar' => 'Sidebar Colour', 'primary_color' => 'White-label Primary', 'secondary_color' => 'White-label Secondary'] as $field => $label)
                            <div class="form-group">
                                <label class="form-label">{{ $label }}</label>
                                <input type="text" name="{{ $field }}" class="form-control {{ $errors->has($field) ? 'is-invalid' : '' }}" value="{{ old($field, $tenant->{$field}) }}" placeholder="#071E45">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary">Save School</button>
                <a href="{{ route('super.tenant.show', $tenant) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>

        <div>
            <div class="card">
                <div class="card-header">Generated URLs</div>
                <div class="card-body">
                    <div class="url-row"><strong>School Portal</strong><a href="{{ $urls['school_portal'] }}" target="_blank" rel="noopener">{{ $urls['school_portal'] }}</a></div>
                    <div class="url-row"><strong>School Login</strong><a href="{{ $urls['school_login'] }}" target="_blank" rel="noopener">{{ $urls['school_login'] }}</a></div>
                    <div class="url-row"><strong>Admissions</strong><a href="{{ $urls['admissions'] }}" target="_blank" rel="noopener">{{ $urls['admissions'] }}</a></div>
                    <div class="url-row"><strong>Local Subdomain</strong>@if($urls['local_subdomain_login'])<a href="{{ $urls['local_subdomain_login'] }}" target="_blank" rel="noopener">{{ $urls['local_subdomain_login'] }}</a>@else - @endif</div>
                    <div class="url-row"><strong>Preferred Login</strong><a href="{{ $urls['preferred_login'] }}" target="_blank" rel="noopener">{{ $urls['preferred_login'] }}</a></div>
                    <div class="url-row"><strong>Account Status</strong><a href="{{ $urls['account_status'] }}" target="_blank" rel="noopener">{{ $urls['account_status'] }}</a></div>
                    <div class="url-row"><strong>Verified Custom Domain</strong>{{ $urls['custom_domain'] ?: 'Not verified or not configured' }}</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Safety Notes</div>
                <div class="card-body" style="font-size:12px;line-height:1.6;color:var(--slate)">
                    This form never accepts a tenant ID and cannot change the tenant primary key. Existing users, students,
                    enrolments, results, billing, subscriptions, audit logs and portal records remain attached to this school.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
