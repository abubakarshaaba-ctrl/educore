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
    --builder-bar-h: 78px;
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
        radial-gradient(circle at 75% -80%, rgba(215,154,33,.27), transparent 42%),
        linear-gradient(115deg, #04132D 0%, var(--midnight) 64%, #0A2C61 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    border-bottom: 3px solid var(--brand-gold);
    box-shadow: 0 8px 24px rgba(7,30,69,.22);
    position: relative;
    z-index: 10;
}
.builder-bar-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
.builder-brand {
    display:flex;align-items:center;gap:9px;padding-right:16px;
    border-right:1px solid rgba(255,255,255,.16);flex-shrink:0;
}
.builder-brand img{width:36px;height:36px;border-radius:9px;display:block}
.builder-brand-copy{line-height:1.1}
.builder-brand-name{font-size:13px;font-weight:800;color:white}
.builder-brand-name span{color:var(--brand-gold-l)}
.builder-brand-kicker{font-size:8px;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.12em;margin-top:3px}
.builder-exit {
    display: inline-flex; align-items: center; gap: 6px;
    color: rgba(255,255,255,.75); text-decoration: none; font-size: 13px;
    font-weight: 700; padding: 8px 10px; border-radius: 9px;
    transition: background 150ms;
    flex-shrink: 0;
}
.builder-exit:hover { background: rgba(255,255,255,.1); color: white; }
.builder-title-wrap { min-width: 0; }
.builder-title { font-size: 16px; font-weight: 800; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing:-.02em; }
.builder-subtitle { font-size: 11px; color: rgba(255,255,255,.62); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top:3px; }
.builder-bar-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.builder-pill {
    font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px;
    background: rgba(215,154,33,.18); color: var(--brand-gold-l);
    border: 1px solid rgba(215,154,33,.35); white-space: nowrap;
}

/* ── Main workspace — fills remaining viewport height ───────────────── */
.builder-body {
    height: calc(100vh - var(--builder-bar-h));
    overflow: hidden;
}

@media(max-width:1050px){
    .builder-brand-copy{display:none}
    .builder-brand{padding-right:10px}
    .builder-bar{padding:0 14px}
}
@media(max-width:820px){
    :root{--builder-bar-h:132px}
    .builder-bar{height:var(--builder-bar-h);align-items:flex-start;flex-wrap:wrap;padding-top:12px;padding-bottom:10px;gap:8px}
    .builder-bar-left{width:100%;height:42px}
    .builder-bar-right{width:100%;overflow-x:auto;padding:2px 0 4px;scrollbar-width:none}
    .builder-bar-right::-webkit-scrollbar{display:none}
    .builder-title{font-size:14px}
}
@media(max-width:480px){
    .builder-brand{display:none}
    .builder-exit span{display:none}
}

@stack('styles')
    </style>
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
