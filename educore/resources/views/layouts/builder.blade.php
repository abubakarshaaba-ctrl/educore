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
    --builder-bar-h: 56px;
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
    background: var(--midnight);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
    position: relative;
    z-index: 10;
}
.builder-bar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.builder-exit {
    display: inline-flex; align-items: center; gap: 6px;
    color: rgba(255,255,255,.75); text-decoration: none; font-size: 13px;
    font-weight: 600; padding: 6px 10px; border-radius: 7px;
    transition: background 150ms;
    flex-shrink: 0;
}
.builder-exit:hover { background: rgba(255,255,255,.1); color: white; }
.builder-title-wrap { min-width: 0; }
.builder-title { font-size: 14px; font-weight: 700; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.builder-subtitle { font-size: 11px; color: rgba(255,255,255,.55); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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

@stack('styles')
    </style>
</head>
<body class="builder-mode">

<header class="builder-bar">
    <div class="builder-bar-left">
        <a href="{{ $builderExitUrl ?? route('cbt.banks') }}" class="builder-exit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Exit Builder
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
