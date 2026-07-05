<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EduCore</title>
    <link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
{!! \App\Helpers\ThemeHelper::css() !!}
    <link rel="stylesheet" href="/brand/educore-brand.css">
    <style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

:root {
    /* ── EduCore Brand ── */
    --brand-navy:  #071E45;   /* primary dark */
    --brand-gold:  #D79A21;   /* primary accent */
    --brand-gold-l:#F2C35B;   /* light gold */
    --brand-gray:  #7A7F87;   /* muted text */

    /* ── Semantic aliases (used throughout UI) ── */
    --indigo:      #D79A21;   /* primary CTA → gold */
    --indigo-dark: #B8810D;   /* hover state */
    --indigo-bg:   #FEF9EC;   /* light gold bg */
    --emerald:     #059669;   /* success / present */
    --amber:       #D79A21;   /* warnings → same gold */
    --crimson:     #DC2626;   /* errors */
    --midnight:    #071E45;   /* headings, nav bg → navy */
    --slate:       #475569;   /* body text */
    --slate-light: #7A7F87;   /* muted text → brand gray */
    --border:      #E4E8EF;
    --bg:          #F4F6FA;   /* slightly warm page bg */
    --white:       #FFFFFF;
    --nav-w:       210px;
    --nav-w-collapsed: 60px;
    --header-h:    58px;
    --radius:      10px;
    --shadow:      0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
    --shadow-md:   0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
}

body { font-family:'Plus Jakarta Sans',system-ui,sans-serif; background:var(--bg); color:var(--midnight); min-height:100vh; }

/* ═══════════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════════ */
.sidebar {
    position: fixed;
    top: 0; left: 0;
    width: var(--nav-w);
    height: 100vh;
    background: var(--brand-navy);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 100;
    transition: width 280ms cubic-bezier(0.4,0,0.2,1);
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.15) transparent;
}
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }

/* Collapsed state */
.sidebar.collapsed {
    width: var(--nav-w-collapsed);
    overflow: hidden;
}
.sidebar.collapsed .logo-text,
.sidebar.collapsed .nav-section-label,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .user-info,
.sidebar.collapsed .logout-btn span,
.sidebar.collapsed .sidebar-footer .user-block { display: none; }
.sidebar.collapsed .nav-item {
    justify-content: center;
    padding: 10px;
    gap: 0;
}
.sidebar.collapsed .nav-item svg { width: 20px; height: 20px; opacity: 0.85; }
.sidebar.collapsed .nav-section { padding: 8px 6px 2px; }
.sidebar.collapsed .sidebar-logo { justify-content: center; padding: 14px 8px; }
.sidebar.collapsed .logo-icon { margin: 0; }
.sidebar.collapsed .user-av { margin: auto; }

/* Logo */
.sidebar-logo {
    padding:18px 16px 16px;
    display:flex; align-items:center; gap:10px;
    border-bottom:1px solid rgba(255,255,255,0.06);
    flex-shrink:0;
    position: relative;
}
.logo-icon {
    width:36px; height:36px; border-radius:9px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
    overflow:hidden; background:transparent;
}
.logo-icon img { width:36px; height:36px; object-fit:contain; border-radius:8px; }
.logo-icon svg { width:20px; height:20px; fill:white; }
.logo-text { font-size:13px; font-weight:800; color:white; letter-spacing:-0.02em; line-height:1.25; flex:1; min-width:0; }
.logo-text small { display:block; font-size:10px; font-weight:400; color:#475569; letter-spacing:0.03em; margin-top:1px; }

/* Collapse toggle button */
.collapse-btn {
    position: absolute; right: -12px; top: 50%; transform: translateY(-50%);
    width: 24px; height: 24px; border-radius: 50%;
    background: #334155; border: 2px solid #0F172A;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 101; transition: background 150ms;
    color: #94A3B8;
}
.collapse-btn:hover { background: var(--brand-gold); color: var(--brand-navy); }
.collapse-btn svg { width: 12px; height: 12px; transition: transform 280ms; }
.sidebar.collapsed .collapse-btn { right: -12px; }
.sidebar.collapsed .collapse-btn svg { transform: rotate(180deg); }

/* Nav sections */
.nav-section { padding:14px 10px 4px; }
.nav-section-label {
    font-size:10px; font-weight:700; color:#334155; text-transform:uppercase;
    letter-spacing:0.09em; padding:0 8px; margin-bottom:4px; white-space:nowrap;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #94A3B8;
    text-decoration: none;
    transition: background 150ms, color 150ms;
    margin-bottom: 1px;
    position: relative;
    white-space: nowrap;
}
.nav-item:hover:not(.active) {
    background: rgba(255,255,255,0.07);
    color: #CBD5E1;
}
.nav-item svg {
    width: 16px; height: 16px; flex-shrink: 0;
    opacity: 0.75; transition: opacity 150ms;
}
.nav-item:hover svg, .nav-item.active svg { opacity: 1; }
.nav-item:hover { background:rgba(255,255,255,0.06); color:#E2E8F0; }
.nav-item.active { background: rgba(215,154,33,0.2); color: #F2C35B; }
.nav-item.active svg { color: var(--brand-gold-l); }
.nav-item.active::before {
    content: '';
    position: absolute; left: 0; top: 4px; bottom: 4px;
    width: 3px; background: #3B82F6; border-radius: 0 3px 3px 0;
}
.nav-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; }

/* Tooltip for collapsed state */
.sidebar.collapsed .nav-item:hover::after {
    content: attr(data-tip);
    position: absolute; left: calc(100% + 10px); top: 50%;
    transform: translateY(-50%);
    background: #1E293B; color: white; font-size: 12px; font-weight: 600;
    padding: 5px 10px; border-radius: 6px; white-space: nowrap; pointer-events: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 200;
}

/* User footer */
.sidebar-footer {
    margin-top:auto; padding:12px 10px; border-top:1px solid rgba(255,255,255,0.06);
    flex-shrink:0;
}
.user-block {
    display:flex; align-items:center; gap:9px; padding:9px 10px;
    background:rgba(255,255,255,0.04); border-radius:9px;
}
.user-av {
    width:32px; height:32px; background:var(--brand-gold); border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; color:white; flex-shrink:0;
}
.user-info { flex:1; min-width:0; }
.user-name { font-size:12px; font-weight:600; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-role { font-size:10px; color:#475569; text-transform:capitalize; margin-top:1px; }
.logout-btn {
    width:100%; background:#FEF2F2; border:1px solid #FECACA; color:#DC2626;
    cursor:pointer; padding:9px 14px; border-radius:8px;
    display:flex; align-items:center; justify-content:center; gap:7px;
    font-size:12px; font-weight:700; font-family:inherit;
    transition:all 150ms; text-transform:uppercase; letter-spacing:.05em;
}
.logout-btn:hover { background:#DC2626; color:white; border-color:#DC2626; }

/* ═══════════════════════════════════════════════
   MAIN AREA
═══════════════════════════════════════════════ */
.main {
    margin-left:var(--nav-w); min-height:100vh; display:flex; flex-direction:column;
    transition: margin-left 280ms cubic-bezier(0.4,0,0.2,1);
}
.main.sidebar-collapsed { margin-left: var(--nav-w-collapsed); }

/* Topbar */
.topbar {
    height:var(--header-h); background:var(--white);
    border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    padding:0 24px; position:sticky; top:0; z-index:50;
    box-shadow:var(--shadow);
}
.topbar-left { display:flex; align-items:center; gap:12px; min-width:0; flex:1; }
.mobile-toggle {
    display:none; background:none; border:none; cursor:pointer;
    padding:6px; border-radius:7px; color:var(--slate); transition:background 150ms;
}
.mobile-toggle:hover { background:#F1F5F9; }
.mobile-toggle svg { width:20px; height:20px; display:block; }
.topbar-title { font-size:16px; font-weight:700; color:var(--midnight); letter-spacing:-0.02em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.topbar-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.tenant-pill {
    font-size:11px; font-weight:600; background:var(--indigo-bg);
    color:var(--brand-navy); padding:5px 12px; border-radius:20px;
    border:1px solid #BFDBFE; white-space:nowrap;
}
.super-pill {
    font-size:11px; font-weight:700; background:#FEF2F2;
    color:#DC2626; padding:5px 12px; border-radius:20px;
    border:1px solid #FECACA;
}
.exit-btn {
    font-size:11px; font-weight:600; background:#FFFBEB; color:var(--amber);
    border:1px solid #FDE68A; padding:5px 12px; border-radius:7px;
    cursor:pointer; font-family:inherit; transition:background 150ms;
}
.exit-btn:hover { background:#FEF3C7; }

/* Page content */
.page-content { padding:24px; flex:1; max-width:1600px; width:100%; }
.tenant-access-banner {
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    margin-bottom:18px;
    border:1px solid #FDE68A;
    border-radius:10px;
    background:#FFFBEB;
    color:#92400E;
    font-size:13px;
    line-height:1.45;
}
.tenant-access-banner strong { color:#78350F; }
.tenant-access-banner a {
    margin-left:auto;
    color:#92400E;
    font-weight:800;
    text-decoration:none;
    white-space:nowrap;
}

/* Overlay */
.sidebar-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.45); z-index:99;
    backdrop-filter:blur(2px);
}

/* ═══════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════ */
@media (max-width: 1280px) { :root { --nav-w: 196px; } }
@media (max-width: 1024px) { :root { --nav-w: 186px; } }

@media (max-width: 768px) {
    :root { --nav-w: 0px; }
    .sidebar {
        width: 260px !important;
        transform: translateX(-100%);
        transition: transform 250ms cubic-bezier(0.4,0,0.2,1);
        box-shadow: 4px 0 24px rgba(0,0,0,0.35);
        z-index: 200;
    }
    .sidebar.open { transform: translateX(0); }
    .sidebar-overlay.open { display: block; }
    .main { margin-left: 0 !important; }
    .mobile-toggle { display: flex; }
    .topbar { padding: 0 16px; }
    .page-content { padding: 14px; }
    .tenant-access-banner { align-items:flex-start; flex-direction:column; }
    .tenant-access-banner a { margin-left:0; }
    .topbar-title { font-size: 14px; }
    .collapse-btn { display: none; }
    .tenant-pill { max-width: 140px; overflow: hidden; text-overflow: ellipsis; }

    /* Ad-hoc page headers built with inline flex + space-between stack
       vertically so titles and action buttons never collide on phones */
    .page-content > div[style*="justify-content:space-between"],
    .page-content > div[style*="justify-content: space-between"] {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
}

@media (max-width: 480px) {
    .page-content { padding: 10px; }
    .tenant-pill { max-width: 100px; }
    .super-pill { padding: 4px 8px; }
    .tenant-pill, .super-pill { display: none; }
}

/* ═══════════════════════════════════════════════
   UTILITY CLASSES
═══════════════════════════════════════════════ */
.badge { display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px; }
.badge-success { background:#ECFDF5;color:var(--emerald); }
.badge-warning { background:#FFFBEB;color:var(--amber); }
.badge-error   { background:#FEF2F2;color:var(--crimson); }
.badge-info    { background:var(--indigo-bg);color:var(--indigo); }
.badge-slate   { background:#F1F5F9;color:var(--slate); }

/* ═══════════════════════════════════════════════
   BUTTONS
═══════════════════════════════════════════════ */
.btn { display:inline-flex;align-items:center;gap:5px;padding:8px 14px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms; }
.btn-primary { background:var(--indigo);color:white; }
.btn-primary:hover { background:var(--indigo-dark); }
.btn-secondary { background:white;color:var(--slate);border:1px solid var(--border); }
.btn-secondary:hover { background:#F8FAFC; }
.btn-danger { background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA; }
.btn-danger:hover { background:var(--crimson);color:white;border-color:var(--crimson); }
.btn-sm { padding:5px 10px;font-size:12px; }
.btn-xs { padding:3px 8px;font-size:11px; }
    </style>

    {{-- ═══════════════════════════════════════════════
         GLOBAL RESPONSIVE STYLES
         Applied system-wide — no per-view duplication needed.
    ═══════════════════════════════════════════════ --}}
    <style>
    /* ── Scrollable table wrapper ─────────────────────────────────────
       Every <table> should live inside .tbl or overflow-x:auto.
       We set min-width here so narrow tables still get scroll on mobile.
    ──────────────────────────────────────────────────────────────────── */
    .tbl { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; }
    .tbl table, table { width: 100%; border-collapse: collapse; min-width: 480px; }

    /* ── Responsive 2-column grids ────────────────────────────────────
       .two  — used heavily in forms (name | email, start | end date, etc.)
       .fr   — inline form row
       .fr3  — 3-column form row
    ──────────────────────────────────────────────────────────────────── */
    .two  { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .fr   { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .fr3  { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }

    @media (max-width: 640px) {
        .two, .fr { grid-template-columns: 1fr; gap: 10px; }
        .fr3 { grid-template-columns: 1fr 1fr; gap: 8px; }
    }

    /* ── Stat/KPI card rows ────────────────────────────────────────────
       .stats-row — 4-col stat cards (dashboard, attendance, etc.)
    ──────────────────────────────────────────────────────────────────── */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
    @media (max-width: 1024px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px)  { .stats-row { grid-template-columns: 1fr 1fr; gap: 10px; } }

    /* ── Page-level two-column layout ──────────────────────────────────
       .two-col — sidebar + main (e.g. student profile, risk flags)
    ──────────────────────────────────────────────────────────────────── */
    .two-col { display: grid; grid-template-columns: 1fr 360px; gap: 20px; align-items: start; }
    @media (max-width: 1024px) { .two-col { grid-template-columns: 1fr; } }

    /* ── Page header (title + action buttons) ──────────────────────────
       Wraps on narrow screens so buttons don't overflow
    ──────────────────────────────────────────────────────────────────── */
    .page-header {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; flex-wrap: wrap; margin-bottom: 18px;
    }
    .page-header h1, .page-header .page-title {
        font-size: 22px; font-weight: 800; color: var(--midnight);
    }
    .page-header-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

    @media (max-width: 640px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        .page-header h1, .page-header .page-title { font-size: 18px; line-height: 1.25; }
        .page-header-actions { width: 100%; }
        .page-header-actions .btn,
        .page-header-actions a.btn,
        .page-header-actions button.btn { flex: 1 1 auto; justify-content: center; min-height: 40px; }

        /* Stat/profile strips: two per row instead of squeezing five across */
        .profile-row .pstat, .stats-row .stat-pill { flex: 1 1 calc(50% - 12px); min-width: 0; }

        /* Any table wrapper scrolls horizontally instead of breaking the page */
        .trx, .table-wrap, .subject-wrap, .pub-grid { max-width: 100%; }
    }

    /* Never allow content to force horizontal page scroll on phones */
    @media (max-width: 768px) {
        .page-content { overflow-x: hidden; }
        .page-content table { max-width: 100%; }
        .page-content .card, .page-content .tcard { border-radius: 10px; }
    }

    /* ── Cards ─────────────────────────────────────────────────────────
       Global .card style so individual views don't redefine it
    ──────────────────────────────────────────────────────────────────── */
    .card {
        background: white; border: 1px solid var(--border);
        border-radius: 12px; overflow: hidden; margin-bottom: 16px;
    }
    .ch { /* card head */
        padding: 13px 18px; border-bottom: 1px solid var(--border);
        font-size: 14px; font-weight: 700; color: var(--midnight);
        background: #F8FAFC;
    }
    .cb { padding: 16px 18px; } /* card body */

    /* ── Filter / search bar ───────────────────────────────────────────
       .filter-bar  — flex row of filters
       .filter-card — card wrapping filter controls
    ──────────────────────────────────────────────────────────────────── */
    .filter-bar {
        display: flex; gap: 10px; align-items: center;
        flex-wrap: wrap; margin-bottom: 16px;
    }
    .filter-card {
        background: white; border: 1px solid var(--border);
        border-radius: 10px; padding: 14px 16px; margin-bottom: 16px;
        display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;
    }
    .filter-control {
        padding: 8px 10px; border: 1px solid var(--border);
        border-radius: 8px; font-size: 13px; font-family: inherit;
        background: #F8FAFC; min-width: 0; flex: 1;
    }
    @media (max-width: 640px) {
        .filter-card { flex-direction: column; }
        .filter-control { width: 100%; }
    }

    /* ── Form utilities ────────────────────────────────────────────────
       .fg  — form group   .fl  — form label   .fc  — form control
    ──────────────────────────────────────────────────────────────────── */
    .fg { margin-bottom: 14px; }
    .fl {
        display: block; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        color: var(--slate-light); margin-bottom: 4px;
    }
    .fc {
        width: 100%; padding: 9px 12px; border: 1px solid var(--border);
        border-radius: 8px; font-size: 13px; font-family: inherit;
        outline: none; background: #F8FAFC; transition: border 150ms;
    }
    .fc:focus { border-color: var(--indigo); background: white; }
    select.fc { appearance: auto; }
    .hint { font-size: 11px; color: var(--slate-light); margin-top: 3px; line-height: 1.5; }

    /* ── Alert banners ─────────────────────────────────────────────────  */
    .alert-s {
        background: #ECFDF5; border: 1px solid #A7F3D0;
        border-radius: 8px; padding: 12px 16px; font-size: 13px;
        color: #065F46; margin-bottom: 16px;
    }
    .alert-e {
        background: #FEF2F2; border: 1px solid #FCA5A5;
        border-radius: 8px; padding: 12px 16px; font-size: 13px;
        color: #991B1B; margin-bottom: 16px;
    }
    .alert-w {
        background: #FFFBEB; border: 1px solid #FDE68A;
        border-radius: 8px; padding: 12px 16px; font-size: 13px;
        color: #92400E; margin-bottom: 16px;
    }

    /* ── Locked / read-only field ──────────────────────────────────────  */
    .locked-field {
        display: flex; align-items: center; gap: 8px;
        padding: 9px 12px; background: #F8FAFC;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px; color: var(--midnight); min-height: 38px;
    }
    .locked-field svg { width: 14px; height: 14px; opacity: .45; flex-shrink: 0; }

    /* ── Page tabs ─────────────────────────────────────────────────────  */
    .page-tabs {
        display: flex; gap: 4px; flex-wrap: wrap;
        background: white; border: 1px solid var(--border);
        border-radius: 10px; padding: 4px; margin-bottom: 20px;
        width: fit-content; max-width: 100%;
    }
    .page-tab {
        padding: 7px 14px; border-radius: 7px; font-size: 13px;
        font-weight: 500; color: var(--slate); text-decoration: none;
        transition: all 150ms; white-space: nowrap;
    }
    .page-tab.active { background: var(--indigo); color: white; }
    .page-tab:hover:not(.active) { background: #F1F5F9; }

    @media (max-width: 640px) {
        .page-tabs { width: 100%; overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
    }

    /* ── Responsive auto-grid (used in dashboards/plans) ───────────────  */
    .auto-grid    { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
    .auto-grid-sm { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }

    /* ── Mobile-only utilities ──────────────────────────────────────────  */
    @media (max-width: 640px) {
        /* Reduce padding on all cards */
        .cb { padding: 12px 14px; }
        .ch { padding: 11px 14px; font-size: 13px; }

        /* Stack inline button groups */
        [style*="display:flex"][style*="gap"] { flex-wrap: wrap; }

        /* Full-width buttons on mobile */
        .btn-block-mobile { width: 100% !important; justify-content: center; }

        /* Hide decorative elements that eat space */
        .hide-mobile { display: none !important; }

        /* Shrink headings */
        h1 { font-size: 20px !important; }
        h2 { font-size: 17px !important; }
    }

    @media (max-width: 380px) {
        .fr3 { grid-template-columns: 1fr; }
        .page-content { padding: 8px !important; }
    }

    @media print {
        .sidebar, .sidebar-overlay, .topbar, .collapse-btn { display: none !important; }
        .page-content { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        body { background: white !important; }
    }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    {{-- Logo & Collapse button --}}
    <div class="sidebar-logo">
        @php $tenant = auth()->user()?->tenant; @endphp
        {{-- EduCore icon --}}
        <div class="logo-icon" style="background:transparent;padding:0">
            <img src="/brand/educore-icon.svg" alt="EduCore" style="width:36px;height:36px;border-radius:8px">
        </div>
        <div class="logo-text">
            <span style="color:white;font-weight:800">{{ optional($tenant)->name ?? 'Edu<span style=&quot;color:var(--brand-gold)&quot;>Core</span>' }}</span>
            <small style="color:rgba(255,255,255,.55);font-size:9px;letter-spacing:.15em;text-transform:uppercase;font-weight:600">School ERP</small>
        </div>
        <button class="collapse-btn" id="collapseBtn" title="Collapse sidebar" onclick="toggleCollapse()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
        </button>
    </div>

    {{-- CORE — always visible --}}
    <div class="nav-section">
        <div class="nav-section-label">Core</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tip="Dashboard">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            <span class="nav-label">Dashboard</span>
        </a>
    </div>

    {{-- ACADEMICS --}}
    @php $u = auth()->user(); @endphp
    @if($u->canAccessModule('students') || $u->canAccessModule('staff') || $u->canAccessModule('classes') || $u->canAccessModule('academic-cycle') || $u->canAccessModule('subjects') || $u->canAccessModule('curriculum') || $u->canAccessModule('attendance') || $u->canAccessModule('timetable') || $u->canAccessModule('skills'))
    <div class="nav-section">
        <div class="nav-section-label">Academics</div>
        @if($u->canAccessModule('students'))
        <a href="{{ route('students.index') }}" class="nav-item {{ request()->routeIs('students.*') && !request()->routeIs('students.transfers.*') && !request()->routeIs('students.class-transfers.*') && !request()->routeIs('students.archive.*') && !request()->routeIs('students.status.*') && !request()->routeIs('students.reactivate*') && !request()->routeIs('students.readmit*') && !request()->routeIs('students.graduation-correction*') ? 'active' : '' }}" data-tip="Students">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            <span class="nav-label">Students</span>
        </a>
        @endif
        @can('student.transfer.view')
        <a href="{{ route('students.class-transfers.index') }}" class="nav-item {{ request()->routeIs('students.class-transfers.*') ? 'active' : '' }}" data-tip="Student Transfers">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 7h11l-3-3 1.41-1.41L21.83 8l-5.42 5.41L15 12l3-3H7V7zm10 10H6l3 3-1.41 1.41L2.17 16l5.42-5.41L9 12l-3 3h11v2z"/></svg>
            <span class="nav-label">Student Transfers</span>
        </a>
        @endcan
        @if($u->canAccessModule('staff'))
        <a href="{{ route('staff.index') }}" class="nav-item {{ request()->routeIs('staff.*') && !request()->routeIs('staff.archive.*') && !request()->routeIs('staff.reinstate*') ? 'active' : '' }}" data-tip="Staff">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-label">Staff</span>
        </a>
        @endif
        @if($u->canAccessModule('classes'))
        <a href="{{ route('classes.levels') }}" class="nav-item {{ request()->routeIs('classes.*') && !request()->routeIs('classes.promotion*') && !request()->routeIs('classes.bulk-promote*') && !request()->routeIs('classes.grading') ? 'active' : '' }}" data-tip="Classes">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Classes</span>
        </a>
        <a href="{{ route('classes.promotion.preview') }}" class="nav-item {{ request()->routeIs('classes.promotion*') || request()->routeIs('classes.bulk-promote*') || request()->routeIs('classes.grading') ? 'active' : '' }}" data-tip="Promotion Engine">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            <span class="nav-label">Promotion Engine</span>
        </a>
        @endif
        @if($u->canAccessModule('academic-cycle'))
        <a href="{{ route('academic-cycle.index') }}" class="nav-item {{ request()->routeIs('academic-cycle.*') ? 'active' : '' }}" data-tip="Academic Session">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 2v2H5c-1.1 0-2 .9-2 2v13c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-2V2h-2v2H9V2H7zm12 8H5V7h14v3zm-9 4H7v-2h3v2zm5 0h-3v-2h3v2zm4 0h-2v-2h2v2zm-9 4H7v-2h3v2zm5 0h-3v-2h3v2z"/></svg>
            <span class="nav-label">Academic Session</span>
        </a>
        @endif
        @if($u->canAccessModule('curriculum'))
        <a href="{{ route('curriculum.tracks') }}" class="nav-item {{ request()->routeIs('curriculum.*') ? 'active' : '' }}" data-tip="Curriculum">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
            <span class="nav-label">Curriculum</span>
        </a>
        @endif
        @if($u->canAccessModule('subjects'))
        <a href="{{ route('subjects.index') }}" class="nav-item {{ request()->routeIs('subjects.*') ? 'active' : '' }}" data-tip="Subjects">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1z"/></svg>
            <span class="nav-label">Subjects</span>
        </a>
        @endif
        @if($u->canAccessModule('attendance'))
        <a href="{{ route('attendance.index') }}" class="nav-item {{ request()->routeIs('attendance.*') && !request()->routeIs('staff-attendance.*') ? 'active' : '' }}" data-tip="Student Attendance">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
            <span class="nav-label">Student Attendance</span>
        </a>
        @endif

           {{-- Staff Attendance — gated by plan feature --}}
        @if($u->canAccessModule('staff-attendance'))
        <a href="{{ route('staff-attendance.my') }}"
           class="nav-item {{ request()->routeIs('staff-attendance.*') ? 'active' : '' }}"
           data-tip="Staff Attendance">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            <span class="nav-label">Staff Attendance</span>
        </a>
        @endif
        @if($u->canAccessModule('timetable'))
        <a href="{{ route('timetable.index') }}" class="nav-item {{ request()->routeIs('timetable.*') ? 'active' : '' }}" data-tip="Timetable">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
            <span class="nav-label">Timetable</span>
        </a>
        @endif
        @if($u->canAccessModule('lesson-planner'))
        <a href="{{ route('lesson-planner.index') }}" class="nav-item {{ request()->routeIs('lesson-planner.*') ? 'active' : '' }}" data-tip="Lesson Planner">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>
            <span class="nav-label">Lesson Planner</span>
            <span style="font-size:9px;background:linear-gradient(135deg,#7C3AED,#4F46E5);color:white;padding:1px 5px;border-radius:8px;margin-left:4px;font-weight:700">AI</span>
        </a>
        @endif
        @if($u->canAccessModule('skills'))
        <a href="{{ route('skills.index') }}" class="nav-item {{ request()->routeIs('skills.*') ? 'active' : '' }}" data-tip="Skill Ratings">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            <span class="nav-label">Skill Ratings</span>
        </a>
        @endif
    </div>
    @endif

    {{-- ASSESSMENTS --}}
    @if($u->canAccessModule('scores') || $u->canAccessModule('reports') || $u->canAccessModule('cbt') || $u->canAccessModule('gradebook'))
    <div class="nav-section">
        <div class="nav-section-label">Assessments</div>
        @if($u->canAccessModule('scores'))
        <a href="{{ route('scores.index') }}" class="nav-item {{ request()->routeIs('scores.*') ? 'active' : '' }}" data-tip="Scores">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            <span class="nav-label">Scores</span>
        </a>
        @endif
        @if($u->canManage('reports'))
        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-tip="Report Cards">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            <span class="nav-label">Report Cards</span>
        </a>
        @endif
        {{-- Transcript: admin, principal, vice principal only --}}
        @if($u->canAccessModule('transcript'))
        <a href="{{ route('students.transcript.index') }}" class="nav-item {{ request()->routeIs('students.transcript*') ? 'active' : '' }}" data-tip="Transcripts">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 8H7v-2h4v2zm6-4H7v-2h10v2zm0-4H7V9h4.5l1.5 1.5V11h4v2z"/></svg>
            <span class="nav-label">Transcripts</span>
        </a>
        @endif

        @if($u->canAccessModule('cbt'))
        <a href="{{ route('cbt.banks') }}" class="nav-item {{ request()->routeIs('cbt.*') ? 'active' : '' }}" data-tip="CBT Exams">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
            <span class="nav-label">CBT Exams</span>
        </a>
        @endif
    </div>
    @endif

    {{-- ADMISSIONS --}}
    @if($u->canAccessModule('admissions'))
    <div class="nav-section">
        <div class="nav-section-label">Admissions</div>
        <a href="{{ route('admissions.index') }}" class="nav-item {{ request()->routeIs('admissions.index') || request()->routeIs('admissions.show') || request()->routeIs('admissions.create') ? 'active' : '' }}" data-tip="Applications">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            <span class="nav-label">Applications</span>
        </a>
        <a href="{{ route('admissions.portal') }}" class="nav-item {{ request()->routeIs('admissions.portal*') ? 'active' : '' }}" data-tip="Online Portal">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            <span class="nav-label">Online Portal</span>
        </a>
        @if($u->canAccessModule('transfers'))
        <a href="{{ route('students.transfers.index') }}" class="nav-item {{ request()->routeIs('students.transfers.*') ? 'active' : '' }}" data-tip="Transfers">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 3L5 6.99h3V14h2V6.99h3L9 3zm7 14.01V10h-2v7.01h-3L15 21l4-3.99h-3z"/></svg>
            <span class="nav-label">Transfers</span>
        </a>
        @endif
    </div>
    @endif

    {{-- FINANCE --}}
    @if($u->canAccessModule('fees') || $u->canAccessModule('expenses') || $u->canAccessModule('payroll'))
    <div class="nav-section">
        <div class="nav-section-label">Finance</div>
        @if($u->canAccessModule('fees'))
        <a href="{{ route('fees.subaccounts') }}" class="nav-item {{ request()->routeIs('fees.subaccounts') || request()->routeIs('fees.categories') || request()->routeIs('fees.structures') ? 'active' : '' }}" data-tip="Fee Setup">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            <span class="nav-label">Fee Setup</span>
        </a>
        <a href="{{ route('fees.invoices') }}" class="nav-item {{ request()->routeIs('fees.invoices*') || request()->routeIs('fees.payment*') ? 'active' : '' }}" data-tip="Invoices">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
            <span class="nav-label">Invoices & Payments</span>
        </a>
        <a href="{{ route('fees.generate.index') }}" class="nav-item {{ request()->routeIs('fees.generate.*') ? 'active' : '' }}" data-tip="Generate Invoices">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
            <span class="nav-label">Generate Invoices</span>
        </a>
        <a href="{{ route('fees.plans.index') }}" class="nav-item {{ request()->routeIs('fees.plans.*') ? 'active' : '' }}" data-tip="Payment Plans">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            <span class="nav-label">Payment Plans</span>
        </a>
        <a href="{{ route('fees.reminders.index') }}" class="nav-item {{ request()->routeIs('fees.reminders.*') ? 'active' : '' }}" data-tip="Fee Reminders">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <span class="nav-label">Fee Reminders</span>
        </a>
        <a href="{{ route('fees.gateway.settings') }}" class="nav-item {{ request()->routeIs('fees.gateway.*') ? 'active' : '' }}" data-tip="Online Payments">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
            <span class="nav-label">Online Payments</span>
        </a>
        @endif
        @if($u->canAccessModule('expenses'))
        <a href="{{ route('expenses.index') }}" class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}" data-tip="Expenses">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
            <span class="nav-label">Expenses</span>
        </a>
        @endif
        @if($u->canAccessModule('payroll'))
        <a href="{{ route('payroll.index') }}" class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}" data-tip="Payroll">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            <span class="nav-label">Payroll</span>
        </a>
        @endif
    </div>
    @endif

    {{-- OPERATIONS --}}
    @if($u->canAccessModule('health') || $u->canAccessModule('library') || $u->canAccessModule('transport') || $u->canAccessModule('announcements') || $u->canAccessModule('calendar') || $u->canAccessModule('messages') || $u->canAccessModule('notifications') || $u->canAccessModule('sms'))
    <div class="nav-section">
        <div class="nav-section-label">Operations</div>
        @if($u->canAccessModule('health'))
        <a href="{{ route('health.index') }}" class="nav-item {{ request()->routeIs('health.*') ? 'active' : '' }}" data-tip="Health Records">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10.5 13H8v-3h2.5V7.5h3V10H16v3h-2.5v2.5h-3V13zM12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z"/></svg>
            <span class="nav-label">Health Records</span>
        </a>
        @endif
        @if($u->canAccessModule('library'))
        <a href="{{ route('library.index') }}" class="nav-item {{ request()->routeIs('library.*') ? 'active' : '' }}" data-tip="Library">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/></svg>
            <span class="nav-label">Library</span>
        </a>
        @endif
        @if($u->canAccessModule('transport'))
        <a href="{{ route('transport.routes') }}" class="nav-item {{ request()->routeIs('transport.*') ? 'active' : '' }}" data-tip="Transport">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-6H6V6h12v5z"/></svg>
            <span class="nav-label">Transport</span>
        </a>
        @endif
        @if($u->canAccessModule('announcements'))
        <a href="{{ route('announcements.index') }}" class="nav-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}" data-tip="Announcements">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
            <span class="nav-label">Announcements</span>
        </a>
        @endif
        @if($u->canAccessModule('calendar'))
        <a href="{{ route('calendar.index') }}" class="nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}" data-tip="Calendar">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
            <span class="nav-label">Calendar</span>
        </a>
        @endif
        @if($u->canAccessModule('messages'))
        <a href="{{ route('messages.inbox') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}" data-tip="Messages">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
            <span class="nav-label">Messages</span>
        </a>
        @endif
        <a href="{{ route('platform.notices') }}" class="nav-item {{ request()->routeIs('platform.notices') ? 'active' : '' }}" data-tip="Platform Notices">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <span class="nav-label">Notices</span>
        </a>
        <a href="{{ route('support.index') }}" class="nav-item {{ request()->routeIs('support.*') ? 'active' : '' }}" data-tip="Platform Support">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
            <span class="nav-label">Support</span>
        </a>
        @if($u->canAccessModule('notifications'))
        <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') && !request()->routeIs('notifications.triggers*') ? 'active' : '' }}" data-tip="Notifications">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
            <span class="nav-label">Notifications</span>
        </a>
        @endif
        @if($u->canManage('notifications'))
        <a href="{{ route('notifications.triggers') }}" class="nav-item {{ request()->routeIs('notifications.triggers*') ? 'active' : '' }}" data-tip="Auto Triggers">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
            <span class="nav-label">Auto Triggers</span>
        </a>
        @endif
        @if($u->canAccessModule('sms'))
        <a href="{{ route('sms.index') }}" class="nav-item {{ request()->routeIs('sms.*') ? 'active' : '' }}" data-tip="SMS Campaigns">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12zM7 9h2v2H7V9zm4 0h2v2h-2V9zm4 0h2v2h-2V9z"/></svg>
            <span class="nav-label">SMS Campaigns</span>
        </a>
        @endif
    </div>
    @endif

    {{-- REPORTING --}}
    @if($u->canAccessModule('analytics') || $u->canAccessModule('risk') || $u->canAccessModule('exports') || $u->canAccessModule('asc'))
    <div class="nav-section">
        <div class="nav-section-label">Reporting</div>
        @if($u->canAccessModule('analytics'))
        <a href="{{ route('analytics.index') }}" class="nav-item {{ request()->routeIs('analytics.*') && !request()->routeIs('analytics.financial') ? 'active' : '' }}" data-tip="Analytics">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
            <span class="nav-label">Analytics</span>
        </a>
        <a href="{{ route('analytics.financial') }}" class="nav-item {{ request()->routeIs('analytics.financial') ? 'active' : '' }}" data-tip="Financial Report">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            <span class="nav-label">Financial Report</span>
        </a>
        @endif
        @if($u->canAccessModule('risk'))
        <a href="{{ route('risk.index') }}" class="nav-item {{ request()->routeIs('risk.*') ? 'active' : '' }}" data-tip="Risk Flags">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
            <span class="nav-label">Risk Flags</span>
        </a>
        @endif
        @if($u->canAccessModule('exports'))
        <a href="{{ route('exports.index') }}" class="nav-item {{ request()->routeIs('exports.*') ? 'active' : '' }}" data-tip="Export Data">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            <span class="nav-label">Export Data</span>
        </a>
        @endif
        @if($u->canAccessModule('asc'))
        <a href="{{ route('asc.report') }}" class="nav-item {{ request()->routeIs('asc.*') ? 'active' : '' }}" data-tip="School Census (ASC)">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
            <span class="nav-label">School Census (ASC)</span>
        </a>
        @endif
    </div>
    @endif

    {{-- SETTINGS --}}
    <div class="nav-section">
        <div class="nav-section-label">Settings</div>
        <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" data-tip="My Profile">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span class="nav-label">My Profile</span>
        </a>

    @if($u->canAccessModule('settings') || $u->canAccessModule('portal-accounts'))
        @if($u->canAccessModule('portal-accounts'))
        <a href="{{ route('portal-accounts.index') }}" class="nav-item {{ request()->routeIs('portal-accounts.*') ? 'active' : '' }}" data-tip="Portal Accounts">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
            <span class="nav-label">Portal Accounts</span>
        </a>
        @endif
        {{-- Subscription billing — admin only --}}
        @if($u->isAdmin())
        <a href="{{ route('billing.subscription') }}" class="nav-item {{ request()->routeIs('billing.*') ? 'active' : '' }}" data-tip="Subscription">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
            <span class="nav-label">Subscription</span>
        </a>
        @endif

        @if($u->canAccessModule('settings'))
        <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" data-tip="School Settings">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
            <span class="nav-label">School Settings</span>
        </a>
        @endif
    @endif
    </div>

    {{-- SUPER ADMIN --}}
    @if(auth()->user()?->is_super_admin)
    <div class="nav-section">
        <div class="nav-section-label">Super Admin</div>
        <a href="{{ route('super.dashboard') }}" class="nav-item {{ request()->routeIs('super.dashboard') ? 'active' : '' }}" data-tip="Admin Panel">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            <span class="nav-label">Admin Panel</span>
        </a>
        <a href="{{ route('super.tenants') }}" class="nav-item {{ request()->routeIs('super.tenants*') || request()->routeIs('super.tenant*') ? 'active' : '' }}" data-tip="All Schools">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
            <span class="nav-label">All Schools</span>
        </a>
    </div>
    @endif
    {{-- STOP IMPERSONATING --}}
    @if(session('impersonating'))
    <div style="margin-top:auto;padding:12px 10px;border-top:1px solid rgba(255,255,255,0.1)">
        <form method="POST" action="{{ route('super.stop-impersonating') }}">
            @csrf
            <button type="submit" style="width:100%;padding:9px 12px;background:rgba(239,68,68,0.2);color:#FCA5A5;border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;text-align:left;display:flex;align-items:center;gap:8px">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                <span class="nav-label">Exit School View</span>
            </button>
        </form>
    </div>
    @endif

    {{-- User footer --}}
    <div class="sidebar-footer">
        {{-- Logout button — full width, above user block --}}
        <form method="POST" action="{{ route('logout') }}" style="margin:0 0 8px 0">
            @csrf
            <button type="submit" class="logout-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
                <span class="nav-label">Logout</span>
            </button>
        </form>
        {{-- User profile link --}}
        <a href="{{ route('profile.edit') }}" class="user-block" title="My Profile" style="text-decoration:none">
            <div class="user-av">
                @if(auth()->user()->passport_photo)
                    <img src="{{ Storage::url(auth()->user()->passport_photo) }}" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ auth()->user()->roleLabel() ?? 'staff' }}</div>
            </div>
        </a>
    </div>
</aside>

<div class="main" id="mainContent">
    <header class="topbar">
        <div class="topbar-left">
            <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Menu">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        </div>
        <div class="topbar-right">
            @if(session('impersonating_tenant_id'))
                <form method="POST" action="{{ route('super.stop-impersonating') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="exit-btn">← Exit School View</button>
                </form>
            @endif
            @if(auth()->user()->is_super_admin && !session('impersonating_tenant_id'))
                <span class="super-pill">⚡ Super Admin</span>
            @elseif(auth()->user()->tenant)
                <span class="tenant-pill">{{ auth()->user()->tenant->name }}</span>
            @endif
        </div>
    </header>

    <div class="page-content">
        @if(isset($tenantAccessDecision) && $tenantAccessDecision->isWarning() && $tenantAccessDecision->state !== 'trial')
            <div class="tenant-access-banner">
                <strong>{{ $tenantAccessDecision->title() }}</strong>
                <span>{{ $tenantAccessDecision->message }}</span>
                @if(Route::has('tenant.account-status'))
                    <a href="{{ route('tenant.account-status') }}">Account status</a>
                @endif
            </div>
        @endif

        @yield('content')
    </div>
</div>

@stack('scripts')
<script>
// ── Sidebar collapse (desktop) ──────────────────────────────────────
const COLLAPSE_KEY = 'sms_sidebar_collapsed';

function toggleCollapse() {
    const sidebar = document.getElementById('sidebar');
    const main    = document.getElementById('mainContent');
    const isNowCollapsed = !sidebar.classList.contains('collapsed');
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('sidebar-collapsed');
    localStorage.setItem(COLLAPSE_KEY, isNowCollapsed ? '1' : '0');
}

// Restore collapsed state on load
(function() {
    if (window.innerWidth > 768 && localStorage.getItem(COLLAPSE_KEY) === '1') {
        document.getElementById('sidebar').classList.add('collapsed');
        document.getElementById('mainContent').classList.add('sidebar-collapsed');
    }
})();

// ── Sidebar toggle (mobile) ─────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
    document.body.style.overflow =
        document.getElementById('sidebar').classList.contains('open') ? 'hidden' : '';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
document.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) closeSidebar();
    });
});

// ── Scroll active nav item into view on page load ───────────────────
document.addEventListener('DOMContentLoaded', function () {
    const sidebar    = document.getElementById('sidebar');
    const activeItem = sidebar ? sidebar.querySelector('.nav-item.active') : null;
    if (sidebar && activeItem) {
        const sidebarH = sidebar.clientHeight;
        const itemTop  = activeItem.offsetTop;
        const itemH    = activeItem.clientHeight;
        const isVisible = itemTop >= sidebar.scrollTop && itemTop + itemH <= sidebar.scrollTop + sidebarH;
        if (!isVisible) sidebar.scrollTop = itemTop - (sidebarH / 2) + (itemH / 2);
    }
});
</script>
</body>
</html>
