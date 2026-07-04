<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Prevent mobile browsers caching the login form with a stale CSRF token (419) --}}
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('page-title', 'EduCore')) }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/brand/favicon.svg') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --ec-navy: #071E45;
            --ec-gold: rgb(215, 154, 33);
            --ec-gold-light: #F2C35B;
            --ec-ink: #101828;
            --ec-muted: #667085;
            --ec-soft: #98A2B3;
            --ec-border: #D8E0E8;
            --ec-border-soft: #EAEEF3;
            --ec-page: #F4F7FB;
            --ec-surface: #FFFFFF;
            --ec-danger: #B42318;
            --ec-success: #067647;
            --ec-warning: #B54708;
            --ec-info: #175CD3;
            --ec-focus: rgba(215, 154, 33, .24);
            --tenant-primary: var(--ec-navy);
            --tenant-accent: var(--ec-gold);
            --panel-shadow: 0 24px 70px rgba(7, 30, 69, .14);
            --card-radius: 16px;
        }

        html {
            width: 100%;
            min-height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ec-ink);
            background: var(--ec-page);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            background: var(--ec-page);
        }

        a { color: inherit; }
        img, svg { display: block; }

        :focus-visible {
            outline: 3px solid var(--ec-focus);
            outline-offset: 3px;
            border-radius: 8px;
        }

        .auth-shell {
            width: 100%;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(340px, .72fr);
            background: var(--ec-page);
        }

        .ec-auth__brand,
        .auth-brand {
            position: relative;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 24px;
            padding: clamp(26px, 4vh, 44px) clamp(34px, 5vw, 64px);
            color: #FFFFFF;
            background:
                linear-gradient(135deg, rgba(215, 154, 33, .18), transparent 34%),
                linear-gradient(145deg, #061733 0%, var(--ec-navy) 58%, #0A1628 100%);
            overflow: hidden;
            /* isolation:isolate removed — causes GPU compositing artifacts on Android WebView */
        }

        .auth-brand::before,
        .auth-brand::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .auth-brand::before {
            width: 380px;
            height: 380px;
            right: -160px;
            top: -140px;
        }

        .auth-brand::after {
            width: 240px;
            height: 240px;
            left: -112px;
            bottom: 24px;
        }

        .auth-brand--tenant {
            background:
                linear-gradient(135deg, rgba(215,154,33,.20), transparent 34%),
                linear-gradient(145deg, var(--tenant-primary) 0%, var(--tenant-primary) 56%, #0A1628 100%);
        }

        .auth-brand__top,
        .auth-brand__bottom,
        .auth-brand__body {
            position: relative;
            z-index: 1;
        }

        .auth-brand__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .ec-brand-logo {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            max-width: 210px;
            text-decoration: none;
        }

        .ec-brand-logo img {
            width: 100%;
            max-width: 210px;
            height: auto;
            object-fit: contain;
        }

        .ec-brand-logo--compact {
            max-width: 132px;
        }

        .ec-brand-logo--compact img {
            max-width: 132px;
        }

        .ec-brand-logo--light {
            padding: 4px;
            border-radius: 8px;
            background: #FFFFFF;
            border: 1px solid var(--ec-border-soft);
        }

        .auth-brand__body {
            max-width: 650px;
            margin: auto 0;
        }

        .auth-eyebrow {
            margin: 0 0 10px;
            color: var(--ec-gold-light);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .auth-brand__title,
        .auth-title {
            margin: 0;
            letter-spacing: 0;
            font-weight: 850;
        }

        .auth-brand__title {
            max-width: 640px;
            font-size: clamp(2.4rem, 5vw, 4.35rem);
            line-height: 1.02;
        }

        .auth-brand__title span {
            color: var(--ec-gold-light);
        }

        .auth-brand__lead {
            max-width: 620px;
            margin: 16px 0 0;
            color: rgba(255, 255, 255, .76);
            font-size: .98rem;
            line-height: 1.65;
        }

        .auth-feature-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 22px;
            max-width: 630px;
        }

        .auth-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .06);
            color: rgba(255, 255, 255, .88);
            font-size: .81rem;
            font-weight: 650;
        }

        .auth-feature svg {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
            color: var(--ec-gold-light);
        }

        .auth-brand__bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, .11);
            color: rgba(255, 255, 255, .62);
            font-size: .72rem;
            line-height: 1.45;
        }

        .auth-provider {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .auth-provider__copy {
            min-width: 0;
        }

        .auth-provider__label {
            display: block;
            color: rgba(255, 255, 255, .46);
            font-size: .58rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .auth-provider__name {
            display: block;
            color: rgba(255, 255, 255, .80);
            white-space: nowrap;
        }

        .auth-panel {
            min-width: 0;
            display: grid;
            place-items: center;
            padding: clamp(22px, 4vh, 42px);
            background-color: #F4F7FB;
            background-image:
                /* subtle dot grid */
                radial-gradient(circle, rgba(7,30,69,.08) 1px, transparent 1px),
                /* gold accent wash in corner */
                radial-gradient(ellipse 70% 60% at 110% 110%, rgba(215,154,33,.12), transparent),
                /* navy vignette top-left */
                radial-gradient(ellipse 60% 50% at -10% -10%, rgba(7,30,69,.06), transparent);
            background-size: 24px 24px, 100% 100%, 100% 100%;
        }

        .auth-card {
            width: min(100%, 420px);
            padding: clamp(20px, 3vh, 28px);
            border: 1px solid rgba(216, 224, 232, .95);
            border-radius: var(--card-radius);
            background: rgba(255, 255, 255, .98);
            box-shadow: var(--panel-shadow);
        }

        .auth-card--wide {
            width: min(100%, 620px);
        }

        .auth-card__header {
            margin-bottom: 14px;
        }

        .auth-card__eyebrow {
            margin: 0 0 8px;
            color: var(--tenant-primary);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .auth-title {
            color: var(--ec-ink);
            font-size: clamp(1.4rem, 2.4vw, 1.75rem);
            font-weight: 700;
            line-height: 1.14;
        }

        .auth-subtitle {
            margin: 9px 0 0;
            color: var(--ec-muted);
            font-size: .92rem;
            line-height: 1.58;
        }

        .ec-form-group {
            margin-bottom: 12px;
        }

        .ec-label {
            display: block;
            margin-bottom: 7px;
            color: #344054;
            font-size: .82rem;
            font-weight: 500;
        }

        .ec-input {
            width: 100%;
            min-height: 40px;
            padding: 0 14px;
            border: 1px solid var(--ec-border);
            border-radius: 10px;
            background: #FBFCFD;
            color: var(--ec-ink);
            font: inherit;
            font-size: .94rem;
            transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
        }

        .ec-input:focus {
            outline: none;
            border-color: var(--tenant-primary);
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(7,30,69,.18);
        }

        .ec-input--error {
            border-color: var(--ec-danger);
            box-shadow: 0 0 0 4px rgba(180, 35, 24, .08);
        }

        .ec-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .ec-input-wrap .ec-input {
            padding-right: 46px;
        }

        .ec-eye-btn {
            position: absolute;
            right: 10px;
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 8px;
            color: var(--ec-muted);
            background: transparent;
            cursor: pointer;
        }

        .ec-eye-btn:hover {
            color: var(--ec-ink);
            background: #F2F4F7;
        }

        .ec-field-error,
        .ec-hint {
            margin: 7px 0 0;
            font-size: .74rem;
            line-height: 1.45;
        }

        .ec-field-error {
            color: var(--ec-danger);
        }

        .ec-hint {
            color: var(--ec-soft);
        }

        .ec-remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 18px;
            color: var(--ec-muted);
            font-size: .82rem;
            user-select: none;
            cursor: pointer;
        }

        .ec-remember input {
            width: 16px;
            height: 16px;
            accent-color: var(--tenant-primary);
        }

        .ec-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 42px;
            padding: 0 18px;
            border: 0;
            border-radius: 10px;
            background: var(--ec-navy);
            color: #FFFFFF;
            font: inherit;
            font-size: .92rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform .16s ease, box-shadow .16s ease, filter .16s ease;
        }

        .ec-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px rgba(7, 30, 69, .16);
        }

        .ec-btn--tenant {
            background: var(--tenant-primary);
        }

        .ec-btn--gold {
            background: var(--ec-gold);
            color: #101828;
        }

        .ec-btn--secondary {
            background: #FFFFFF;
            color: var(--ec-navy);
            border: 1px solid var(--ec-border);
        }

        .ec-links,
        .auth-inline-links {
            display: flex;
            flex-wrap: wrap;
            gap: 11px 16px;
            align-items: center;
            margin-top: 18px;
        }

        .ec-link {
            color: var(--tenant-primary);
            font-size: .82rem;
            font-weight: 720;
            text-decoration: none;
        }

        .ec-link:hover {
            text-decoration: underline;
        }

        .ec-link--muted {
            color: var(--ec-muted);
        }

        .ec-alert {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 16px;
            padding: 11px 13px;
            border-radius: 10px;
            font-size: .83rem;
            line-height: 1.5;
        }

        .ec-alert__icon {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 20px;
            height: 20px;
            margin-top: 1px;
        }

        .ec-alert__icon svg {
            width: 18px;
            height: 18px;
        }

        .ec-alert--error { color: var(--ec-danger); background: #FEF3F2; border: 1px solid #FECDCA; }
        .ec-alert--ok { color: var(--ec-success); background: #ECFDF3; border: 1px solid #ABEFC6; }
        .ec-alert--warn { color: var(--ec-warning); background: #FFFAEB; border: 1px solid #FEDF89; }
        .ec-alert--info { color: var(--ec-info); background: #EFF8FF; border: 1px solid #B2DDFF; }

        .auth-note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 18px;
            padding: 12px 13px;
            border: 1px solid var(--ec-border-soft);
            border-radius: 12px;
            background: #F8FAFC;
            color: #475467;
            font-size: .78rem;
            line-height: 1.5;
        }

        .auth-note svg {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
            color: var(--tenant-primary);
            margin-top: 1px;
        }

        .auth-footer {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px 10px;
            margin-top: 18px;
            color: var(--ec-soft);
            font-size: .72rem;
            line-height: 1.4;
            text-align: center;
        }

        .auth-footer span + span::before {
            content: "/";
            margin-right: 10px;
            color: var(--ec-border);
        }

        .auth-tenant-lockup {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .auth-tenant-logo {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 92px;
            height: 92px;
            padding: 6px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .14);
            text-decoration: none;
            overflow: hidden;
        }

        .auth-tenant-logo img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .auth-tenant-logo--fallback {
            color: #FFFFFF;
            font-size: 1.6rem;
            font-weight: 850;
        }

        .auth-tenant-lockup--compact .auth-tenant-logo {
            width: 72px;
            height: 72px;
        }

        .auth-tenant-copy {
            min-width: 0;
        }

        .auth-tenant-label {
            margin: 0 0 4px;
            color: rgba(255, 255, 255, .55);
            font-size: .64rem;
            font-weight: 850;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .auth-tenant-copy h1 {
            margin: 0;
            color: #FFFFFF;
            font-size: clamp(1.28rem, 2.2vw, 1.72rem);
            line-height: 1.12;
            letter-spacing: 0;
            font-weight: 850;
        }

        .auth-tenant-motto {
            margin: 7px 0 0;
            color: rgba(255, 255, 255, .72);
            font-size: .86rem;
            line-height: 1.45;
        }

        .auth-portal-list {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .auth-portal-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            min-height: 52px;
            padding: 11px 14px;
            border: 1px solid var(--ec-border);
            border-radius: 11px;
            background: #FFFFFF;
            color: var(--ec-ink);
            text-decoration: none;
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }

        .auth-portal-link:hover {
            transform: translateY(-1px);
            border-color: var(--ec-border);
            box-shadow: 0 12px 24px rgba(7, 30, 69, .08);
        }

        .auth-portal-link--primary {
            border-color: var(--ec-border);
            background: #f7f9fc;
        }

        .auth-portal-link__copy {
            min-width: 0;
        }

        .auth-portal-link__label {
            display: block;
            font-size: .86rem;
            font-weight: 780;
        }

        .auth-portal-link__description {
            display: block;
            margin-top: 3px;
            color: var(--ec-muted);
            font-size: .74rem;
            line-height: 1.35;
        }

        .auth-portal-link__icon {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            color: var(--tenant-primary);
        }

        .auth-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 18px;
        }

        .auth-info-item {
            min-width: 0;
            padding: 11px 12px;
            border: 1px solid var(--ec-border-soft);
            border-radius: 10px;
            background: #FFFFFF;
        }

        .auth-info-item__label {
            display: block;
            margin-bottom: 4px;
            color: var(--ec-soft);
            font-size: .62rem;
            font-weight: 850;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .auth-info-item__value {
            display: block;
            color: #344054;
            font-size: .8rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
            text-decoration: none;
        }

        a.auth-info-item__value:hover {
            color: var(--tenant-primary);
            text-decoration: underline;
        }

        .auth-mini-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 18px;
        }

        .auth-mini-card {
            min-width: 0;
            padding: 12px;
            border: 1px solid var(--ec-border-soft);
            border-radius: 12px;
            background: #FFFFFF;
        }

        .auth-mini-card strong {
            display: block;
            margin-bottom: 4px;
            color: var(--ec-ink);
            font-size: .78rem;
        }

        .auth-mini-card span {
            display: block;
            color: var(--ec-muted);
            font-size: .72rem;
            line-height: 1.4;
        }

        .auth-split-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }

        .auth-success-mark {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            margin-bottom: 18px;
            border-radius: 14px;
            background: #ECFDF3;
            color: var(--ec-success);
            border: 1px solid #ABEFC6;
        }

        .auth-success-mark svg {
            width: 28px;
            height: 28px;
        }

        .auth-ref-code {
            display: block;
            margin: 18px 0;
            padding: 14px;
            border: 1px solid var(--ec-border);
            border-radius: 12px;
            background: #F8FAFC;
            color: var(--ec-navy);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 1.15rem;
            font-weight: 850;
            letter-spacing: .12em;
            text-align: center;
        }

        @media (min-width: 1051px) {
            html,
            body {
                height: 100%;
                overflow: hidden;
            }

            .auth-shell {
                height: 100vh;
                height: 100dvh;
                min-height: 0;
                overflow: hidden;
            }

            .auth-brand,
            .auth-panel {
                min-height: 0;
                overflow: hidden;
            }
        }

        @media (min-width: 1051px) and (max-height: 780px) {
            .auth-brand {
                padding-top: 22px;
                padding-bottom: 18px;
            }

            .auth-brand__title {
                font-size: clamp(2.05rem, 4vw, 3.45rem);
            }

            .auth-brand__lead {
                font-size: .9rem;
                line-height: 1.55;
            }

            .auth-feature-grid {
                margin-top: 16px;
                gap: 8px;
            }

            .auth-feature {
                min-height: 38px;
                padding: 8px 10px;
                font-size: .75rem;
            }

            .auth-panel {
                padding: 18px 26px;
            }

            .auth-card {
                padding-top: 22px;
                padding-bottom: 22px;
            }

            .auth-card__header {
                margin-bottom: 16px;
            }

            .ec-form-group {
                margin-bottom: 13px;
            }

            .auth-mini-grid {
                gap: 8px;
            }

            .auth-mini-card {
                padding: 10px;
            }

            .auth-portal-link {
                min-height: 46px;
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .auth-shell--gateway .auth-card {
                padding: 18px;
            }

            .auth-shell--gateway .auth-title {
                font-size: 1.82rem;
            }

            .auth-shell--gateway .auth-subtitle {
                font-size: .84rem;
                line-height: 1.42;
            }

            .auth-shell--gateway .auth-info-grid {
                gap: 8px;
                margin-top: 12px;
            }

            .auth-shell--gateway .auth-info-item {
                padding: 8px 10px;
            }

            .auth-shell--gateway .auth-portal-list {
                gap: 7px;
                margin-top: 12px;
            }

            .auth-shell--gateway .auth-portal-link {
                min-height: 42px;
                padding: 7px 10px;
            }

            .auth-shell--gateway .auth-portal-link__description {
                margin-top: 2px;
                font-size: .68rem;
                line-height: 1.22;
            }

            .auth-shell--gateway .auth-note {
                margin-top: 12px;
                padding: 9px 10px;
            }
        }

        @media (max-width: 1050px) {
            html,
            body {
                height: auto;
                overflow-x: hidden;
                overflow-y: auto;
            }

            .auth-shell {
                grid-template-columns: 1fr;
                min-height: 100dvh;
                max-width: 100vw;
                overflow-x: hidden;
            }

            .auth-brand {
                min-height: auto;
                width: 100%;
                padding: 32px 24px 36px;
                max-width: 100vw;
                overflow: hidden;
            }

            .auth-brand__body {
                width: 100%;
                margin: 28px 0 0;
                max-width: 100%;
                min-width: 0;
            }

            .auth-panel {
                padding: 32px 18px;
                max-width: 100vw;
                overflow-x: hidden;
            }

            .auth-brand__bottom {
                flex-direction: column;
                align-items: flex-start;
                margin-top: 24px;
            }
        }

        @media (max-width: 640px) {
            /* Disable decorative circles on mobile — they cause GPU compositing
               artifacts (horizontal stripe glitch) on Android WebView */
            .auth-brand::before,
            .auth-brand::after { display: none; }

            .auth-brand {
                padding: 26px 18px 30px;
            }

            .auth-brand__top,
            .auth-tenant-lockup {
                align-items: flex-start;
                flex-direction: column;
            }

            .auth-brand__title {
                font-size: 1.72rem;
                line-height: 1.12;
                width: 100%;
                max-width: 100%;
                overflow-wrap: anywhere;
            }

            .auth-brand__lead,
            .auth-subtitle,
            .auth-note,
            .auth-feature-grid,
            .auth-feature {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-wrap: anywhere;
            }

            .auth-feature-grid,
            .auth-info-grid,
            .auth-mini-grid,
            .auth-split-actions {
                grid-template-columns: 1fr;
            }

            .auth-card {
                padding: 22px 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: .001ms !important;
            }
        }

        /* Refined EduCore login experience. Scoped to login surfaces only. */
        .auth-shell--refined {
            grid-template-columns: minmax(390px, 42%) minmax(520px, 58%);
            min-height: 100dvh;
            background: #FFFFFF;
        }

        .auth-shell--refined .auth-brand {
            min-height: 100dvh;
            padding: clamp(34px, 5vw, 68px);
            justify-content: center;
            gap: clamp(34px, 7vh, 70px);
            background: var(--ec-navy);
        }

        .auth-brand__identity {
            position: relative;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 16px;
            width: fit-content;
            color: #FFFFFF;
            text-decoration: none;
        }

        .auth-brand__identity img {
            width: clamp(62px, 6vw, 88px);
            height: clamp(62px, 6vw, 88px);
            object-fit: contain;
        }

        .auth-brand__wordmark {
            color: #FFFFFF;
            font-size: clamp(1.55rem, 2.4vw, 2.35rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: .08em;
        }

        .auth-shell--refined .auth-brand__body {
            position: relative;
            z-index: 2;
            width: min(100%, 520px);
            margin: 0;
        }

        .auth-brand__rule {
            width: 52px;
            height: 3px;
            margin-bottom: 24px;
            background: var(--ec-gold);
        }

        .auth-shell--refined .auth-brand__title {
            max-width: 520px;
            color: #FFFFFF;
            font-size: clamp(2rem, 3.7vw, 3.35rem);
            font-weight: 730;
            line-height: 1.08;
        }

        .auth-shell--refined .auth-brand__lead {
            max-width: 470px;
            margin-top: 20px;
            color: rgba(255, 255, 255, .72);
            font-size: .96rem;
            line-height: 1.7;
        }

        .auth-brand__motif {
            position: absolute;
            z-index: 1;
            right: -28px;
            bottom: 38px;
            width: min(88%, 560px);
            height: auto;
            color: rgba(255, 255, 255, .16);
            pointer-events: none;
        }

        .auth-shell--refined .auth-brand__bottom {
            position: relative;
            z-index: 2;
            width: min(100%, 520px);
            padding-top: 18px;
            color: rgba(255, 255, 255, .62);
            border-top-color: rgba(255, 255, 255, .16);
        }

        .auth-shell--refined .auth-panel {
            position: relative;
            min-height: 100dvh;
            padding: clamp(42px, 7vw, 96px);
            background: #FFFFFF;
        }

        .auth-shell--refined .auth-card {
            width: min(100%, 520px);
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .auth-portal-context {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 28px;
        }

        .auth-portal-context__icon {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 44px;
            height: 44px;
            padding: 7px;
            color: var(--ec-navy);
            border: 1px solid #E5B536;
            border-radius: 8px;
            background: #FFFDF6;
        }

        .auth-portal-context__icon img,
        .auth-portal-context__icon svg {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .auth-portal-context__label {
            display: block;
            color: var(--ec-navy);
            font-size: .92rem;
            font-weight: 730;
            line-height: 1.25;
        }

        .auth-portal-context__meta {
            display: block;
            margin-top: 3px;
            color: var(--ec-muted);
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .auth-shell--refined .auth-card__header {
            margin-bottom: 28px;
        }

        .auth-shell--refined .auth-title {
            color: var(--ec-navy);
            font-size: clamp(2rem, 3.4vw, 2.65rem);
            font-weight: 740;
            line-height: 1.08;
        }

        .auth-shell--refined .auth-subtitle {
            max-width: 470px;
            margin-top: 12px;
            font-size: .96rem;
            line-height: 1.55;
        }

        .auth-shell--refined .ec-form-group {
            margin-bottom: 18px;
        }

        .auth-shell--refined .ec-label {
            margin-bottom: 8px;
            color: var(--ec-navy);
            font-size: .84rem;
            font-weight: 650;
        }

        .auth-shell--refined .ec-input {
            min-height: 54px;
            padding-right: 16px;
            padding-left: 16px;
            border-color: #C9D3DF;
            border-radius: 7px;
            font-size: .95rem;
        }

        .auth-shell--refined .ec-input:focus {
            border-color: var(--ec-gold);
            box-shadow: 0 0 0 4px rgba(215, 154, 33, .16);
        }

        .auth-shell--refined .ec-remember {
            margin: 1px 0 20px;
            color: #475467;
            font-size: .84rem;
        }

        .auth-shell--refined .ec-remember input {
            accent-color: var(--ec-gold);
        }

        .auth-shell--refined .ec-btn {
            min-height: 54px;
            border-radius: 7px;
            color: var(--ec-navy);
            background: var(--ec-gold);
            font-size: .95rem;
            font-weight: 750;
        }

        .auth-shell--refined .ec-btn:hover {
            box-shadow: 0 12px 24px rgba(215, 154, 33, .24);
            filter: brightness(1.03);
        }

        .auth-portal-switcher {
            margin-top: 26px;
            padding-top: 22px;
            border-top: 1px solid var(--ec-border-soft);
        }

        .auth-portal-switcher__title {
            margin: 0 0 10px;
            color: var(--ec-muted);
            font-size: .73rem;
            font-weight: 700;
        }

        .auth-portal-switcher__links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .auth-portal-switcher__link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-height: 42px;
            padding: 9px 10px;
            color: var(--ec-navy);
            border-bottom: 1px solid var(--ec-border);
            font-size: .78rem;
            font-weight: 670;
            text-decoration: none;
        }

        .auth-portal-switcher__link svg {
            flex: 0 0 auto;
            width: 14px;
            height: 14px;
            color: var(--ec-gold);
        }

        .auth-portal-switcher__link:hover {
            color: #102E62;
            border-bottom-color: var(--ec-gold);
        }

        .auth-register-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 16px;
            color: var(--ec-muted);
            font-size: .78rem;
        }

        .auth-register-link a {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            flex: 0 0 auto;
            color: var(--ec-navy);
            font-weight: 730;
            text-decoration: none;
        }

        .auth-register-link a:hover {
            color: #102E62;
            text-decoration: underline;
        }

        .auth-shell--refined .auth-footer {
            align-items: flex-start !important;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--ec-border-soft);
            text-align: left;
        }

        .auth-shell--refined .auth-footer__meta,
        .auth-shell--refined .auth-footer__contacts {
            justify-content: flex-start !important;
        }

        .auth-shell--refined .auth-footer__contacts a {
            font-size: .73rem;
        }

        @media (max-width: 1050px) {
            .auth-shell--refined {
                grid-template-columns: 1fr;
            }

            .auth-shell--refined .auth-brand {
                min-height: 220px;
                padding: 26px clamp(24px, 7vw, 54px);
                justify-content: flex-start;
                gap: 20px;
            }

            .auth-brand__identity img {
                width: 58px;
                height: 58px;
            }

            .auth-brand__wordmark {
                font-size: 1.55rem;
            }

            .auth-shell--refined .auth-brand__body {
                margin: 0;
            }

            .auth-brand__rule {
                width: 36px;
                height: 2px;
                margin-bottom: 12px;
            }

            .auth-shell--refined .auth-brand__title {
                max-width: 520px;
                font-size: 1.65rem;
            }

            .auth-shell--refined .auth-brand__lead,
            .auth-shell--refined .auth-brand__bottom {
                display: none;
            }

            .auth-brand__motif {
                right: -50px;
                bottom: -36px;
                width: 390px;
                opacity: .75;
            }

            .auth-shell--refined .auth-panel {
                min-height: auto;
                padding: 42px clamp(24px, 7vw, 54px) 34px;
            }
        }

        @media (max-width: 640px) {
            .auth-shell--refined .auth-brand {
                min-height: 190px;
                padding: 22px 20px;
            }

            .auth-brand__identity {
                flex-direction: row;
                align-items: center;
                gap: 12px;
            }

            .auth-brand__identity img {
                width: 50px;
                height: 50px;
            }

            .auth-brand__wordmark {
                font-size: 1.3rem;
            }

            .auth-shell--refined .auth-brand__title {
                font-size: 1.35rem;
                line-height: 1.2;
            }

            .auth-shell--refined .auth-panel {
                padding: 32px 20px 28px;
            }

            .auth-shell--refined .auth-title {
                font-size: 2rem;
            }

            .auth-shell--refined .auth-card__header {
                margin-bottom: 24px;
            }

            .auth-portal-switcher__links {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .auth-portal-switcher__link {
                min-height: 46px;
            }

            .auth-register-link {
                align-items: flex-start;
            }

            .auth-shell--refined .auth-footer,
            .auth-shell--refined .auth-footer__meta,
            .auth-shell--refined .auth-footer__contacts {
                align-items: flex-start !important;
                justify-content: flex-start !important;
            }

            .auth-shell--refined .auth-footer__contacts {
                gap: 8px 14px !important;
            }
        }
    </style>
    @stack('auth-styles')
</head>
<body class="ec-auth">
    @yield('auth-body')

    <script>
        (function () {
            'use strict';

            function initPasswordToggles() {
                document.querySelectorAll('[data-ec-eye]').forEach(function (button) {
                    var targetId = button.getAttribute('data-ec-eye');
                    var input = document.getElementById(targetId);

                    if (!input) {
                        return;
                    }

                    button.setAttribute('type', 'button');

                    button.addEventListener('click', function () {
                        var isVisible = input.type === 'text';
                        input.type = isVisible ? 'password' : 'text';
                        button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                        button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');

                        var icon = button.querySelector('.ec-eye-svg');
                        if (!icon) {
                            return;
                        }

                        icon.innerHTML = isVisible
                            ? '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/>'
                            : '<path d="M3 3l18 18"/><path d="M10.6 10.6A3 3 0 0 0 13.4 13.4"/><path d="M7.1 7.1C3.8 8.8 2 12 2 12s3.5 6 10 6c1.7 0 3.2-.4 4.4-1"/><path d="M14.1 6.3C19.2 7.1 22 12 22 12s-.8 1.4-2.2 2.8"/>';
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPasswordToggles);
            } else {
                initPasswordToggles();
            }
        }());
    </script>
    @stack('auth-scripts')
</body>
</html>
