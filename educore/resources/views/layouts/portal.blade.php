<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Portal') — {{ optional(optional(auth()->user())->tenant)->name ?? 'School Portal' }}</title>
    <style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
:root {
    --brand-navy: #071E45;        /* canonical — overridden per-tenant by ThemeHelper below */
    --brand-gold: #D79A21;        /* canonical — overridden per-tenant by ThemeHelper below */
    --brand:    var(--brand-gold); /* legacy alias kept for portal child views */
    --brand-dk: #B8810D;
    --brand-bg: #FEF9EC;
    --emerald:  #059669;
    --amber:    #D97706;
    --crimson:  #DC2626;
    --midnight: var(--brand-navy);
    --slate:    #475569;
    --muted:    #94A3B8;
    --border:   #E2E8F0;
    --bg:       #F4F6FA;
    --white:    #FFFFFF;
    --nav-h:    58px;
    --sidebar-w:220px;
}
body { font-family:'Plus Jakarta Sans',system-ui,sans-serif; background:var(--bg); color:var(--midnight); min-height:100vh; }

/* Top nav */
.p-nav {
    position:fixed; top:0; left:0; right:0; height:var(--nav-h);
    background:var(--midnight); display:flex; align-items:center;
    padding:0 20px; gap:14px; z-index:100;
    box-shadow:0 1px 3px rgba(0,0,0,0.2);
}
.p-logo { display:flex; align-items:center; gap:9px; text-decoration:none; }
.p-logo-icon {
    width:34px; height:34px; background:var(--midnight); border-radius:8px;
    display:flex; align-items:center; justify-content:center;
}
.p-logo-icon svg { width:18px; height:18px; fill:white; }
.p-school { font-size:13px; font-weight:800; color:white; }
.p-school small { display:block; font-size:10px; font-weight:400; color:#94A3B8; }
.p-spacer { flex:1; }
.p-user { display:flex; align-items:center; gap:8px; }
.p-av {
    width:32px; height:32px; border-radius:50%; background:var(--brand-gold);
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; color:white; flex-shrink:0;
}
.p-name { font-size:13px; font-weight:600; color:#CBD5E1; }
.p-role { font-size:10px; color:#64748B; text-transform:capitalize; }
.p-logout {
    padding:6px 12px; background:rgba(239,68,68,0.15); color:#FCA5A5;
    border:none; border-radius:7px; font-size:12px; font-weight:600;
    cursor:pointer; font-family:inherit; text-decoration:none;
}

/* Sidebar */
.p-sidebar {
    position:fixed; top:var(--nav-h); left:0; bottom:0;
    width:var(--sidebar-w); background:#1E293B; overflow-y:auto;
    z-index:90; display:flex; flex-direction:column;
    scrollbar-width:thin; scrollbar-color:rgba(255,255,255,0.1) transparent;
}
.p-sidebar::-webkit-scrollbar { width:4px; }
.p-sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:4px; }
.p-nav-section { padding:16px 10px 6px; }
.p-nav-label { font-size:9px; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:.1em; padding:0 8px; margin-bottom:4px; }
.p-nav-item {
    display:flex; align-items:center; gap:9px; padding:9px 10px;
    border-radius:8px; font-size:13px; font-weight:500; color:#94A3B8;
    text-decoration:none; transition:all 150ms; margin-bottom:1px; white-space:nowrap;
}
.p-nav-item svg { width:16px; height:16px; flex-shrink:0; }
.p-nav-item:hover { background:rgba(255,255,255,0.07); color:#CBD5E1; }
.p-nav-item.active { background:rgba(215,154,33,0.25); color:#F2C35B; }
.p-nav-item.active svg { color:#F2C35B; }

/* Main content */
.p-main { margin-left:var(--sidebar-w); margin-top:var(--nav-h); min-height:calc(100vh - var(--nav-h)); }
.p-content { padding:24px; max-width:1200px; }

/* Cards */
.card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:16px; }
.ch   { padding:12px 18px; border-bottom:1px solid var(--border); background:#F8FAFC; font-size:13px; font-weight:700; color:var(--midnight); display:flex; align-items:center; justify-content:space-between; }
.cb   { padding:16px 18px; }
.kpi-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:12px; margin-bottom:20px; }
.kpi  { background:white; border:1px solid var(--border); border-radius:12px; padding:16px 18px; }
.kv   { font-size:22px; font-weight:800; letter-spacing:-0.02em; margin-bottom:4px; }
.kl   { font-size:11px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; }
.badge { display:inline-flex; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
.b-g  { background:#ECFDF5; color:#059669; }
.b-r  { background:#FEF2F2; color:#DC2626; }
.b-a  { background:#FFFBEB; color:#D97706; }
.b-b  { background:#EFF6FF; color:#2563EB; }
.b-s  { background:#F1F5F9; color:#64748B; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th    { padding:9px 14px; background:#F8FAFC; border-bottom:1px solid var(--border); font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); text-align:left; }
td    { padding:9px 14px; border-bottom:1px solid var(--border); }
tr:last-child td { border:none; }
tr:hover td { background:#F8FAFC; }
.empty { text-align:center; padding:50px; color:var(--muted); }
.empty-icon { font-size:40px; margin-bottom:12px; }
.btn { display:inline-flex; align-items:center; gap:5px; padding:8px 14px; font-size:13px; font-weight:600; font-family:inherit; border-radius:8px; border:none; cursor:pointer; text-decoration:none; transition:all 150ms; }
.btn-primary { background:var(--brand); color:white; }
.btn-primary:hover { background:var(--brand-dk); }
.btn-ghost { background:#F1F5F9; color:var(--slate); border:1px solid var(--border); }
.alert-s { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:8px; padding:10px 14px; font-size:13px; color:#059669; margin-bottom:14px; }

/* Attendance bar */
.att-bar { height:8px; background:#F1F5F9; border-radius:4px; overflow:hidden; margin-top:6px; }
.att-fill { height:100%; border-radius:4px; }

/* Mobile */
/* ── Mobile hamburger ───────────────────────────────────────────── */
.p-hamburger {
    display:none; align-items:center; justify-content:center;
    width:38px; height:38px; background:rgba(255,255,255,.08);
    border:none; border-radius:8px; cursor:pointer; margin-right:6px;
}
.p-hamburger svg { width:20px; height:20px; fill:white; }
.p-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
    z-index:149; backdrop-filter:blur(2px);
}
.p-overlay.open { display:block; }

@media (max-width:768px) {
    :root { --sidebar-w:0px; }
    .p-sidebar { transform:translateX(-100%); width:240px; transition:transform 250ms; z-index:150; }
    .p-sidebar.open { transform:translateX(0); }
    .p-main { margin-left:0; }
    .p-content { padding:14px; }
    .kpi-row { grid-template-columns:1fr 1fr; }
    .p-hamburger { display:flex; }
    .p-name, .p-role { display:none; }
}

/* Child selector tabs */
.child-tabs { display:flex; gap:6px; margin-bottom:18px; flex-wrap:wrap; }
.child-tab  { padding:7px 16px; font-size:13px; font-weight:600; border-radius:8px; border:1.5px solid var(--border); background:white; color:var(--slate); text-decoration:none; }
.child-tab.active, .child-tab:hover { background:var(--midnight); border-color:var(--midnight); color:white; }

/* ── Portal responsive utilities ──────────────────────────────────── */
.tbl { overflow-x:auto; -webkit-overflow-scrolling:touch; width:100%; }
.tbl table { min-width:460px; }
table { width:100%; border-collapse:collapse; }
.two  { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.fr   { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.card { background:white; border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:16px; }
.ch   { padding:12px 16px; border-bottom:1px solid var(--border); font-size:13px; font-weight:700; color:var(--midnight); background:#F8FAFC; }
.cb   { padding:14px 16px; }
.fg   { margin-bottom:12px; }
.fl   { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); margin-bottom:3px; }
.fc   { width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#F8FAFC; }
.fc:focus { border-color:var(--midnight); background:white; }
.badge { display:inline-flex; align-items:center; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }
.alert-s { background:#ECFDF5; border:1px solid #A7F3D0; border-radius:8px; padding:12px 16px; font-size:13px; color:#065F46; margin-bottom:14px; }
.alert-e { background:#FEF2F2; border:1px solid #FCA5A5; border-radius:8px; padding:12px 16px; font-size:13px; color:#991B1B; margin-bottom:14px; }
.page-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }

@media (max-width:640px) {
    .two, .fr { grid-template-columns:1fr; }
    .page-header { flex-direction:column; align-items:flex-start; }
    .page-header .btn, .page-header a.btn { width:100%; justify-content:center; min-height:42px; }
    .p-content { padding:10px; overflow-x:hidden; }
    .cb { padding:12px 14px; }
    .child-tabs { gap:8px; }
    .child-tab { flex:1 1 calc(50% - 8px); text-align:center; min-height:38px; display:inline-flex; align-items:center; justify-content:center; }
    /* Stack inline flex space-between page headers on phones */
    .p-content > div[style*="justify-content:space-between"],
    .p-content > div[style*="justify-content: space-between"] {
        flex-direction:column !important;
        align-items:flex-start !important;
    }
}
@media (max-width:380px) {
    .p-content { padding:8px; }
}
    </style>
    {!! \App\Helpers\ThemeHelper::css() !!}
    @stack('styles')
</head>
<body>

{{-- TOP NAV --}}
<div class="p-overlay" id="portalOverlay" onclick="closePortalSidebar()"></div>

<nav class="p-nav">
    <button class="p-hamburger" onclick="togglePortalSidebar()" aria-label="Menu">
        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>
    @php
        $portalHomeRoute = auth()->user()->isStudent() ? 'student.portal.dashboard'
            : (auth()->user()->isParent() ? 'parent.dashboard' : 'staff.portal.dashboard');
        $portalLabel = auth()->user()->isStudent() ? 'Student Portal'
            : (auth()->user()->isParent() ? 'Parent Portal' : 'Staff Portal');
    @endphp
    <a href="{{ route($portalHomeRoute) }}" class="p-logo">
        <div class="p-logo-icon">
            <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
        </div>
        <div class="p-school">
            {{ optional(auth()->user()->tenant)->name ?? 'School Portal' }}
            <small>{{ $portalLabel }}</small>
        </div>
    </a>
    <div class="p-spacer"></div>
    <div class="p-user">
        <div class="p-av">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
        <div>
            <div class="p-name">{{ auth()->user()->name }}</div>
            <div class="p-role">{{ auth()->user()->roleLabel() }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="p-logout">Sign Out</button>
        </form>
    </div>
</nav>

{{-- SIDEBAR --}}
<aside class="p-sidebar" id="portalSidebar">
    @if(auth()->user()->isStudent())
        @include('layouts.partials.student-sidebar')
    @elseif(auth()->user()->isParent())
        @include('layouts.partials.parent-sidebar')
    @else
        @include('layouts.partials.staff-sidebar')
    @endif
</aside>

{{-- MAIN --}}
<main class="p-main">
    <div class="p-content">
        @yield('content')
    </div>
</main>

@stack('scripts')
<script>
function togglePortalSidebar() {
    document.getElementById('portalSidebar').classList.toggle('open');
    document.getElementById('portalOverlay').classList.toggle('open');
    document.body.style.overflow = document.getElementById('portalSidebar').classList.contains('open') ? 'hidden' : '';
}
function closePortalSidebar() {
    document.getElementById('portalSidebar').classList.remove('open');
    document.getElementById('portalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
// Close sidebar when a nav link is tapped on mobile
document.querySelectorAll('.p-nav-item').forEach(function(el) {
    el.addEventListener('click', function() {
        if (window.innerWidth <= 768) closePortalSidebar();
    });
});
</script>
</body>
</html>
