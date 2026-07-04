<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title','Agent Portal') — EduCore</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--brand-navy:#071E45;--brand-gold:#D79A21;--navy:var(--brand-navy);--navy2:#1E293B;--gold:var(--brand-gold);--gold-dk:#B8810D;--gold-bg:#FEF9EC;--green:#059669;--amber:#D97706;--red:#DC2626;--border:#E2E8F0;--bg:#F4F6FA;--white:#FFFFFF;--nav-w:230px;}
body{font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif;background:var(--bg);color:var(--navy);min-height:100vh;}
.sidebar{position:fixed;top:0;left:0;width:var(--nav-w);height:100vh;background:var(--navy);display:flex;flex-direction:column;overflow-y:auto;z-index:100;border-right:1px solid rgba(255,255,255,0.06);}
.sb-logo{padding:18px 14px;border-bottom:1px solid rgba(255,255,255,0.07);}
.logo-row{display:flex;align-items:center;gap:9px;}
.logo-icon{width:32px;height:32px;background:transparent;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.logo-icon svg{width:16px;height:16px;fill:white;}
.logo-txt{font-size:12px;font-weight:800;color:white;}
.logo-txt span{display:block;font-size:10px;font-weight:400;color:#475569;}
.sb-agent{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;align-items:center;gap:9px;}
.ag-av{width:34px;height:34px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:white;flex-shrink:0;}
.ag-name{font-size:12px;font-weight:700;color:white;}
.ag-code{font-size:10px;color:#475569;font-family:monospace;}
.nav-section{padding:12px 10px 4px;}
.nav-lbl{font-size:9px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.09em;padding:0 8px;margin-bottom:4px;}
.nav-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;font-size:12.5px;font-weight:500;color:#9CA3AF;text-decoration:none;transition:all 120ms;margin-bottom:1px;}
.nav-item:hover{background:rgba(255,255,255,0.06);color:#E5E7EB;}
.nav-item.active{background:rgba(215,154,33,0.2);color:#F2C35B;border-left:3px solid #D79A21;padding-left:7px;}
.nav-item svg{width:15px;height:15px;flex-shrink:0;opacity:.7;}
.nav-item.active svg,.nav-item:hover svg{opacity:1;}
.unread-badge{background:#DC2626;color:white;font-size:9px;font-weight:800;padding:1px 6px;border-radius:20px;margin-left:auto;}
.sb-footer{margin-top:auto;padding:12px 10px;border-top:1px solid rgba(255,255,255,0.06);}
.logout-btn{width:100%;padding:9px 12px;background:rgba(220,38,38,0.15);border:1px solid rgba(220,38,38,0.25);color:#FCA5A5;border-radius:7px;cursor:pointer;font-size:11px;font-weight:700;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:7px;transition:all 150ms;text-transform:uppercase;letter-spacing:.05em;}
.logout-btn:hover{background:#DC2626;color:white;}
.main{margin-left:var(--nav-w);min-height:100vh;display:flex;flex-direction:column;}
.topbar{height:52px;background:white;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:50;box-shadow:0 1px 3px rgba(0,0,0,0.05);}
.topbar-title{font-size:15px;font-weight:800;color:var(--navy);}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;font-size:12px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms;}
.btn-primary{background:var(--gold);color:white;}
.btn-primary:hover{background:var(--gold-dk);}
.btn-sm{padding:4px 10px;font-size:11px;}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;}
.badge-green{background:#ECFDF5;color:#059669;}
.badge-amber{background:#FFFBEB;color:#D97706;}
.badge-red{background:#FEF2F2;color:#DC2626;}
.badge-gold{background:var(--gold-bg);color:var(--gold);}
.page-body{padding:24px;flex:1;}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px;}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:16px;}

/* ── Mobile ── */
.ag-hamburger{display:none;align-items:center;justify-content:center;width:36px;height:36px;background:rgba(7,30,69,.08);border:none;border-radius:8px;cursor:pointer;flex-shrink:0;}
.ag-hamburger svg{width:20px;height:20px;fill:var(--navy);}
.ag-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:149;backdrop-filter:blur(2px);}
.ag-overlay.open{display:block;}
@media(max-width:768px){
    :root{--nav-w:0px;}
    .sidebar{width:230px;transform:translateX(-100%);transition:transform 250ms;z-index:200;}
    .sidebar.open{transform:translateX(0);}
    .main{margin-left:0;}
    .topbar{padding:0 14px;}
    .page-body{padding:14px;}
    .ag-hamburger{display:flex;}
}
@media(max-width:480px){
    .page-body{padding:10px;}
}
</style>
{!! \App\Helpers\ThemeHelper::css() !!}
@stack('styles')
</head>
<body>

<div class="ag-overlay" id="agOverlay" onclick="closeAgentSidebar()"></div>
<aside class="sidebar" id="agentSidebar">
    <div class="sb-logo">
        <div class="logo-row">
            <div class="logo-icon"><img src="/brand/educore-icon.svg" alt="EduCore" style="width:32px;height:32px;border-radius:8px"></div>
            <div class="logo-txt">EduCore <span>Agent Portal</span></div>
        </div>
    </div>
    @if($currentAgent)
    <div class="sb-agent">
        <div class="ag-av">{{ strtoupper(substr($currentAgent->name,0,1)) }}</div>
        <div>
            <div class="ag-name">{{ Str::limit($currentAgent->name,18) }}</div>
            <div class="ag-code">{{ $currentAgent->referral_code }}</div>
        </div>
    </div>
    @endif

    
    <div class="nav-section">
        <div class="nav-lbl">Menu</div>
        <a href="{{ route('agent.portal.dashboard') }}" class="nav-item {{ request()->routeIs('agent.portal.dashboard') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            Dashboard
        </a>
        <a href="{{ route('agent.portal.schools') }}" class="nav-item {{ request()->routeIs('agent.portal.schools') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
            My Schools
        </a>
        <a href="{{ route('agent.portal.earnings') }}" class="nav-item {{ request()->routeIs('agent.portal.earnings') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            Earnings & Payouts
        </a>
        <a href="{{ route('agent.portal.messages') }}" class="nav-item {{ request()->routeIs('agent.portal.messages') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
            Messages
            @if($unread > 0)<span class="unread-badge">{{ $unread }}</span>@endif
        </a>
        <a href="{{ route('agent.portal.profile') }}" class="nav-item {{ request()->routeIs('agent.portal.profile') ? 'active':'' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            My Profile & Bank
        </a>
    </div>

    <div class="sb-footer">
        <form method="POST" action="{{ route('agent.portal.logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                Sign Out
            </button>
        </form>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <button class="ag-hamburger" onclick="toggleAgentSidebar()" aria-label="Menu">
            <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <div class="topbar-title">@yield('title','Dashboard')</div>
        <div style="display:flex;align-items:center;gap:12px">
            @if($currentAgent)
            <div style="font-size:12px;color:#64748B">Ref code: <strong style="font-family:monospace;color:#2563EB">{{ $currentAgent->referral_code }}</strong></div>
            @endif
        </div>
    </header>
    <div class="page-body">
        @if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif
        @yield('content')
    </div>
</div>
<script>
function toggleAgentSidebar(){
    document.getElementById('agentSidebar').classList.toggle('open');
    document.getElementById('agOverlay').classList.toggle('open');
    document.body.style.overflow=document.getElementById('agentSidebar').classList.contains('open')?'hidden':'';
}
function closeAgentSidebar(){
    document.getElementById('agentSidebar').classList.remove('open');
    document.getElementById('agOverlay').classList.remove('open');
    document.body.style.overflow='';
}
document.querySelectorAll('.nav-item').forEach(function(el){
    el.addEventListener('click',function(){if(window.innerWidth<=768)closeAgentSidebar();});
});
</script>
</body>
</html>
