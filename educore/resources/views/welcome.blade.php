<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EduCore is a complete school management platform for Nigerian K-12 schools — admissions, academics, attendance, fees, payroll, staff HR, exams and parent communication in one system. Free for up to 20 students.">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#071E45">
<meta name="author" content="EduCore">
<link rel="canonical" href="https://educoreng.online/">
<title>EduCore — School Management Platform for Nigerian Schools</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900" rel="stylesheet">

{{-- Open Graph / Facebook, WhatsApp, LinkedIn link previews --}}
<meta property="og:type" content="website">
<meta property="og:url" content="https://educoreng.online/">
<meta property="og:site_name" content="EduCore">
<meta property="og:locale" content="en_NG">
<meta property="og:title" content="EduCore — School Management Platform for Nigerian Schools">
<meta property="og:description" content="Admissions, academics, attendance, fees, payroll, staff HR, exams and parent communication — unified in one platform built for Nigerian K-12 schools. Free for up to 20 students.">
<meta property="og:image" content="https://educoreng.online/brand/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="EduCore — School Management Platform">

{{-- Twitter/X Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="EduCore — School Management Platform for Nigerian Schools">
<meta name="twitter:description" content="Admissions, academics, fees, payroll and staff HR — unified in one platform built for Nigerian K-12 schools.">
<meta name="twitter:image" content="https://educoreng.online/brand/og-image.png">

{{-- Structured data for Google rich results --}}
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "SoftwareApplication",
  "name": "EduCore",
  "applicationCategory": "EducationalApplication",
  "operatingSystem": "Web, Android, iOS",
  "url": "https://educoreng.online/",
  "description": "Complete school management platform for Nigerian K-12 schools covering admissions, academics, attendance, fees, payroll, staff HR, exams and parent communication.",
  "offers": {
    "@@type": "Offer",
    "price": "0",
    "priceCurrency": "NGN",
    "description": "Free for up to 20 students. Paid tiers scale per student per term."
  },
  "provider": {
    "@@type": "Organization",
    "name": "EduCore",
    "url": "https://educoreng.online/",
    "email": "support@educoreng.online",
    "telephone": "+2347065595768"
  }
}
</script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#071E45;--navy-dark:#040f25;--navy-mid:#0d2a5e;
  --gold:#D79A21;--gold-light:#F2C35B;--gold-pale:#FEF9EC;
  --white:#FFFFFF;--off:#F7F9FC;--slate:#475569;--muted:#94A3B8;--border:#E2E8F0;
  --r:14px;--r-sm:10px;--r-xs:8px;
  --sh:0 4px 24px rgba(7,30,69,.10);--sh-lg:0 16px 64px rgba(7,30,69,.16);
  --font:'Plus Jakarta Sans',system-ui,sans-serif;
}
body{font-family:var(--font);color:var(--navy);background:var(--white);line-height:1.6;overflow-x:hidden}

/* NAV */
.nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:0 5vw;height:68px;display:flex;align-items:center;justify-content:space-between;gap:24px;background:rgba(7,30,69,.96);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,.07)}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
.nav-brand img{width:34px;height:34px;border-radius:8px}
.nav-brand-name{font-size:17px;font-weight:800;color:#fff;letter-spacing:-.02em}
.nav-brand-name span{color:var(--gold)}
.nav-links{display:flex;align-items:center;gap:6px}
.nav-links a{color:rgba(255,255,255,.75);font-size:13px;font-weight:500;padding:7px 13px;border-radius:8px;text-decoration:none;transition:all 150ms}
.nav-links a:hover{color:#fff;background:rgba(255,255,255,.08)}
.nav-cta{display:flex;align-items:center;gap:10px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:var(--r-xs);font-size:13px;font-weight:700;text-decoration:none;border:none;cursor:pointer;font-family:inherit;transition:all 180ms;white-space:nowrap}
.btn-gold{background:var(--gold);color:var(--navy)}.btn-gold:hover{background:var(--gold-light);transform:translateY(-1px)}
.btn-outline{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.3)}.btn-outline:hover{background:rgba(255,255,255,.08)}
.btn-navy{background:var(--navy);color:#fff}.btn-navy:hover{background:var(--navy-mid)}
.btn-white{background:#fff;color:var(--navy)}.btn-white:hover{background:var(--off)}
.nav-toggle{display:none;align-items:center;justify-content:center;width:38px;height:38px;border:none;background:rgba(255,255,255,.1);border-radius:8px;cursor:pointer}
.nav-toggle svg{width:20px;height:20px;fill:white}
.nav-mobile{display:none;position:fixed;top:68px;left:0;right:0;background:var(--navy-dark);border-top:1px solid rgba(255,255,255,.08);padding:16px 5vw 24px;flex-direction:column;gap:4px;z-index:99}
.nav-mobile.open{display:flex}
.nav-mobile a{color:rgba(255,255,255,.8);font-size:14px;padding:11px 14px;border-radius:8px;text-decoration:none}
.nav-mobile a:hover{background:rgba(255,255,255,.07)}
.nm-cta{margin-top:12px;display:flex;flex-direction:column;gap:8px}

/* HERO */
.hero{min-height:100vh;background:linear-gradient(145deg,var(--navy-dark) 0%,#081b3d 40%,#0e2650 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:100px 5vw 80px;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 30%,rgba(215,154,33,.12) 0%,transparent 70%);pointer-events:none}
.hero-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:48px 48px;pointer-events:none}
.hero-pill{display:inline-flex;align-items:center;gap:8px;background:rgba(215,154,33,.15);border:1px solid rgba(215,154,33,.3);color:var(--gold-light);font-size:12px;font-weight:600;letter-spacing:.04em;padding:6px 16px;border-radius:50px;margin-bottom:28px;text-transform:uppercase}
.hero h1{font-size:clamp(36px,6vw,76px);font-weight:900;color:#fff;line-height:1.05;letter-spacing:-.03em;margin-bottom:24px;max-width:900px}
.hero h1 span{color:var(--gold)}
.hero-sub{font-size:clamp(15px,2vw,20px);color:rgba(255,255,255,.65);max-width:600px;margin:0 auto 40px;line-height:1.7}
.hero-actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:64px}
.btn-lg{padding:14px 28px;font-size:15px;border-radius:12px}
.hero-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:680px;width:100%;border-top:1px solid rgba(255,255,255,.08);padding-top:48px}
.hnum{font-size:clamp(28px,4vw,42px);font-weight:900;color:var(--gold-light);line-height:1}
.hlabel{font-size:12px;color:rgba(255,255,255,.5);margin-top:4px;font-weight:500}

/* SECTIONS */
section{padding:100px 5vw}
.slabel{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--gold);margin-bottom:14px}
.stitle{font-size:clamp(28px,3.5vw,46px);font-weight:800;color:var(--navy);line-height:1.15;letter-spacing:-.02em;margin-bottom:16px}
.ssub{font-size:17px;color:var(--slate);max-width:540px;line-height:1.7}
.tc{text-align:center}.tc .ssub{margin:0 auto}

/* FEATURES BENTO */
.features{background:var(--off)}
.bento{display:grid;grid-template-columns:repeat(12,1fr);gap:16px;margin-top:56px}
.bc{border-radius:var(--r);background:#fff;border:1px solid var(--border);padding:28px;overflow:hidden;box-shadow:var(--sh);transition:transform 200ms,box-shadow 200ms}
.bc:hover{transform:translateY(-3px);box-shadow:var(--sh-lg)}
.b-1{grid-column:span 8}.b-2{grid-column:span 4}
.b-3{grid-column:span 4}.b-4{grid-column:span 4}.b-5{grid-column:span 4}
.b-6{grid-column:span 6}.b-7{grid-column:span 6}
.bc-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:16px;flex-shrink:0}
.bc-gold{background:var(--gold-pale)}.bc-navy{background:rgba(7,30,69,.06)}.bc-green{background:#ECFDF5}
.bc-purple{background:#F5F3FF}.bc-blue{background:#EFF6FF}
.bc h3{font-size:17px;font-weight:800;color:var(--navy);margin-bottom:8px}
.bc p{font-size:13.5px;color:var(--slate);line-height:1.6}
.feat-list{list-style:none;margin-top:16px;display:flex;flex-direction:column;gap:8px}
.feat-list li{font-size:13px;color:var(--slate);display:flex;align-items:center;gap:8px}
.feat-list li::before{content:'✓';color:#059669;font-weight:800;flex-shrink:0}
.bnum{font-size:52px;font-weight:900;color:var(--navy);line-height:1;margin-top:12px}
.bnum span{color:var(--gold)}
.fpreview{margin-top:20px;background:var(--off);border:1px solid var(--border);border-radius:10px;overflow:hidden}
.fp-head{padding:12px;border-bottom:1px solid var(--border);font-size:12px;font-weight:700;color:var(--navy)}
.fp-row{display:flex;gap:8px;padding:10px 12px;border-top:1px solid var(--border)}
.fp-dot{width:8px;height:8px;border-radius:50%;background:var(--gold);flex-shrink:0;margin-top:3px}
.fp-line{height:10px;border-radius:4px;background:var(--border)}

/* FULL FEATURE LIST */
.flist-head{margin-top:72px;margin-bottom:8px}
.flist{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:32px}
.flist-cat{background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:22px}
.flist-cat h4{font-size:13px;font-weight:800;color:var(--navy);text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.flist-cat h4::before{content:'';width:8px;height:8px;border-radius:2px;background:var(--gold)}
.flist-cat ul{list-style:none;display:flex;flex-direction:column;gap:7px}
.flist-cat ul li{font-size:12.5px;color:var(--slate);display:flex;align-items:flex-start;gap:7px;line-height:1.5}
.flist-cat ul li::before{content:'✓';color:#059669;font-weight:800;flex-shrink:0}

/* STEPS */
.how{background:#fff}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-top:56px}
.step{text-align:center;padding:32px 20px;border-radius:var(--r);border:1px solid var(--border);background:#fff}
.step-num{width:52px;height:52px;border-radius:50%;background:var(--navy);color:#fff;font-size:18px;font-weight:900;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;border:3px solid var(--gold)}
.step h3{font-size:16px;font-weight:700;margin-bottom:8px}
.step p{font-size:13px;color:var(--slate)}

/* PORTALS */
.portals{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);color:#fff}
.portals .stitle{color:#fff}
.portals .ssub{color:rgba(255,255,255,.65)}
.pgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-top:56px}
.pcard{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:var(--r);padding:28px;text-decoration:none;transition:all 200ms;display:block}
.pcard:hover{background:rgba(255,255,255,.1);border-color:rgba(215,154,33,.4);transform:translateY(-2px)}
.pcard-icon{font-size:28px;margin-bottom:14px}
.pcard h3{font-size:16px;font-weight:700;color:#fff;margin-bottom:6px}
.pcard p{font-size:13px;color:rgba(255,255,255,.6);line-height:1.6}
.pcard-arr{margin-top:16px;font-size:18px;color:var(--gold)}

/* TESTIMONIALS */
.testimonials{background:#fff}
.tgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:56px}
.tcard{background:var(--off);border:1px solid var(--border);border-radius:var(--r);padding:26px}
.tcard-stars{color:var(--gold);font-size:14px;margin-bottom:12px}
.tcard-text{font-size:14px;color:var(--navy);line-height:1.7;font-style:italic;margin-bottom:18px}
.tcard-author{display:flex;align-items:center;gap:10px}
.tcard-av{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0}
.tcard-name{font-size:13px;font-weight:700;color:var(--navy)}
.tcard-school{font-size:11px;color:var(--muted)}

/* PRICING */
.pricing{background:var(--off)}
.tier-table{max-width:760px;margin:56px auto 0;background:#fff;border:1.5px solid var(--border);border-radius:var(--r);overflow:hidden}
.tier-row{display:grid;grid-template-columns:2fr 1.3fr 1fr;gap:12px;align-items:center;padding:20px 28px;border-bottom:1px solid var(--border)}
.tier-row:last-child{border-bottom:none}
.tier-row.tier-free{background:#F0FDF4}
.tier-range{font-size:15px;font-weight:700;color:var(--navy)}
.tier-rate{font-size:16px;font-weight:800;color:var(--navy)}
.tier-cycle{font-size:12px;color:var(--muted);text-align:right}
.pricing-note{max-width:700px;margin:24px auto 0;text-align:center;font-size:13px;color:var(--muted)}
.pgrid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-top:56px}
.prc{background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:28px;display:flex;flex-direction:column;transition:all 200ms;position:relative}
.prc:hover{box-shadow:var(--sh-lg);transform:translateY(-3px)}
.prc.popular{border-color:var(--gold)}
.pop-badge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--gold);color:var(--navy);font-size:11px;font-weight:800;padding:4px 14px;border-radius:50px;white-space:nowrap}
.pname{font-size:15px;font-weight:700;margin-bottom:4px}
.pdesc{font-size:12px;color:var(--muted);margin-bottom:20px}
.pamount{font-size:32px;font-weight:900}
.pamount sup{font-size:16px;vertical-align:top;margin-top:6px}
.pamount small{font-size:12px;font-weight:500;color:var(--muted)}
hr.pdiv{border:none;border-top:1px solid var(--border);margin:20px 0}
.pfeats{list-style:none;display:flex;flex-direction:column;gap:9px;flex:1}
.pfeats li{font-size:13px;display:flex;align-items:center;gap:8px}
.pfeats li::before{content:'✓';color:#059669;font-weight:800;flex-shrink:0}
.pcta{margin-top:22px}
.pbtn{width:100%;padding:11px;background:var(--navy);color:#fff;border:none;border-radius:var(--r-xs);font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;text-align:center;text-decoration:none;display:block;transition:background 150ms}
.pbtn:hover{background:var(--navy-mid)}
.prc.popular .pbtn{background:var(--gold);color:var(--navy)}
.prc.popular .pbtn:hover{background:var(--gold-light)}

/* CTA */
.cta-b{background:linear-gradient(135deg,var(--gold) 0%,#c48b1a 100%);padding:80px 5vw;text-align:center}
.cta-b h2{font-size:clamp(26px,3.5vw,46px);font-weight:900;color:var(--navy);margin-bottom:12px;letter-spacing:-.02em}
.cta-b p{font-size:17px;color:rgba(7,30,69,.75);margin-bottom:36px}
.cta-acts{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}

/* FOOTER */
footer{background:var(--navy-dark);color:rgba(255,255,255,.65);padding:64px 5vw 32px}
.fg{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:48px}
.fb p{font-size:13px;margin-top:12px;line-height:1.7}
.fc h4{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#fff;margin-bottom:14px}
.fc a{display:block;font-size:13px;color:rgba(255,255,255,.55);text-decoration:none;padding:3px 0;transition:color 150ms}
.fc a:hover{color:var(--gold)}
.fb2{border-top:1px solid rgba(255,255,255,.08);padding-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.fb2 p{font-size:12px}
.fb2-links{display:flex;gap:20px}
.fb2-links a{font-size:12px;color:rgba(255,255,255,.45);text-decoration:none}
.fb2-links a:hover{color:var(--gold)}
.fcontact{display:flex;flex-direction:column;gap:10px;margin-top:16px}
.fcontact-row{display:flex;align-items:center;gap:10px}
.fcontact-row svg{width:16px;height:16px;flex-shrink:0;color:var(--gold)}
.fcontact a{font-size:12.5px;color:rgba(255,255,255,.7);text-decoration:none;font-weight:600}
.fcontact a:hover{color:var(--gold)}

/* RESPONSIVE */
@media(max-width:1024px){
  .b-1{grid-column:span 12}.b-2{grid-column:span 12}
  .b-3,.b-4,.b-5{grid-column:span 4}
  .b-6,.b-7{grid-column:span 12}
  .fg{grid-template-columns:1fr 1fr}
}
@media(max-width:768px){
  section{padding:72px 5vw}
  .nav-links,.nav-cta{display:none}
  .nav-toggle{display:flex}
  .b-3,.b-4,.b-5{grid-column:span 12}
  .bento{gap:12px}
  .fg{grid-template-columns:1fr}
}
@media(max-width:480px){
  .hero-stats{grid-template-columns:1fr;gap:16px;padding-top:36px}
  .hero-actions{flex-direction:column;align-items:center}
  .btn-lg{width:100%;max-width:340px;justify-content:center}
  .fb2{flex-direction:column;text-align:center}
}
</style>
</head>
<body>

<nav class="nav">
    <a href="{{ route('home') }}" class="nav-brand">
        <img src="/brand/educore-icon.svg" alt="EduCore">
        <span class="nav-brand-name">Edu<span>Core</span></span>
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#portals">Portals</a>
        <a href="#pricing">Pricing</a>
        <a href="#testimonials">Reviews</a>
    </div>
    <div class="nav-cta">
        <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}" class="btn btn-outline">Login</a>
        <a href="{{ route('school.register') }}" class="btn btn-gold">Get Started &rarr;</a>
    </div>
    <button class="nav-toggle" onclick="document.getElementById('nm').classList.toggle('open')" aria-label="Menu">
        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>
</nav>
<div class="nav-mobile" id="nm">
    <a href="#features" onclick="document.getElementById('nm').classList.remove('open')">Features</a>
    <a href="#portals" onclick="document.getElementById('nm').classList.remove('open')">Portals</a>
    <a href="#pricing" onclick="document.getElementById('nm').classList.remove('open')">Pricing</a>
    <a href="#testimonials" onclick="document.getElementById('nm').classList.remove('open')">Reviews</a>
    <div class="nm-cta">
        <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}" class="btn btn-outline" style="justify-content:center">Login</a>
        <a href="{{ route('school.register') }}" class="btn btn-gold" style="justify-content:center">Get Started</a>
    </div>
</div>

<section class="hero">
    <div class="hero-grid"></div>
    <div class="hero-pill">&starf; Built for Nigerian K-12 Schools</div>
    <h1>Run Your School<br><span>Smarter. Faster.</span><br>Better.</h1>
    <p class="hero-sub">EduCore is the all-in-one school management platform &mdash; admissions, academics, fees, payroll, portals, and everything in between. One system, zero chaos.</p>
    <div class="hero-actions">
        <a href="{{ route('school.register') }}" class="btn btn-gold btn-lg">Start Free Trial &rarr;</a>
        <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}" class="btn btn-outline btn-lg">School Login</a>
        <a href="{{ route('app.download') }}" class="btn btn-outline btn-lg">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
            Download Staff App
        </a>
    </div>
    <div class="hero-stats">
        <div><div class="hnum">500+</div><div class="hlabel">Schools Active</div></div>
        <div><div class="hnum">47</div><div class="hlabel">Modules Built</div></div>
        <div><div class="hnum">99.9%</div><div class="hlabel">Uptime SLA</div></div>
    </div>
</section>

<section class="features" id="features">
    <div class="slabel">Everything you need</div>
    <h2 class="stitle">One platform,<br>every operation</h2>
    <p class="ssub">From first admission to final results &mdash; EduCore handles every workflow your school runs on.</p>
    <div class="bento">
        <div class="bc b-1">
            <div style="display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap">
                <div style="flex:1;min-width:200px">
                    <div class="bc-icon bc-navy">&#128218;</div>
                    <h3>Academic Management</h3>
                    <p>Full curriculum from class structure to report cards. Sessions, terms, timetables, CBT exams, and broadsheet &mdash; all in one place.</p>
                    <ul class="feat-list">
                        <li>Academic sessions &amp; terms with activation control</li>
                        <li>Timetable generation with conflict detection</li>
                        <li>Score entry with assessment type config</li>
                        <li>Automated broadsheet &amp; report card publishing</li>
                        <li>CBT engine with objective &amp; essay sections</li>
                        <li>Promotion engine with configurable rules</li>
                    </ul>
                </div>
                <div class="fpreview" style="flex:1;min-width:200px">
                    <div class="fp-head">&#128202; Broadsheet &mdash; Basic 7A &middot; First Term</div>
                    <div class="fp-row"><div class="fp-dot"></div><div class="fp-line" style="flex:1"></div><div style="width:32px;height:10px;background:#ECFDF5;border-radius:4px"></div></div>
                    <div class="fp-row"><div class="fp-dot" style="background:var(--border)"></div><div class="fp-line" style="flex:1;width:80%"></div><div style="width:32px;height:10px;background:#ECFDF5;border-radius:4px"></div></div>
                    <div class="fp-row"><div class="fp-dot"></div><div class="fp-line" style="flex:1;width:50%"></div><div style="width:32px;height:10px;background:#FEF2F2;border-radius:4px"></div></div>
                </div>
            </div>
        </div>
        <div class="bc b-2">
            <div class="bc-icon bc-gold">&#127891;</div>
            <h3>Student Management</h3>
            <p>Admissions, profiles, class transfers, archive, and the full lifecycle from enrollment to graduation.</p>
            <div class="bnum">15<span>k+</span></div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px">Students managed</div>
        </div>
        <div class="bc b-3">
            <div class="bc-icon bc-blue">&#128105;&#8205;&#128188;</div>
            <h3>Staff &amp; Payroll</h3>
            <p>Staff records, PAYE payroll, salary templates, deductions, and secure bank detail management.</p>
        </div>
        <div class="bc b-4">
            <div class="bc-icon bc-green">&#128179;</div>
            <h3>Fee Management</h3>
            <p>Invoice generation, Monnify &amp; Paystack, payment tracking, overdue reports, and reminders.</p>
        </div>
        <div class="bc b-5">
            <div class="bc-icon bc-purple">&#128203;</div>
            <h3>Attendance</h3>
            <p>Daily student attendance, QR staff clock-in, proxy requests, and monthly attendance reports.</p>
        </div>
        <div class="bc b-6">
            <div class="bc-icon bc-navy">&#127962;</div>
            <h3>Multi-School Platform</h3>
            <p>Each school has its own isolated environment, branded subdomain, and independent data. One platform &mdash; unlimited schools.</p>
            <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
                @foreach(['Isolated data','Per-school branding','Custom domain','White-label','Role-based access'] as $t)
                <span style="padding:4px 10px;background:rgba(7,30,69,.06);border-radius:20px;font-size:11px;font-weight:600">{{ $t }}</span>
                @endforeach
            </div>
        </div>
        <div class="bc b-7">
            <div class="bc-icon bc-gold">&#127760;</div>
            <h3>Parent &amp; Student Portals</h3>
            <p>Real-time access to results, attendance, fees, timetable, and messages &mdash; from any device, anywhere.</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:16px">
                <div style="padding:10px;background:var(--off);border-radius:8px;font-size:12px;font-weight:600">&#128241; Mobile-first</div>
                <div style="padding:10px;background:var(--off);border-radius:8px;font-size:12px;font-weight:600">&#128276; Notifications</div>
                <div style="padding:10px;background:var(--off);border-radius:8px;font-size:12px;font-weight:600">&#128184; Online payment</div>
                <div style="padding:10px;background:var(--off);border-radius:8px;font-size:12px;font-weight:600">&#128202; Live results</div>
            </div>
        </div>
    </div>

    <div class="tc flist-head">
        <div class="slabel">Every module, in full</div>
        <h2 class="stitle">The complete feature list</h2>
        <p class="ssub">Everything above, broken down module by module.</p>
    </div>
    <div class="flist">
        <div class="flist-cat">
            <h4>Academics</h4>
            <ul>
                <li>Academic sessions &amp; terms with activation control</li>
                <li>Class levels, arms &amp; form tutor assignment</li>
                <li>Promotion engine with configurable rules</li>
                <li>Curriculum tracks &amp; subject management</li>
                <li>Timetable builder with conflict detection</li>
                <li>AI-assisted lesson planner</li>
                <li>Student &amp; staff attendance (QR clock-in)</li>
                <li>Skill ratings &amp; behavioural assessment</li>
            </ul>
        </div>
        <div class="flist-cat">
            <h4>Assessments &amp; Results</h4>
            <ul>
                <li>Score entry with configurable assessment types</li>
                <li>Automated broadsheet &amp; report card publishing</li>
                <li>Transcript generation</li>
                <li>CBT engine &mdash; objective &amp; essay sections</li>
                <li>CBT LAN mode for offline exam halls</li>
                <li>Exam timetables &amp; supervision duty rosters</li>
            </ul>
        </div>
        <div class="flist-cat">
            <h4>Admissions</h4>
            <ul>
                <li>Online application portal</li>
                <li>Application review &amp; status tracking</li>
                <li>Student transfers between schools</li>
                <li>Class transfers &amp; enrollment lifecycle</li>
            </ul>
        </div>
        <div class="flist-cat">
            <h4>Finance &amp; Payroll</h4>
            <ul>
                <li>Fee structures, categories &amp; sub-accounts</li>
                <li>Invoice generation &amp; bulk billing</li>
                <li>Online payments &mdash; Paystack &amp; Monnify</li>
                <li>Payment plans &amp; instalments</li>
                <li>Automated fee reminders</li>
                <li>Staff payroll, PAYE &amp; salary templates</li>
                <li>Expense tracking</li>
            </ul>
        </div>
        <div class="flist-cat">
            <h4>Operations</h4>
            <ul>
                <li>Health records</li>
                <li>Library management</li>
                <li>Transport routes</li>
                <li>School announcements &amp; calendar</li>
                <li>In-app messaging (staff, parent &amp; student threads)</li>
                <li>SMS campaigns &amp; automated notification triggers</li>
            </ul>
        </div>
        <div class="flist-cat">
            <h4>Reporting &amp; Analytics</h4>
            <ul>
                <li>School-wide analytics dashboard</li>
                <li>Financial reports</li>
                <li>Academic risk flags</li>
                <li>Data export tools</li>
                <li>Nigerian School Census (ASC) reporting</li>
            </ul>
        </div>
        <div class="flist-cat">
            <h4>Portals &amp; Access</h4>
            <ul>
                <li>Dedicated admin, staff, parent &amp; student portals</li>
                <li>Role-based access control</li>
                <li>Agent/referral portal with commission tracking</li>
                <li>Native mobile app for staff</li>
            </ul>
        </div>
        <div class="flist-cat">
            <h4>Platform</h4>
            <ul>
                <li>Multi-school, multi-tenant architecture</li>
                <li>Per-school branding &amp; custom subdomain</li>
                <li>Isolated data per school</li>
                <li>Subscription &amp; billing management</li>
            </ul>
        </div>
    </div>
</section>

<section class="how">
    <div class="tc">
        <div class="slabel">Simple by design</div>
        <h2 class="stitle">Up and running in minutes</h2>
        <p class="ssub">No IT department required. We set up your school &mdash; you run it.</p>
    </div>
    <div class="steps">
        <div class="step"><div class="step-num">1</div><h3>Register your school</h3><p>Provide your school details and branding. We activate your account within 24 hours.</p></div>
        <div class="step"><div class="step-num">2</div><h3>Configure academics</h3><p>Set up classes, subjects, sessions, and grading. Takes under 30 minutes.</p></div>
        <div class="step"><div class="step-num">3</div><h3>Add staff &amp; students</h3><p>Import via CSV or add individually. Assign roles and grant portal access instantly.</p></div>
        <div class="step"><div class="step-num">4</div><h3>Go live</h3><p>Attendance, scores, fee collection, and portals &mdash; all working from day one.</p></div>
    </div>
</section>

<section class="portals" id="portals">
    <div class="tc">
        <div class="slabel" style="color:var(--gold-light)">Access for everyone</div>
        <h2 class="stitle">The right view for every stakeholder</h2>
        <p class="ssub">Different roles, tailored portals &mdash; each designed for exactly who uses it.</p>
    </div>
    <div class="pgrid">
        <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}" class="pcard">
            <div class="pcard-icon">&#127979;</div>
            <h3>School Admin</h3>
            <p>Complete school operations &mdash; students, staff, fees, reports, settings, and analytics.</p>
            <div class="pcard-arr">&rarr;</div>
        </a>
        <a href="{{ Route::has('student.login') ? route('student.login') : '#' }}" class="pcard">
            <div class="pcard-icon">&#127891;</div>
            <h3>Student Portal</h3>
            <p>Results, timetable, CBT exams, attendance, and fee statements on any device.</p>
            <div class="pcard-arr">&rarr;</div>
        </a>
        <a href="{{ Route::has('parent.login') ? route('parent.login') : '#' }}" class="pcard">
            <div class="pcard-icon">&#128106;</div>
            <h3>Parent Portal</h3>
            <p>Track attendance, view results, pay fees, and receive notifications for your children.</p>
            <div class="pcard-arr">&rarr;</div>
        </a>
        <a href="{{ Route::has('agent.portal.login') ? route('agent.portal.login') : '#' }}" class="pcard">
            <div class="pcard-icon">&#129309;</div>
            <h3>Agent Portal</h3>
            <p>Refer schools, track commissions, and manage your school partnership portfolio.</p>
            <div class="pcard-arr">&rarr;</div>
        </a>
    </div>
</section>

<section class="testimonials" id="testimonials">
    <div class="tc">
        <div class="slabel">Trusted by schools</div>
        <h2 class="stitle">What school admins say</h2>
    </div>
    <div class="tgrid">
        <div class="tcard">
            <div class="tcard-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p class="tcard-text">&ldquo;EduCore replaced four separate tools we were using. Payroll, attendance, fees, and report cards all in one place. Our staff learned it in a day.&rdquo;</p>
            <div class="tcard-author"><div class="tcard-av" style="background:#D79A21;color:#071E45">AB</div><div><div class="tcard-name">Amaka Briggs</div><div class="tcard-school">Principal, Sunrise Academy</div></div></div>
        </div>
        <div class="tcard">
            <div class="tcard-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p class="tcard-text">&ldquo;Parents now see their children&rsquo;s results online the same day we enter scores. The parent portal eliminated 80% of our result inquiry calls.&rdquo;</p>
            <div class="tcard-author"><div class="tcard-av" style="background:#071E45">EM</div><div><div class="tcard-name">Emmanuel Musa</div><div class="tcard-school">Director, Grace Schools</div></div></div>
        </div>
        <div class="tcard">
            <div class="tcard-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p class="tcard-text">&ldquo;Online fee payment through Monnify changed everything. Parents pay from anywhere. Our collection rate jumped to 94% this term.&rdquo;</p>
            <div class="tcard-author"><div class="tcard-av" style="background:#059669">FO</div><div><div class="tcard-name">Fatima Obi</div><div class="tcard-school">Bursar, Heritage College</div></div></div>
        </div>
    </div>
</section>

<section class="pricing" id="pricing">
    <div class="tc">
        <div class="slabel">Plans &amp; Pricing</div>
        <h2 class="stitle">One plan. Every feature. Priced by enrollment.</h2>
        <p class="ssub">No feature tiers, no add-on packages — every school gets the complete EduCore platform. You only pay for the size of school you run today.</p>
    </div>
    <div class="tier-table">
        @foreach($tiers as $tier)
        <div class="tier-row {{ $loop->first ? 'tier-free' : '' }}">
            <div class="tier-range">{{ $tier['range'] }}</div>
            <div class="tier-rate">{{ $tier['rate'] }}</div>
            <div class="tier-cycle">{{ $tier['cycle'] }}</div>
        </div>
        @endforeach
    </div>
    <p class="pricing-note">Billed per academic term to match how Nigerian schools collect fees — pay a full year (3 terms) upfront and save 10%. Schools above 500 students get a tailored volume quote.</p>
    <div class="cta-acts" style="margin-top:32px">
        <a href="{{ route('school.register') }}" class="btn btn-gold btn-lg">Start Free Trial &rarr;</a>
    </div>
</section>

<section class="cta-b">
    <h2>Ready to transform your school?</h2>
    <p>Join hundreds of Nigerian schools already running on EduCore.</p>
    <div class="cta-acts">
        <a href="{{ route('school.register') }}" class="btn btn-navy btn-lg">Start Today &rarr;</a>
    </div>
</section>

<footer>
    <div class="fg">
        <div class="fb">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
                <img src="/brand/educore-icon.svg" alt="EduCore" style="width:32px;height:32px;border-radius:7px">
                <span style="font-size:17px;font-weight:800;color:white">Edu<span style="color:var(--gold)">Core</span></span>
            </div>
            <p>The complete school management platform built for Nigerian K-12 institutions.</p>
            <div class="fcontact">
                <div class="fcontact-row">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1.1.5 1.1 1.1V20c0 .6-.5 1.1-1.1 1.1C10.9 21.1 2.9 13.1 2.9 3.2c0-.6.5-1.1 1.1-1.1h3.5c.6 0 1.1.5 1.1 1.1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
                    <a href="tel:+2347065595768">07065595768</a>
                </div>
                <div class="fcontact-row">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.2-1.3c1.5.8 3.1 1.3 4.8 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18.1c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3.1.8.8-3-.2-.3c-.9-1.4-1.3-2.9-1.3-4.5 0-4.5 3.7-8.2 8.2-8.2s8.2 3.7 8.2 8.2-3.7 8.2-8.2 8.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8.9-.1.2-.3.2-.6.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5.1-.1.2-.3.4-.4.1-.1.2-.2.2-.4.1-.1 0-.3 0-.4-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.4 3.8 3.4.5.2.9.4 1.3.5.5.2 1 .1 1.4.1.4-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.2-.4-.3z"/></svg>
                    <a href="https://wa.me/2347065595768" target="_blank" rel="noopener">WhatsApp: +2347065595768</a>
                </div>
                <div class="fcontact-row">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <a href="mailto:support@educoreng.online">support@educoreng.online</a>
                </div>
            </div>
        </div>
        <div class="fc"><h4>Product</h4><a href="#features">Features</a><a href="#pricing">Pricing</a><a href="#portals">Portals</a><a href="#testimonials">Reviews</a></div>
        <div class="fc">
            <h4>Portals</h4>
            <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">School Admin</a>
            <a href="{{ Route::has('student.login') ? route('student.login') : '#' }}">Student</a>
            <a href="{{ Route::has('parent.login') ? route('parent.login') : '#' }}">Parent</a>
            <a href="{{ Route::has('agent.portal.login') ? route('agent.portal.login') : '#' }}">Agent</a>
        </div>
        <div class="fc"><h4>Company</h4><a href="mailto:support@educoreng.online">Contact</a><a href="mailto:support@educoreng.online">Support</a><a href="{{ route('legal.privacy') }}">Privacy Policy</a><a href="{{ route('legal.terms') }}">Terms</a></div>
    </div>
    <div class="fb2">
        <p><span style="color:#fff;font-weight:700">Edu<span style="color:var(--gold)">Core</span></span> Education Technology &copy; {{ date('Y') }}. All rights reserved.</p>
        <div class="fb2-links"><a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('legal.terms') }}">Terms</a><a href="mailto:support@educoreng.online">Contact</a></div>
    </div>
</footer>
</body>
</html>