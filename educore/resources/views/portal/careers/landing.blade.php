<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Careers – {{ $tenant->name }}</title>
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --navy:   #0B1D3A;
    --blue:   #1A56DB;
    --slate:  #64748B;
    --border: #E2E8F0;
    --bg:     #F8FAFC;
    --green:  #10B981;
}
body { font-family:'Inter',-apple-system,sans-serif; background:var(--bg); color:var(--navy); line-height:1.6; min-height:100vh; }
.topnav { position:sticky; top:0; z-index:50; background:rgba(11,29,58,0.97); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.07); padding:0 24px; display:flex; align-items:center; justify-content:space-between; height:60px; }
.topnav-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
.topnav-logo { width:36px; height:36px; border-radius:9px; background:var(--blue); display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; color:white; overflow:hidden; flex-shrink:0; }
.topnav-logo img { width:100%; height:100%; object-fit:cover; }
.topnav-name { font-size:14px; font-weight:700; color:white; letter-spacing:-0.01em; }
.nav-link { font-size:13px; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; padding:6px 14px; border-radius:8px; }
.nav-link:hover { color:white; background:rgba(255,255,255,0.08); }
.hero { background:linear-gradient(160deg, var(--navy) 0%, #0F2952 60%, #1A3A6A 100%); color:white; padding:64px 24px 56px; text-align:center; }
.hero h1 { font-size:clamp(26px, 5vw, 44px); font-weight:800; letter-spacing:-0.03em; margin-bottom:14px; }
.hero h1 span { background:linear-gradient(90deg, #60A5FA, #93C5FD); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.hero-sub { font-size:16px; color:rgba(255,255,255,0.65); max-width:520px; margin:0 auto; }
.section { max-width:820px; margin:0 auto; padding:48px 24px; }
.postings { display:flex; flex-direction:column; gap:16px; }
.posting-card { background:white; border:1px solid var(--border); border-radius:14px; padding:24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; transition:box-shadow 150ms, transform 150ms; }
.posting-card:hover { box-shadow:0 8px 24px rgba(0,0,0,0.06); transform:translateY(-2px); }
.posting-title { font-size:17px; font-weight:700; color:var(--navy); margin-bottom:4px; }
.posting-meta { font-size:13px; color:var(--slate); }
.btn-view { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; font-size:13px; font-weight:700; border-radius:10px; text-decoration:none; background:var(--blue); color:white; }
.btn-view:hover { background:#1946C0; }
.empty { text-align:center; padding:60px 24px; color:var(--slate); }
.empty-icon { font-size:36px; margin-bottom:14px; }
.portal-footer { background:var(--navy); color:rgba(255,255,255,0.45); text-align:center; padding:26px 24px; font-size:12px; }
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
    <div><a href="{{ route('tenant.portal.landing', $tenant->slug) }}" class="nav-link">School Portal</a></div>
</nav>

<section class="hero">
    <h1>Careers at<br><span>{{ $tenant->name }}</span></h1>
    <p class="hero-sub">Join our team. Browse current openings below and apply directly online.</p>
</section>

<div class="section">
    @if($postings->isEmpty())
        <div class="empty">
            <div class="empty-icon">📭</div>
            <p>There are no open positions right now. Please check back later.</p>
        </div>
    @else
        <div class="postings">
            @foreach($postings as $p)
            <div class="posting-card">
                <div>
                    <div class="posting-title">{{ $p->title }}</div>
                    <div class="posting-meta">
                        @if($p->department){{ $p->department }} &nbsp;·&nbsp; @endif
                        @if($p->closes_at)Closes {{ $p->closes_at->format('d M Y') }}@else Open until filled @endif
                    </div>
                </div>
                <a href="{{ route('careers.show', [$tenant->slug, $p->id]) }}" class="btn-view">View & Apply →</a>
            </div>
            @endforeach
        </div>
    @endif
</div>

<footer class="portal-footer">
    &copy; {{ date('Y') }} {{ $tenant->name }}
</footer>
</body>
</html>
