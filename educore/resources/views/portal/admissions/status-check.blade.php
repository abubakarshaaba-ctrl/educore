<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Check Status — {{ $tenant->name }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--navy:#071E45;--gold:#D79A21;--ink:#101828;--muted:#667085;--line:#DFE6F0}
body{font-family:"Segoe UI",Arial,sans-serif;background:radial-gradient(circle at 78% 12%,rgba(215,154,33,.18),transparent 24%),linear-gradient(125deg,#03132D,var(--navy) 62%,#0A326F);min-height:100vh;padding:24px;display:grid;place-items:center}
.shell{width:min(940px,100%);display:grid;grid-template-columns:.9fr 1.1fr;background:white;border-radius:24px;overflow:hidden;box-shadow:0 32px 90px rgba(0,0,0,.35)}
.aside{padding:46px 40px;background:var(--navy);color:white;position:relative;overflow:hidden}
.aside:after{content:"";position:absolute;width:280px;height:280px;border:50px solid rgba(215,154,33,.08);border-radius:50%;right:-140px;bottom:-150px}
.school{display:flex;align-items:center;gap:11px;position:relative;z-index:1}.school-logo{width:44px;height:44px;border-radius:11px;background:white;padding:4px;object-fit:contain}.school strong{font-size:14px}.school span{display:block;font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.55);margin-top:3px}
.aside h2{font-size:34px;line-height:1.08;letter-spacing:-.04em;margin:70px 0 14px;position:relative;z-index:1}.aside h2 em{font-style:normal;color:#F2C35B}
.aside p{font-size:13px;line-height:1.7;color:rgba(255,255,255,.62);position:relative;z-index:1}
.secure{display:flex;gap:9px;align-items:flex-start;margin-top:30px;padding:13px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.06);font-size:10px;color:rgba(255,255,255,.68);position:relative;z-index:1}.secure b{color:#6EE7B7}
.card{padding:46px 48px}
.top{margin-bottom:28px}.eyebrow{color:#9B6807;text-transform:uppercase;letter-spacing:.13em;font-size:9px;font-weight:900}.top h1{font-size:26px;font-weight:800;color:var(--navy);margin:8px 0}.top p{font-size:13px;color:var(--muted);line-height:1.6}
.fg{display:flex;flex-direction:column;gap:7px;margin-bottom:17px}
label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#475467}
input{padding:13px 14px;font-size:14px;border:1.5px solid var(--line);border-radius:10px;background:#F8FAFC;outline:none;width:100%;transition:.18s;font-family:inherit;color:var(--ink)}
input:focus{border-color:var(--gold);background:white;box-shadow:0 0 0 3px rgba(215,154,33,.12)}
.btn{width:100%;padding:14px;font-size:13px;font-weight:800;background:var(--gold);color:var(--navy);border:none;border-radius:10px;cursor:pointer;font-family:inherit;transition:.18s;box-shadow:0 10px 22px rgba(215,154,33,.18)}
.btn:hover{background:#F2C35B;transform:translateY(-1px)}
.error{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:12px;color:#B42318;margin-bottom:16px}
.back{display:block;text-align:center;margin-top:18px;font-size:11px;font-weight:700;color:var(--navy);text-decoration:none}
.hint{font-size:10px;color:#98A2B3;margin-top:-8px;margin-bottom:17px}
@media(max-width:720px){body{padding:14px}.shell{grid-template-columns:1fr}.aside{padding:24px}.aside h2,.aside>p,.secure{display:none}.card{padding:30px 22px}}
</style>
</head>
<body>
@php $logoPath = $tenant->logo_path ? asset('storage/'.ltrim($tenant->logo_path,'/')) : asset('brand/educore-icon.svg'); @endphp
<div class="shell">
<aside class="aside">
    <div class="school"><img class="school-logo" src="{{ $logoPath }}" alt=""><div><strong>{{ $tenant->name }}</strong><span>Online Admissions</span></div></div>
    <h2>Your admission journey, <em>in view.</em></h2>
    <p>See the latest school decision securely without visiting the admissions office.</p>
    <div class="secure"><b>✓</b><span>Your guardian phone number is used as a second verification step and is not displayed publicly.</span></div>
</aside>
<div class="card">
    <div class="top">
        <div class="eyebrow">Secure application tracker</div>
        <h1>Check Application Status</h1>
        <p>Enter your application number and guardian phone number to see your current status.</p>
    </div>

    @if($errors->any())
    <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ url('/apply/status') }}">
    @csrf
    <div class="fg">
        <label>Application Number</label>
        <input type="text" name="application_number" placeholder="e.g. APP-SCHOOL-2025-XXXXXX" required style="text-transform:uppercase">
    </div>
    <div class="fg">
        <label>Guardian Phone Number</label>
        <input type="tel" name="guardian_phone" placeholder="e.g. 08012345678" required>
    </div>
    <div class="hint">Use the same phone number entered on the application form.</div>
    <button type="submit" class="btn">Check Status</button>
    </form>
    <a href="{{ url('/apply') }}" class="back">&#8592; Back to Portal</a>
</div>
</div>
</body>
</html>
