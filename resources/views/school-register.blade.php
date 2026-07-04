<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Get Started — EduCore</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#071E45;--gold:#D79A21;--white:#FFFFFF;--off:#F7F9FC;
  --slate:#475569;--border:#E2E8F0;--red:#DC2626;--green:#059669;
  --font:'Plus Jakarta Sans',system-ui,sans-serif;
}
body{font-family:var(--font);background:var(--off);color:var(--navy);min-height:100vh;display:flex;flex-direction:column}
.nav{background:rgba(7,30,69,.97);padding:0 5vw;height:64px;display:flex;align-items:center;justify-content:space-between}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
.nav-brand img{width:32px;height:32px;border-radius:8px}
.nav-brand-name{font-size:16px;font-weight:800;color:#fff}
.nav-brand-name span{color:var(--gold)}
.nav a.login{color:rgba(255,255,255,.7);font-size:13px;font-weight:500;text-decoration:none}
.nav a.login:hover{color:#fff}

.page{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 16px}
.card{background:white;border-radius:16px;box-shadow:0 8px 40px rgba(7,30,69,.1);width:100%;max-width:480px;overflow:hidden}
.card-head{background:var(--navy);padding:28px 32px}
.card-head h1{font-size:22px;font-weight:800;color:white;margin-bottom:4px}
.card-head p{font-size:13px;color:rgba(255,255,255,.6)}
.trial-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(215,154,33,.15);border:1px solid rgba(215,154,33,.3);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;color:var(--gold);margin-bottom:14px}
.card-body{padding:28px 32px}

.fg{display:flex;flex-direction:column;gap:4px;margin-bottom:16px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.06em}
.fc{padding:10px 13px;font-size:14px;font-family:inherit;border:1.5px solid var(--border);border-radius:9px;background:#F8FAFC;outline:none;width:100%;transition:border 180ms,background 180ms;color:var(--navy)}
.fc:focus{border-color:var(--navy);background:white}
.fc.error{border-color:var(--red)}
.err{font-size:12px;color:var(--red);margin-top:3px}

.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}

.btn-submit{width:100%;padding:13px;font-size:14px;font-weight:700;font-family:inherit;background:var(--navy);color:white;border:none;border-radius:9px;cursor:pointer;transition:background 150ms;margin-top:4px}
.btn-submit:hover{background:#0d2a5e}
.btn-submit:disabled{opacity:.6;cursor:not-allowed}

.divider{text-align:center;font-size:12px;color:var(--slate);margin:18px 0;position:relative}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:var(--border)}
.divider::before{left:0}.divider::after{right:0}

.already{text-align:center;font-size:13px;color:var(--slate)}
.already a{color:var(--navy);font-weight:600;text-decoration:none}
.already a:hover{text-decoration:underline}

.perks{display:flex;flex-direction:column;gap:7px;margin-bottom:22px}
.perk{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate)}
.perk::before{content:'✓';width:18px;height:18px;background:#ECFDF5;color:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}

.alert-err{background:#FEF2F2;border:1px solid #FECACA;border-radius:9px;padding:11px 14px;font-size:13px;color:var(--red);margin-bottom:16px}
.hint{font-size:11px;color:var(--slate);margin-top:3px}

@media(max-width:480px){.row{grid-template-columns:1fr}.card-head,.card-body{padding:22px 20px}}
</style>
</head>
<body>
<nav class="nav">
    <a href="{{ route('home') }}" class="nav-brand">
        <img src="/brand/educore-icon.svg" alt="EduCore">
        <span class="nav-brand-name">Edu<span>Core</span></span>
    </a>
    <a href="{{ route('admin.login') }}" class="login">Already have an account? Sign in</a>
</nav>

<div class="page">
<div class="card">
    <div class="card-head">
        <div class="trial-badge">⚡ 30-Day Free Trial</div>
        <h1>Get started with EduCore</h1>
        <p>Set up your school in minutes. No credit card required.</p>
    </div>
    <div class="card-body">

        <div class="perks">
            <div class="perk">Full access to all features for 30 days</div>
            <div class="perk">Admissions, fees, payroll & student portals</div>
            <div class="perk">No credit card required to get started</div>
        </div>

        @if($errors->any() && !$errors->has('school_name') && !$errors->has('admin_name') && !$errors->has('admin_email') && !$errors->has('phone') && !$errors->has('password'))
        <div class="alert-err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('school.register.post') }}" id="regForm">
            @csrf
            {{-- Preserve agent referral code if the school arrived via a referral link --}}
            <input type="hidden" name="ref" value="{{ old('ref', $ref ?? '') }}">

            <div class="fg">
                <label class="fl">School Name</label>
                <input type="text" name="school_name" class="fc {{ $errors->has('school_name') || $errors->has('slug') ? 'error' : '' }}"
                    value="{{ old('school_name') }}" placeholder="e.g. Bright Future Academy" required>
                @if($errors->has('school_name'))<div class="err">{{ $errors->first('school_name') }}</div>@endif
                @if($errors->has('slug'))<div class="err">This school name is already taken. Try a different name.</div>@endif
            </div>

            <div class="row">
                <div class="fg">
                    <label class="fl">Your Full Name</label>
                    <input type="text" name="admin_name" class="fc {{ $errors->has('admin_name') ? 'error' : '' }}"
                        value="{{ old('admin_name') }}" placeholder="John Doe" required>
                    @if($errors->has('admin_name'))<div class="err">{{ $errors->first('admin_name') }}</div>@endif
                </div>
                <div class="fg">
                    <label class="fl">Phone Number</label>
                    <input type="tel" name="phone" class="fc {{ $errors->has('phone') ? 'error' : '' }}"
                        value="{{ old('phone') }}" placeholder="08012345678" required>
                    @if($errors->has('phone'))<div class="err">{{ $errors->first('phone') }}</div>@endif
                </div>
            </div>

            <div class="fg">
                <label class="fl">Email Address</label>
                <input type="email" name="admin_email" class="fc {{ $errors->has('admin_email') ? 'error' : '' }}"
                    value="{{ old('admin_email') }}" placeholder="admin@yourschool.edu.ng" required>
                @if($errors->has('admin_email'))<div class="err">{{ $errors->first('admin_email') }}</div>@endif
            </div>

            <div class="row">
                <div class="fg">
                    <label class="fl">Password</label>
                    <input type="password" name="password" class="fc {{ $errors->has('password') ? 'error' : '' }}"
                        placeholder="Min. 8 characters" required>
                    @if($errors->has('password'))<div class="err">{{ $errors->first('password') }}</div>@endif
                </div>
                <div class="fg">
                    <label class="fl">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="fc" placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Create My School Account →</button>
        </form>

        <div class="divider">or</div>
        <div class="already">Already have an account? <a href="{{ route('admin.login') }}">Sign in</a></div>
    </div>
</div>
</div>
</body>
</html>
