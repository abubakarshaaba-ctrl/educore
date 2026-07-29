<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Question Builder') — EduCore</title>
    <link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
{!! \App\Helpers\ThemeHelper::css() !!}
    <link rel="stylesheet" href="/brand/educore-brand.css">
    <style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

:root {
    --brand-navy:  #071E45;
    --brand-gold:  #D79A21;
    --brand-gold-l:#F2C35B;
    --brand-gray:  #7A7F87;
    --indigo:      #D79A21;
    --indigo-dark: #B8810D;
    --indigo-bg:   #FEF9EC;
    --emerald:     #059669;
    --amber:       #D79A21;
    --crimson:     #DC2626;
    --midnight:    #071E45;
    --slate:       #475569;
    --slate-light: #7A7F87;
    --border:      #E4E8EF;
    --bg:          #F4F6FA;
    --white:       #FFFFFF;
    --builder-bar-h: 92px;
}

html, body {
    height: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg);
    color: var(--slate);
    overflow: hidden; /* the builder panes scroll internally, not the page */
}

/* ── Distraction-free top bar — no sidebar, no app chrome ──────────── */
.builder-bar {
    height: var(--builder-bar-h);
    background:
        radial-gradient(circle at 82% -70%, rgba(215,154,33,.22), transparent 38%),
        linear-gradient(118deg, #031229 0%, var(--midnight) 58%, #092B5F 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 0 28px;
    border-bottom: 2px solid var(--brand-gold);
    box-shadow: 0 10px 30px rgba(7,30,69,.18);
    position: relative;
    z-index: 10;
}
.builder-bar::after {
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    opacity:.14;
    background-image:
        linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
    background-size:36px 36px;
    mask-image:linear-gradient(90deg, transparent, black 55%, black);
}
.builder-bar-left { display: flex; align-items: center; gap: 18px; min-width: 0; position:relative; z-index:1; }
.builder-brand {
    display:flex;align-items:center;gap:10px;padding-right:18px;
    border-right:1px solid rgba(255,255,255,.16);flex-shrink:0;
}
.builder-brand img{width:42px;height:42px;border-radius:11px;display:block;box-shadow:0 7px 18px rgba(0,0,0,.2)}
.builder-brand-copy{line-height:1.1}
.builder-brand-name{font-size:15px;font-weight:850;color:white;letter-spacing:-.02em}
.builder-brand-name span{color:var(--brand-gold-l)}
.builder-brand-kicker{font-size:8px;color:rgba(255,255,255,.58);text-transform:uppercase;letter-spacing:.16em;margin-top:4px}
.builder-exit {
    display: inline-flex; align-items: center; gap: 6px;
    color: rgba(255,255,255,.78); text-decoration: none; font-size: 12px;
    font-weight: 750; padding: 9px 11px; border-radius: 10px;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.055);
    transition: background 150ms, border-color 150ms, transform 150ms;
    flex-shrink: 0;
}
.builder-exit:hover { background: rgba(255,255,255,.12); border-color:rgba(255,255,255,.24); color: white; transform:translateY(-1px); }
.builder-title-wrap { min-width: 0; }
.builder-eyebrow{font-size:8.5px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--brand-gold-l);margin-bottom:4px}
.builder-title { font-size: 17px; font-weight: 850; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing:-.025em; }
.builder-subtitle { font-size: 11px; color: rgba(255,255,255,.62); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top:4px; }
.builder-bar-right {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
    position:relative;z-index:1;padding:7px;
    border:1px solid rgba(255,255,255,.11);border-radius:14px;
    background:rgba(2,13,31,.24);backdrop-filter:blur(8px);
}
.builder-pill {
    display:inline-flex;align-items:center;gap:6px;
    font-size: 10.5px; font-weight: 750; padding: 7px 10px; border-radius: 9px;
    background: rgba(215,154,33,.14); color: #FFE09A;
    border: 1px solid rgba(215,154,33,.3); white-space: nowrap;
}
.builder-pill::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--brand-gold-l);box-shadow:0 0 0 3px rgba(242,195,91,.13)}
.builder-action{
    display:inline-flex;align-items:center;justify-content:center;gap:6px;
    min-height:36px;padding:8px 11px;border:1px solid rgba(255,255,255,.17);
    border-radius:9px;background:rgba(255,255,255,.075);color:white;
    font:750 11px/1 inherit;text-decoration:none;cursor:pointer;white-space:nowrap;
    transition:background 150ms,border-color 150ms,transform 150ms,box-shadow 150ms;
}
.builder-action:hover{background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.3);transform:translateY(-1px)}
.builder-action svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.builder-action-primary{
    background:linear-gradient(135deg,#F0B32D,var(--brand-gold));border-color:#F1BB43;color:#071E45;
    box-shadow:0 7px 16px rgba(215,154,33,.2)
}
.builder-action-primary:hover{background:linear-gradient(135deg,#F5C553,#E4A51C);border-color:#F5CA68;box-shadow:0 9px 20px rgba(215,154,33,.27)}

/* ── Main workspace — fills remaining viewport height ───────────────── */
.builder-body {
    height: calc(100vh - var(--builder-bar-h));
    overflow: hidden;
}

@media(max-width:1050px){
    .builder-brand-copy{display:none}
    .builder-brand{padding-right:10px}
    .builder-bar{padding:0 14px}
    .builder-action{padding:8px 9px}
}
@media(max-width:820px){
    :root{--builder-bar-h:148px}
    .builder-bar{height:var(--builder-bar-h);align-items:flex-start;flex-wrap:wrap;padding-top:12px;padding-bottom:10px;gap:8px}
    .builder-bar-left{width:100%;height:54px}
    .builder-bar-right{width:100%;overflow-x:auto;padding:7px;scrollbar-width:none}
    .builder-bar-right::-webkit-scrollbar{display:none}
    .builder-title{font-size:14px}
}
@media(max-width:480px){
    .builder-brand{display:none}
    .builder-exit span{display:none}
}

    </style>
    @stack('styles')
</head>
<body class="builder-mode">

<header class="builder-bar">
    <div class="builder-bar-left">
        <div class="builder-brand">
            <img src="/brand/educore-icon.svg" alt="EduCore">
            <div class="builder-brand-copy">
                <div class="builder-brand-name">Edu<span>Core</span></div>
                <div class="builder-brand-kicker">Assessment Studio</div>
            </div>
        </div>
        <a href="{{ $builderExitUrl ?? route('cbt.banks') }}" class="builder-exit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            <span>Exit Builder</span>
        </a>
        <div class="builder-title-wrap">
            <div class="builder-eyebrow">Question bank workspace</div>
            <div class="builder-title">@yield('builder-title', 'Question Builder')</div>
            <div class="builder-subtitle">@yield('builder-subtitle', '')</div>
        </div>
    </div>
    <div class="builder-bar-right">
        @yield('builder-bar-right')
    </div>
</header>

<main class="builder-body">
    @yield('content')
</main>

@stack('scripts')
</body>
</html>
