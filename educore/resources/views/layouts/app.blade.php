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

    @include('layouts.partials.full-nav')

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
