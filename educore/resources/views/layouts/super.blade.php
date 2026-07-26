<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — EduCore Platform</title>
    <style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
:root {
    --midnight: #071E45;
    --navy:     #1E3A5F;
    --indigo:   #D79A21;
    --indigo-dk:#B8810D;
    --indigo-bg:#FEF9EC;
    --emerald:  #059669;
    --amber:    #D97706;
    --crimson:  #DC2626;
    --slate:       #475569;
    --slate-light: #94A3B8;
    --border:      #E2E8F0;
    --bg:          #F4F6FA;
    --nav-w:    240px;
}
body { font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif; background:var(--bg); color:var(--midnight); min-height:100vh; }

.sidebar {
    position:fixed; top:0; left:0; width:var(--nav-w); height:100vh;
    background:linear-gradient(180deg, #0A0F1E 0%, #071E45 100%);
    display:flex; flex-direction:column; overflow-y:auto; z-index:100;
    border-right:1px solid rgba(255,255,255,0.06);
}
.sidebar-logo { padding:20px 16px; border-bottom:1px solid rgba(255,255,255,0.06); }
.logo-badge { display:inline-flex; align-items:center; gap:8px; }
.logo-icon { width:36px; height:36px; background:transparent; border-radius:9px; display:flex; align-items:center; justify-content:center; }
.logo-icon svg { width:20px; height:20px; fill:white; }
.logo-text { font-size:13px; font-weight:800; color:white; }
.logo-text small { display:block; font-size:10px; font-weight:400; color:#6B7280; }
.super-badge { margin-top:10px; display:inline-flex; align-items:center; gap:6px; background:rgba(220,38,38,0.15); border:1px solid rgba(220,38,38,0.3); color:#FCA5A5; font-size:10px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:0.08em; }

.nav-section { padding:14px 10px 4px; }
.nav-label { font-size:10px; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:0.09em; padding:0 8px; margin-bottom:4px; }
.nav-item { display:flex; align-items:center; gap:9px; padding:8px 10px; border-radius:8px; font-size:12.5px; font-weight:500; color:#9CA3AF; text-decoration:none; transition:all 150ms; margin-bottom:1px; }
.nav-item:hover { background:rgba(255,255,255,0.06); color:#E5E7EB; }
.nav-item.active { background:rgba(215,154,33,0.2); color:#F2C35B; border-left:3px solid #D79A21; padding-left:7px; }
.nav-item svg { width:15px; height:15px; flex-shrink:0; opacity:0.7; }
.nav-item.active svg, .nav-item:hover svg { opacity:1; }

.sidebar-footer { margin-top:auto; padding:12px 10px; border-top:1px solid rgba(255,255,255,0.06); }
.user-block { display:flex; align-items:center; gap:9px; padding:9px 10px; background:rgba(255,255,255,0.03); border-radius:9px; }
.user-av { width:32px; height:32px; background:var(--indigo); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:white; flex-shrink:0; }
.user-name { font-size:12px; font-weight:600; color:white; }
.user-role { font-size:10px; color:#6B7280; }
.logout-btn { background:none; border:none; color:#6B7280; cursor:pointer; padding:5px; border-radius:5px; transition:color 150ms; }
.logout-btn:hover { color:#EF4444; }

.btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; font-size:12px; font-weight:600; font-family:inherit; border-radius:8px; border:none; cursor:pointer; text-decoration:none; transition:all 150ms; }
.btn-primary { background:var(--indigo); color:white; }
.btn-primary:hover { background:var(--indigo-dk); }
.btn-sm { padding:4px 10px; font-size:11px; }
.btn-ghost { background:#F1F5F9; color:var(--slate); border:1px solid var(--border); }

.badge { display:inline-flex; font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; }
.badge-green { background:#ECFDF5; color:#059669; }
.badge-red   { background:#FEF2F2; color:#DC2626; }
.badge-amber { background:#FFFBEB; color:#D97706; }
.badge-blue  { background:#EFF6FF; color:#2563EB; }
.badge-gold  { background:var(--indigo-bg); color:var(--indigo); }

.main { margin-left:var(--nav-w); min-height:100vh; display:flex; flex-direction:column; }
.topbar {
    height:56px; background:white; border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; padding:0 24px;
    position:sticky; top:0; z-index:50; box-shadow:0 1px 3px rgba(0,0,0,0.06);
}
.topbar-title { font-size:15px; font-weight:700; color:var(--midnight); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
.topbar-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.super-pill { font-size:11px; font-weight:700; background:#FEF2F2; color:#DC2626; padding:5px 12px; border-radius:20px; border:1px solid #FECACA; }

/* Mobile hamburger */
.super-hamburger {
    display:none; align-items:center; justify-content:center;
    width:36px; height:36px; background:#F1F5F9; border:1px solid var(--border);
    border-radius:8px; cursor:pointer; flex-shrink:0;
}
.super-hamburger svg { width:18px; height:18px; }
.super-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
    z-index:149; backdrop-filter:blur(2px);
}
.super-overlay.open { display:block; }

.page-content { padding:24px; flex:1; }

.alert-success { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:8px; padding:12px 16px; font-size:13px; color:#059669; margin-bottom:16px; }
.alert-error   { background:#FEF2F2; border:1px solid #FECACA; border-radius:8px; padding:12px 16px; font-size:13px; color:#DC2626; margin-bottom:16px; }

@media(max-width:768px) {
    :root { --nav-w: 0px; }
    .sidebar { transform:translateX(-100%); transition:transform 250ms; width:260px; z-index:200; }
    .sidebar.open { transform:translateX(0); }
    .main { margin-left:0; }
    .page-content { padding:14px; overflow-x:hidden; }
    .super-hamburger { display:flex; }
    .topbar { padding:0 14px; gap:10px; }
    .topbar-title { font-size:13.5px; }
    .super-pill { white-space:nowrap; }
    .two, .fr { grid-template-columns:1fr; }
    /* Stack inline flex space-between page headers on phones */
    .page-content > div[style*="justify-content:space-between"],
    .page-content > div[style*="justify-content: space-between"] {
        flex-direction:column !important;
        align-items:flex-start !important;
    }
}
@media(max-width:480px) {
    .page-content { padding:10px; }
    .super-pill { padding:4px 8px; font-size:10px; }
}
/* Shared responsive utilities (mirrors app.blade.php global styles) */
.tbl { overflow-x:auto; -webkit-overflow-scrolling:touch; width:100%; }
.tbl table { min-width:480px; }
table { width:100%; border-collapse:collapse; }
.two  { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.fr   { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
@media(max-width:1024px){.stats-row{grid-template-columns:repeat(2,1fr);}.two-col{grid-template-columns:1fr;}}
@media(max-width:640px){.two,.fr{grid-template-columns:1fr;}.stats-row{grid-template-columns:1fr 1fr;}}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#065F46;margin-bottom:16px}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:16px}
    </style>
    @stack('styles')
</head>
<body>

<div class="super-overlay" id="superOverlay" onclick="closeSuperSidebar()"></div>

<aside class="sidebar" id="superSidebar">
    <div class="sidebar-logo">
        <div class="logo-badge">
            <div class="logo-icon" style="background:transparent"><img src="/brand/educore-icon.svg" alt="EduCore" style="width:36px;height:36px;border-radius:8px"></div>
            <div class="logo-text">
                EduCore
                <small>Platform Control</small>
            </div>
        </div>
        <div class="super-badge">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            Super Administrator
        </div>
    </div>

    <div class="nav-section">
        <div class="nav-label">Platform</div>
        <a href="{{ route('super.dashboard') }}" class="nav-item {{ request()->routeIs('super.dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            Platform Dashboard
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Schools</div>
        <a href="{{ route('super.tenants') }}" class="nav-item {{ request()->routeIs('super.tenants') && !request()->routeIs('super.tenants.create') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
            All Schools
        </a>
        <a href="{{ route('super.tenants.create') }}" class="nav-item {{ request()->routeIs('super.tenants.create') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Provision School
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Billing</div>
        <a href="{{ route('super.plans') }}" class="nav-item {{ request()->routeIs('super.plans*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            Pricing
        </a>
        <a href="{{ route('super.payments') }}" class="nav-item {{ request()->routeIs('super.payments') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            Revenue & Payments
        </a>
        <a href="{{ route('super.billing') }}" class="nav-item {{ request()->routeIs('super.billing*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            Invoicing
        </a>
        <a href="{{ route('super.groups.index') }}" class="nav-item {{ request()->routeIs('super.groups*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
            School Groups
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Platform</div>
        <a href="{{ route('super.analytics') }}" class="nav-item {{ request()->routeIs('super.analytics') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
            Platform Analytics
        </a>
        <a href="{{ route('super.payment-gateways') }}" class="nav-item {{ request()->routeIs('super.payment-gateways*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
            Payment Gateways
        </a>
        <a href="{{ route('super.settings') }}" class="nav-item {{ request()->routeIs('super.settings') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
            Platform Settings
        </a>
        <a href="{{ route('super.agents.index') }}" class="nav-item {{ request()->routeIs('super.agents*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            Agents
        </a>
    </div>

    <div class="sidebar-footer">
        {{-- Sign out button — full width, above user block --}}
        <form method="POST" action="{{ route('logout') }}" style="margin:0 0 8px">
            @csrf
            <button type="submit" style="width:100%;padding:10px 14px;background:#7F1D1D;border:1px solid #991B1B;color:#FCA5A5;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 150ms;letter-spacing:.04em;text-transform:uppercase"
                    onmouseover="this.style.background='#DC2626';this.style.color='white'"
                    onmouseout="this.style.background='#7F1D1D';this.style.color='#FCA5A5'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                Sign Out
            </button>
        </form>
        {{-- User identity block --}}
        <div class="user-block" style="cursor:default">
            <div class="user-av">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div style="flex:1;min-width:0">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">Super Administrator</div>
            </div>
        </div>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <button class="super-hamburger" onclick="toggleSuperSidebar()" aria-label="Menu">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <div class="topbar-title">@yield('page-title', 'Platform Dashboard')</div>
        <div class="topbar-right">
            @if(session('impersonating_tenant_id'))
                <form method="POST" action="{{ route('super.stop-impersonating') }}" style="margin:0">
                    @csrf
                    <button type="submit" style="font-size:11px;font-weight:600;background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;padding:5px 12px;border-radius:7px;cursor:pointer;font-family:inherit">← Exit School View</button>
                </form>
            @endif
            <span class="super-pill">⚡ Super Admin</span>
        </div>
    </header>
    <div class="page-content">
        @yield('content')
    </div>
</div>

@stack('scripts')
<script>
function toggleSuperSidebar() {
    var s = document.getElementById('superSidebar');
    var o = document.getElementById('superOverlay');
    s.classList.toggle('open');
    o.classList.toggle('open');
    document.body.style.overflow = s.classList.contains('open') ? 'hidden' : '';
}
function closeSuperSidebar() {
    document.getElementById('superSidebar').classList.remove('open');
    document.getElementById('superOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
</script>
</body>
</html>
