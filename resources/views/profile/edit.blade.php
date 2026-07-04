@extends('layouts.app')
@section('title','My Profile')
@section('page-title','My Profile')

@push('styles')
<style>
.page-wrap{width:100%;max-width:none;display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:18px;align-items:start}
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:0}
.profile-card{grid-column:1/-1;order:1}
.page-wrap>.alert-s,.page-wrap>.alert-e{grid-column:1/-1;order:2}
.details-card{grid-column:1/-1;order:3}
.account-card{grid-column:1/-1;order:4}
.payroll-card{grid-column:1/-1;order:5}
.password-card{grid-column:1/-1;order:6}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;gap:8px}
.cb{padding:22px 20px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:16px;min-width:0}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.06em}
.fc{padding:10px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;min-width:0}
.fc:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white}
.two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 20px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none}
.btn-p{background:var(--indigo);color:white}.btn-p:hover{background:#1D4ED8}
.card form>.btn-p{width:100%}
.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:14px}
.hint{font-size:11px;color:var(--slate-light);margin-top:4px}
/* Photo upload */
.photo-wrap{display:flex;align-items:center;gap:20px;margin-bottom:20px}
.photo-circle{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--border);background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:var(--indigo);flex-shrink:0}
.photo-circle img{width:80px;height:80px;border-radius:50%;object-fit:cover}
.role-badge{display:inline-flex;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#EFF6FF;color:var(--indigo)}
.locked-field{background:#F8FAFC;padding:10px 12px;border-radius:8px;border:1px dashed var(--border);font-size:13px;color:var(--slate);display:flex;align-items:flex-start;gap:8px;min-width:0;max-width:100%;overflow:hidden;overflow-wrap:anywhere;word-break:break-word;line-height:1.4;white-space:normal}
.locked-field svg{width:14px;height:14px;color:var(--slate-light);flex-shrink:0}
.locked-value{display:block;min-width:0;max-width:100%;overflow-wrap:anywhere;word-break:break-word;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
.payroll-card .two,.password-card .two{grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
.account-card .two{grid-template-columns:repeat(2,minmax(0,1fr))}
@media(max-width:700px){
    .page-wrap{gap:14px}
    .two,.account-card .two,.payroll-card .two,.password-card .two{grid-template-columns:1fr}
    .photo-wrap{align-items:flex-start;flex-direction:column}
}
</style>
@endpush

@section('content')
<div class="page-wrap">

{{-- Profile Header --}}
<div class="card profile-card">
    <div class="cb">
        <div class="photo-wrap">
            <div class="photo-circle">
                @if($user->passport_photo)
                    <img src="{{ Storage::url($user->passport_photo) }}" alt="{{ $user->name }}">
                @else
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                @endif
            </div>
            <div>
                <div style="font-size:18px;font-weight:800;color:var(--midnight)">{{ $user->name }}</div>
                <div style="font-size:12px;color:var(--slate);margin-top:2px">{{ $user->email }}</div>
                <div style="margin-top:6px">
                    <span class="role-badge">{{ $user->roleLabel() }}</span>
                    @if($user->staff_id)
                    <span style="font-size:11px;color:var(--slate-light);margin-left:8px;font-family:monospace">{{ $user->staff_id }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Photo upload --}}
        <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data"
              style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            @csrf
            <input type="file" name="passport_photo" id="photoInput" accept="image/*"
                   style="font-size:13px" onchange="this.form.submit()">
            <div class="hint">Max 2MB · JPG, PNG, WebP</div>
        </form>
    </div>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if(session('success_pw'))<div class="alert-s" style="background:#EFF6FF;border-color:#BFDBFE;color:var(--indigo)">🔑 {{ session('success_pw') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif

{{-- Basic Details (Editable) --}}
<div class="card details-card">
    <div class="ch">👤 Personal Details</div>
    <div class="cb">
    <form method="POST" action="{{ route('profile.update') }}">
        @csrf
        <div class="two">
            <div class="fg">
                <label class="fl">Full Name *</label>
                <input name="name" class="fc" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="fg">
                <label class="fl">Phone Number</label>
                <input name="phone" class="fc" value="{{ old('phone', $user->phone) }}" placeholder="08012345678">
            </div>
            <div class="fg">
                <label class="fl">Date of Birth</label>
                <input name="date_of_birth" type="date" class="fc"
                       value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}">
            </div>
            <div class="fg">
                <label class="fl">Email Address</label>
                <div class="locked-field" style="color:var(--slate-light)">
                    {{ $user->email }}
                    <span style="font-size:10px;margin-left:8px;color:var(--indigo)">(update in Email section below ↓)</span>
                </div>
            </div>
        </div>
        <div class="fg">
            <label class="fl">Home Address</label>
            <input name="address" class="fc" value="{{ old('address', $user->address) }}"
                   placeholder="Your residential address">
        </div>
        <button type="submit" class="btn btn-p">💾 Save Profile</button>
    </form>
    </div>
</div>

{{-- Bank & Payroll Details (staff only) --}}
@if($user->isStaff())
<div class="card payroll-card">
    <div class="ch">
        🏦 Bank & Payroll Details
        @if(optional($salarySetting)->bank_details_locked)
            <span style="font-size:11px;font-weight:400;color:var(--slate-light)">— Locked. Contact the accountant to change.</span>
        @else
            <span style="font-size:11px;font-weight:400;color:var(--slate-light)">— Used for your payroll. You can set this once.</span>
        @endif
    </div>
    <div class="cb">
    @if($errors->has('error'))<div class="alert-e" style="margin-bottom:14px">{{ $errors->first('error') }}</div>@endif

    @if(optional($salarySetting)->bank_details_locked)
        <div class="two">
            <div class="fg">
                <label class="fl">Bank Name</label>
                <div class="locked-field">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.5 1L2 6v2h19V6L11.5 1zM4 10v7H2v2h19v-2h-2v-7h-2v7h-3v-7h-2v7h-3v-7H4z"/></svg>
                    {{ $salarySetting->bank_name }}
                </div>
            </div>
            <div class="fg">
                <label class="fl">Account Number</label>
                <div class="locked-field"><span class="locked-value">{{ $salarySetting->account_number }}</span></div>
            </div>
            <div class="fg">
                <label class="fl">Account Name</label>
                <div class="locked-field">{{ $salarySetting->account_name }}</div>
            </div>
            <div class="fg">
                <label class="fl">Tax Identification Number</label>
                <div class="locked-field"><span class="locked-value">{{ $salarySetting->tax_identification_number ?? '—' }}</span></div>
            </div>
            <div class="fg">
                <label class="fl">BVN</label>
                <div class="locked-field"><span class="locked-value">{{ $salarySetting->bvn ? '•••••••' . substr($salarySetting->bvn, -4) : '—' }}</span></div>
            </div>
            <div class="fg">
                <label class="fl">NIN</label>
                <div class="locked-field"><span class="locked-value">{{ $salarySetting->nin ? '•••••••' . substr($salarySetting->nin, -4) : '—' }}</span></div>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('profile.bank-details') }}">
            @csrf
            <div class="two">
                <div class="fg">
                    <label class="fl">Bank Name *</label>
                    <input name="bank_name" class="fc" required value="{{ old('bank_name') }}" placeholder="e.g. GTBank">
                </div>
                <div class="fg">
                    <label class="fl">Account Number *</label>
                    <input name="account_number" class="fc" required maxlength="10" value="{{ old('account_number') }}" placeholder="0000000000">
                </div>
                <div class="fg">
                    <label class="fl">Account Name *</label>
                    <input name="account_name" class="fc" required value="{{ old('account_name') }}" placeholder="As it appears on your bank account">
                </div>
                <div class="fg">
                    <label class="fl">Tax Identification Number (TIN)</label>
                    <input name="tax_identification_number" class="fc" value="{{ old('tax_identification_number') }}" placeholder="Optional">
                </div>
                <div class="fg">
                    <label class="fl">BVN (Bank Verification Number)</label>
                    <input name="bvn" class="fc" maxlength="11" inputmode="numeric" value="{{ old('bvn') }}" placeholder="11-digit BVN">
                </div>
                <div class="fg">
                    <label class="fl">NIN (National Identification Number)</label>
                    <input name="nin" class="fc" maxlength="11" inputmode="numeric" value="{{ old('nin') }}" placeholder="11-digit NIN">
                </div>
            </div>
            <div class="hint" style="margin-bottom:12px">Once you save, these fields lock — only the accountant can change them afterward. Please double-check before submitting.</div>
            <button type="submit" class="btn btn-p" style="background:#059669">💾 Save Bank Details</button>
        </form>
    @endif
    </div>
</div>
@endif

{{-- Email & Account Information --}}
<div class="card account-card">
    <div class="ch">📧 Email Address &amp; Account</div>
    <div class="cb">

    @error('email')<div class="alert-e" style="margin-bottom:14px">{{ $message }}</div>@enderror
    @error('current_password')<div class="alert-e" style="margin-bottom:14px">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('profile.email') }}">
        @csrf
        <div class="two">
            <div class="fg">
                <label class="fl">Email Address</label>
                <input type="email" name="email" class="fc" value="{{ old('email', $user->email) }}" required>
                <div class="hint" style="margin-top:4px">Used to log in. Must be unique.</div>
            </div>
            <div class="fg">
                <label class="fl">Confirm with Password</label>
                <input type="password" name="current_password" class="fc" placeholder="Your current password" autocomplete="current-password">
                <div class="hint" style="margin-top:4px">Required to confirm the change.</div>
            </div>
        </div>
        <button type="submit" class="btn btn-p btn-sm">Update Email</button>
    </form>

    <div style="border-top:1px solid var(--border);margin:16px 0"></div>

    <div class="two">
        @if($user->staff_id)
        <div class="fg">
            <label class="fl">Staff ID</label>
            <div class="locked-field">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                {{ $user->staff_id }}
            </div>
        </div>
        @endif
        <div class="fg">
            <label class="fl">Role</label>
            <div class="locked-field">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                {{ $user->roleLabel() }}
            </div>
        </div>
        <div class="fg">
            <label class="fl">School</label>
            <div class="locked-field">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12z"/></svg>
                {{ optional($user->tenant)->name ?? '—' }}
            </div>
        </div>
        <div class="fg">
            <label class="fl">Last Login</label>
            <div class="locked-field">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'First session' }}
            </div>
        </div>
    </div>
    </div>
</div>

{{-- Change Password --}}
<div class="card password-card">
    <div class="ch">🔑 Change Password</div>
    <div class="cb">
    <form method="POST" action="{{ route('profile.password') }}">
        @csrf
        @error('current_password')<div class="alert-e" style="margin-bottom:14px">{{ $message }}</div>@enderror
        <div class="two">
            <div class="fg">
                <label class="fl">Current Password *</label>
                <input name="current_password" type="password" class="fc" required autocomplete="current-password">
            </div>
            <div style="display:none"></div>
            <div class="fg">
                <label class="fl">New Password *</label>
                <input name="password" type="password" class="fc" required autocomplete="new-password">
                <div class="hint">Minimum 8 characters</div>
            </div>
            <div class="fg">
                <label class="fl">Confirm New Password *</label>
                <input name="password_confirmation" type="password" class="fc" required autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn btn-p" style="background:#059669">🔑 Change Password</button>
    </form>
    </div>
</div>

</div>
@endsection
