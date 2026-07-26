<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $posting->title }} – {{ $tenant->name }}</title>
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --navy:   #0B1D3A;
    --blue:   #1A56DB;
    --slate:  #64748B;
    --border: #E2E8F0;
    --bg:     #F8FAFC;
    --green:  #10B981;
    --red:    #EF4444;
}
body { font-family:'Inter',-apple-system,sans-serif; background:var(--bg); color:var(--navy); line-height:1.6; min-height:100vh; }
.topnav { position:sticky; top:0; z-index:50; background:rgba(11,29,58,0.97); backdrop-filter:blur(12px); padding:0 24px; display:flex; align-items:center; justify-content:space-between; height:60px; }
.topnav-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
.topnav-logo { width:36px; height:36px; border-radius:9px; background:var(--blue); display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; color:white; overflow:hidden; flex-shrink:0; }
.topnav-logo img { width:100%; height:100%; object-fit:cover; }
.topnav-name { font-size:14px; font-weight:700; color:white; }
.nav-link { font-size:13px; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; padding:6px 14px; border-radius:8px; }
.nav-link:hover { color:white; background:rgba(255,255,255,0.08); }
.wrap { max-width:720px; margin:0 auto; padding:40px 24px 64px; }
.back { font-size:13px; color:var(--blue); text-decoration:none; display:inline-flex; align-items:center; gap:4px; margin-bottom:20px; }
.card { background:white; border:1px solid var(--border); border-radius:16px; padding:32px; margin-bottom:20px; }
.title { font-size:26px; font-weight:800; color:var(--navy); letter-spacing:-0.02em; margin-bottom:8px; }
.meta { font-size:13px; color:var(--slate); margin-bottom:20px; }
.body-text { font-size:14px; color:#334155; white-space:pre-line; margin-bottom:16px; }
.subhead { font-size:13px; font-weight:700; color:var(--navy); text-transform:uppercase; letter-spacing:0.05em; margin:20px 0 8px; }
.fg { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
.fl { font-size:12px; font-weight:600; color:var(--slate); text-transform:uppercase; letter-spacing:0.05em; }
.fc { padding:11px 14px; font-size:14px; font-family:inherit; border:1px solid var(--border); border-radius:10px; background:#F8FAFC; outline:none; width:100%; }
.fc:focus { border-color:var(--blue); background:white; }
.fr { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.btn-submit { width:100%; padding:14px; font-size:15px; font-weight:700; border-radius:12px; border:none; cursor:pointer; background:var(--blue); color:white; font-family:inherit; }
.btn-submit:hover { background:#1946C0; }
.alert { padding:14px 18px; border-radius:10px; font-size:13px; margin-bottom:20px; }
.alert-success { background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0; }
.alert-error { background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; }
@media(max-width:600px) { .fr { grid-template-columns:1fr; } }
</style>
</head>
<body>
<nav class="topnav">
    <a href="#" class="topnav-brand">
        <div class="topnav-logo">
            @if($tenant->logo_path)
                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}">
            @else
                {{ strtoupper(substr($tenant->name, 0, 1)) }}
            @endif
        </div>
        <span class="topnav-name">{{ $tenant->name }}</span>
    </a>
    <div><a href="{{ route('careers.landing', $tenant->slug) }}" class="nav-link">← All Openings</a></div>
</nav>

<div class="wrap">
    <a href="{{ route('careers.landing', $tenant->slug) }}" class="back">← Back to all openings</a>

    @if(session('success'))
        <div class="alert alert-success">✓ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="title">{{ $posting->title }}</div>
        <div class="meta">
            @if($posting->department){{ $posting->department }} &nbsp;·&nbsp; @endif
            @if($posting->closes_at)Closes {{ $posting->closes_at->format('d F Y') }}@else Open until filled @endif
        </div>
        @if($posting->description)
            <div class="subhead">Description</div>
            <div class="body-text">{{ $posting->description }}</div>
        @endif
        @if($posting->requirements)
            <div class="subhead">Requirements</div>
            <div class="body-text">{{ $posting->requirements }}</div>
        @endif
    </div>

    <div class="card">
        <div class="subhead" style="margin-top:0">Apply for This Position</div>
        <form method="POST" action="{{ url()->current() . '/apply' }}" enctype="multipart/form-data">
            @csrf
            <div class="fr">
                <div class="fg"><label class="fl">Full Name *</label><input type="text" name="name" class="fc" value="{{ old('name') }}" required></div>
                <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fc" value="{{ old('email') }}"></div>
            </div>
            <div class="fr">
                <div class="fg"><label class="fl">Phone</label><input type="text" name="phone" class="fc" value="{{ old('phone') }}"></div>
                <div class="fg"><label class="fl">Resume (PDF or Word) *</label><input type="file" name="resume" class="fc" required></div>
            </div>
            <div class="fg">
                <label class="fl">Certificates (PDF or image, select all that apply) *</label>
                <input type="file" name="certificates[]" class="fc" multiple required>
            </div>
            <div class="fg"><label class="fl">Cover Letter</label><textarea name="cover_letter" class="fc" rows="4">{{ old('cover_letter') }}</textarea></div>
            <button type="submit" class="btn-submit">Submit Application</button>
        </form>
    </div>
</div>
</body>
</html>
