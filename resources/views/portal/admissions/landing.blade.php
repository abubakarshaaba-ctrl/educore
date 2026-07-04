<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admissions – {{ $tenant->name }}</title>
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

:root {
    --navy:   #0B1D3A;
    --blue:   #1A56DB;
    --sky:    #3B82F6;
    --green:  #10B981;
    --amber:  #F59E0B;
    --red:    #EF4444;
    --slate:  #64748B;
    --border: #E2E8F0;
    --bg:     #F8FAFC;
    --white:  #FFFFFF;
}

body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: var(--bg);
    color: var(--navy);
    line-height: 1.6;
    min-height: 100vh;
}

/* ── TOP NAV ─────────────────────────────────────────── */
.topnav {
    position: sticky;
    top: 0;
    z-index: 50;
    background: rgba(11,29,58,0.97);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
}
.topnav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}
.topnav-logo {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: var(--blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 800;
    color: white;
    overflow: hidden;
    flex-shrink: 0;
}
.topnav-logo img { width:100%; height:100%; object-fit:cover; }
.topnav-name {
    font-size: 14px;
    font-weight: 700;
    color: white;
    letter-spacing: -0.01em;
}
.topnav-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.nav-link {
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    padding: 6px 14px;
    border-radius: 8px;
    transition: all 150ms;
}
.nav-link:hover { color: white; background: rgba(255,255,255,0.08); }
.nav-btn {
    font-size: 13px;
    font-weight: 600;
    color: white;
    background: var(--blue);
    text-decoration: none;
    padding: 8px 18px;
    border-radius: 8px;
    transition: all 150ms;
}
.nav-btn:hover { background: #1946C0; }

/* ── HERO ─────────────────────────────────────────────── */
.hero {
    background: linear-gradient(160deg, var(--navy) 0%, #0F2952 60%, #1A3A6A 100%);
    color: white;
    padding: 72px 24px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    top: -80px; left: 50%;
    transform: translateX(-50%);
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(26,86,219,0.25) 0%, transparent 70%);
    pointer-events: none;
}
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 100px;
    padding: 5px 14px 5px 8px;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,0.85);
    margin-bottom: 28px;
    letter-spacing: 0.01em;
}
.hero-badge .pulse {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--green);
    position: relative;
}
.hero-badge .pulse::after {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    background: var(--green);
    opacity: 0.3;
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%,100% { transform:scale(1); opacity:0.3; }
    50%      { transform:scale(1.5); opacity:0; }
}
.hero-closed-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(239,68,68,0.15);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 100px;
    padding: 5px 14px 5px 10px;
    font-size: 12px;
    font-weight: 600;
    color: #FCA5A5;
    margin-bottom: 28px;
}
.hero h1 {
    font-size: clamp(28px, 5vw, 48px);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
    margin-bottom: 16px;
}
.hero h1 span {
    background: linear-gradient(90deg, #60A5FA, #93C5FD);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero-sub {
    font-size: 17px;
    color: rgba(255,255,255,0.65);
    max-width: 500px;
    margin: 0 auto 36px;
    font-weight: 400;
}
.hero-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 56px;
}
.btn-hero {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    font-size: 15px;
    font-weight: 700;
    border-radius: 12px;
    text-decoration: none;
    transition: all 200ms;
    font-family: inherit;
    border: none;
    cursor: pointer;
}
.btn-hero-primary {
    background: var(--blue);
    color: white;
    box-shadow: 0 4px 20px rgba(26,86,219,0.5);
}
.btn-hero-primary:hover {
    background: #1946C0;
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(26,86,219,0.55);
}
.btn-hero-ghost {
    background: rgba(255,255,255,0.1);
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
}
.btn-hero-ghost:hover {
    background: rgba(255,255,255,0.16);
}
.hero-deadline {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}
.hero-deadline strong { color: rgba(255,255,255,0.8); }

/* ── STATS BAR ─────────────────────────────────────────── */
.stats-bar {
    background: white;
    border-bottom: 1px solid var(--border);
    padding: 20px 24px;
}
.stats-inner {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 48px;
    flex-wrap: wrap;
}
.stat {
    text-align: center;
}
.stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--navy);
    letter-spacing: -0.03em;
    line-height: 1;
}
.stat-value.blue  { color: var(--blue); }
.stat-value.green { color: var(--green); }
.stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--slate);
    margin-top: 4px;
}
.stat-divider {
    width: 1px;
    height: 36px;
    background: var(--border);
}

/* ── WELCOME MESSAGE ────────────────────────────────────── */
.welcome-section {
    max-width: 720px;
    margin: 40px auto;
    padding: 0 24px;
}
.welcome-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px 32px;
    border-left: 4px solid var(--blue);
}
.welcome-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--blue);
    margin-bottom: 10px;
}
.welcome-text {
    font-size: 15px;
    color: #334155;
    line-height: 1.75;
    white-space: pre-line;
}

/* ── HOW IT WORKS ─────────────────────────────────────── */
.section {
    max-width: 900px;
    margin: 0 auto;
    padding: 48px 24px;
}
.section-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--blue);
    margin-bottom: 8px;
    text-align: center;
}
.section-title {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.02em;
    text-align: center;
    color: var(--navy);
    margin-bottom: 40px;
}
.steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}
.step-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px 24px;
    position: relative;
    transition: box-shadow 200ms, transform 200ms;
}
.step-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    transform: translateY(-3px);
}
.step-num {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #EFF6FF;
    color: var(--blue);
    font-size: 16px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.step-icon {
    font-size: 24px;
    margin-bottom: 14px;
    display: block;
}
.step-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 8px;
}
.step-desc {
    font-size: 13px;
    color: var(--slate);
    line-height: 1.6;
}

/* ── REQUIREMENTS ────────────────────────────────────────── */
.req-section {
    background: white;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.req-inner {
    max-width: 900px;
    margin: 0 auto;
    padding: 48px 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: start;
}
.req-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 20px;
}
.req-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.req-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
    color: #334155;
}
.req-check {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #ECFDF5;
    color: var(--green);
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.contact-card {
    background: #F8FAFC;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 24px;
}
.contact-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 16px;
}
.contact-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #334155;
    margin-bottom: 10px;
}
.contact-icon {
    font-size: 16px;
    width: 22px;
    text-align: center;
    flex-shrink: 0;
}

/* ── CTA BANNER ──────────────────────────────────────────── */
.cta-section {
    max-width: 900px;
    margin: 48px auto;
    padding: 0 24px;
}
.cta-card {
    background: linear-gradient(135deg, var(--navy) 0%, #1A3A6A 100%);
    border-radius: 20px;
    padding: 48px 40px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
}
.cta-card::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 200px; height: 200px;
    background: rgba(26,86,219,0.3);
    border-radius: 50%;
    pointer-events: none;
}
.cta-card h2 {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 10px;
}
.cta-card p {
    font-size: 15px;
    color: rgba(255,255,255,0.65);
    margin-bottom: 28px;
}
.cta-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

/* ── CLOSED STATE ────────────────────────────────────────── */
.closed-section {
    max-width: 600px;
    margin: 60px auto;
    padding: 0 24px;
    text-align: center;
}
.closed-icon {
    width: 72px;
    height: 72px;
    background: #FEF2F2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin: 0 auto 20px;
}
.closed-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 10px;
}
.closed-text {
    font-size: 15px;
    color: var(--slate);
    margin-bottom: 24px;
    line-height: 1.7;
}
.btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: all 150ms;
    border: 1.5px solid var(--blue);
    color: var(--blue);
    background: white;
}
.btn-outline:hover { background: #EFF6FF; }

/* ── FOOTER ─────────────────────────────────────────────── */
.portal-footer {
    background: var(--navy);
    color: rgba(255,255,255,0.45);
    text-align: center;
    padding: 28px 24px;
    font-size: 12px;
    margin-top: 0;
}
.portal-footer a { color: rgba(255,255,255,0.6); text-decoration: none; }
.portal-footer .powered { margin-top: 6px; font-size: 11px; opacity: 0.4; }

/* ── RESPONSIVE ─────────────────────────────────────────── */
@media (max-width: 680px) {
    .hero { padding: 52px 20px 60px; }
    .stats-inner { gap: 24px; }
    .stat-divider { display: none; }
    .req-inner { grid-template-columns: 1fr; gap: 28px; }
    .cta-card { padding: 36px 24px; }
    .cta-card h2 { font-size: 22px; }
    .topnav-actions .nav-link { display: none; }
}
</style>
</head>
<body>

{{-- ── TOP NAV ─────────────────────────────────────────── --}}
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
    <div class="topnav-actions">
        <a href="{{ route('tenant.portal.landing', $tenant->slug) }}" class="nav-link">School Portal</a>
        <a href="{{ route('tenant.login', $tenant->slug) }}" class="nav-link">Staff Login</a>
        <a href="{{ route('portal.status.form', $tenant->slug) }}" class="nav-link">Check Status</a>
        @if($settings->isCurrentlyOpen())
            <a href="{{ route('portal.form', $tenant->slug) }}" class="nav-btn">Apply Now →</a>
        @endif
    </div>
</nav>

{{-- ── HERO ─────────────────────────────────────────────── --}}
<section class="hero">
    @if($settings->isCurrentlyOpen())
        <div class="hero-badge">
            <span class="pulse"></span>
            Admissions Open
            @if($settings->academic_year)
                &nbsp;·&nbsp; {{ $settings->academic_year }} Academic Year
            @endif
        </div>
    @else
        <div class="hero-closed-badge">
            &#9940; Admissions Currently Closed
        </div>
    @endif

    <h1>
        Apply to<br>
        <span>{{ $tenant->name }}</span>
    </h1>

    <p class="hero-sub">
        @if($settings->welcome_message && strlen($settings->welcome_message) < 120)
            {{ $settings->welcome_message }}
        @else
            Complete your child's admission application online in minutes. Get instant confirmation by SMS.
        @endif
    </p>

    @if($settings->isCurrentlyOpen())
        <div class="hero-actions">
            <a href="{{ route('portal.form', $tenant->slug) }}" class="btn-hero btn-hero-primary">
                ✏️ Start Application
            </a>
            <a href="{{ route('portal.status.form', $tenant->slug) }}" class="btn-hero btn-hero-ghost">
                🔍 Track My Application
            </a>
        </div>
        @if($settings->closes_on)
            <div class="hero-deadline">
                ⏰ Application deadline:
                <strong>{{ $settings->closes_on->format('d F Y') }}</strong>
            </div>
        @endif
    @else
        <div class="hero-actions">
            <a href="{{ route('portal.status.form', $tenant->slug) }}" class="btn-hero btn-hero-ghost">
                🔍 Check Existing Application
            </a>
        </div>
        @if($settings->opens_on)
            <div class="hero-deadline">
                📅 Portal opens:
                <strong>{{ $settings->opens_on->format('d F Y') }}</strong>
            </div>
        @endif
    @endif
</section>

{{-- ── STATS BAR ─────────────────────────────────────────── --}}
@if($stats['total_applied'] || $stats['admitted'] || $tenant->phone)
<div class="stats-bar">
    <div class="stats-inner">
        @if($stats['total_applied'])
            <div class="stat">
                <div class="stat-value blue">{{ number_format($stats['total_applied']) }}</div>
                <div class="stat-label">Applied This Year</div>
            </div>
            <div class="stat-divider"></div>
        @endif
        @if($stats['admitted'])
            <div class="stat">
                <div class="stat-value green">{{ number_format($stats['admitted']) }}</div>
                <div class="stat-label">Admitted</div>
            </div>
            <div class="stat-divider"></div>
        @endif
        @if($tenant->phone)
            <div class="stat">
                <div class="stat-value" style="font-size:16px;letter-spacing:0">{{ $tenant->phone }}</div>
                <div class="stat-label">Contact Phone</div>
            </div>
        @endif
        @if($tenant->address)
            @if($tenant->phone)<div class="stat-divider"></div>@endif
            <div class="stat">
                <div class="stat-value" style="font-size:13px;font-weight:600;letter-spacing:0;color:var(--slate)">{{ $tenant->address }}</div>
                <div class="stat-label">Address</div>
            </div>
        @endif
    </div>
</div>
@endif

@if($settings->isCurrentlyOpen())

{{-- ── HOW IT WORKS ─────────────────────────────────────── --}}
<div class="section">
    <div class="section-label">Application Process</div>
    <div class="section-title">How to Apply in 3 Steps</div>
    <div class="steps">
        <div class="step-card">
            <div class="step-num">1</div>
            <div class="step-title">Fill the Form</div>
            <p class="step-desc">Provide your ward's personal details, previous school, and parent/guardian information. Takes about 5 minutes.</p>
        </div>
        <div class="step-card">
            <div class="step-num">2</div>
            <div class="step-title">Upload Documents</div>
            <p class="step-desc">
                @php
                    $docs = [];
                    if($settings->require_passport) $docs[] = 'passport photograph';
                    if($settings->require_birth_cert) $docs[] = 'birth certificate';
                    if($settings->require_report_card) $docs[] = 'last report card';
                @endphp
                @if(count($docs))
                    Attach your ward's {{ implode(', ', $docs) }} as clear image or PDF files.
                @else
                    Attach any supporting documents such as previous school reports or immunisation records.
                @endif
            </p>
        </div>
        <div class="step-card">
            <div class="step-num">3</div>
            <div class="step-title">Get Confirmation</div>
            <p class="step-desc">Receive your unique application number by SMS instantly. Use it to track your application status at any time.</p>
        </div>
    </div>
</div>

{{-- ── REQUIREMENTS & CONTACT ────────────────────────────── --}}
<div class="req-section">
    <div class="req-inner">
        <div>
            <h3 class="req-title">What You Need</h3>
            <ul class="req-list">
                <li class="req-item">
                    <span class="req-check">✓</span>
                    Ward's full name, date of birth and home address
                </li>
                <li class="req-item">
                    <span class="req-check">✓</span>
                    Parent or guardian phone number (for SMS confirmation)
                </li>
                @if($settings->require_passport)
                <li class="req-item">
                    <span class="req-check">✓</span>
                    Recent passport photograph (JPG or PNG, max 2MB)
                </li>
                @endif
                @if($settings->require_birth_cert)
                <li class="req-item">
                    <span class="req-check">✓</span>
                    Birth certificate (PDF or image, max 4MB)
                </li>
                @endif
                @if($settings->require_report_card)
                <li class="req-item">
                    <span class="req-check">✓</span>
                    Most recent school report card
                </li>
                @endif
                @if($settings->application_fee > 0)
                <li class="req-item">
                    <span class="req-check" style="background:#FFFBEB;color:#D97706">₦</span>
                    Application fee: <strong>₦{{ number_format($settings->application_fee) }}</strong>
                    (payment details provided after submission)
                </li>
                @endif
                @if($settings->requirements)
                <li class="req-item">
                    <span class="req-check">✓</span>
                    {{ $settings->requirements }}
                </li>
                @endif
            </ul>
        </div>
        <div>
            <div class="contact-card">
                <div class="contact-title">📞 Need Help?</div>
                @if($tenant->phone)
                <div class="contact-row">
                    <span class="contact-icon">📱</span>
                    <div><strong>Call or WhatsApp</strong><br>{{ $tenant->phone }}</div>
                </div>
                @endif
                @if($tenant->email)
                <div class="contact-row">
                    <span class="contact-icon">✉️</span>
                    <div><strong>Email</strong><br>{{ $tenant->email }}</div>
                </div>
                @endif
                @if($tenant->address)
                <div class="contact-row">
                    <span class="contact-icon">📍</span>
                    <div><strong>Visit Us</strong><br>{{ $tenant->address }}</div>
                </div>
                @endif
                @if($settings->footer_note)
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);font-size:12px;color:var(--slate);line-height:1.6">
                    {{ $settings->footer_note }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── CTA BANNER ────────────────────────────────────────── --}}
<div class="cta-section">
    <div class="cta-card">
        <h2>Ready to secure a place for your child?</h2>
        <p>Applications are open now. The process takes just a few minutes.</p>
        <div class="cta-actions">
            <a href="{{ route('portal.form', $tenant->slug) }}" class="btn-hero btn-hero-primary">
                ✏️ Apply Now — It's Free
            </a>
            <a href="{{ route('portal.status.form', $tenant->slug) }}" class="btn-hero btn-hero-ghost">
                🔍 Check Application Status
            </a>
        </div>
    </div>
</div>

@else

{{-- ── CLOSED STATE ──────────────────────────────────────── --}}
<div class="closed-section">
    <div class="closed-icon">🔒</div>
    <h2 class="closed-title">Admissions Are Closed</h2>
    <p class="closed-text">
        We are not currently accepting new applications.
        @if($settings->opens_on)
            The portal will reopen on <strong>{{ $settings->opens_on->format('d F Y') }}</strong>.
        @else
            Please check back later or contact the school for more information.
        @endif
        @if($tenant->phone)
            <br><br>📞 Call us: <strong>{{ $tenant->phone }}</strong>
        @endif
    </p>
    <a href="{{ route('portal.status.form', $tenant->slug) }}" class="btn-outline">
        🔍 Check an Existing Application
    </a>
</div>

@endif

{{-- ── FOOTER ─────────────────────────────────────────────── --}}
<footer class="portal-footer">
    <div>
        &copy; {{ date('Y') }} {{ $tenant->name }}
        @if($tenant->address) &nbsp;·&nbsp; {{ $tenant->address }} @endif
        @if($tenant->email) &nbsp;·&nbsp; <a href="mailto:{{ $tenant->email }}">{{ $tenant->email }}</a> @endif
    </div>
    <div class="powered">Powered by Enterprise School Management System</div>
</footer>

</body>
</html>
