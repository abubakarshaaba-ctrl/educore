<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EduCore connects academics, attendance, finance, staff, results and communication in one school management platform built for Nigerian schools.">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#071a38">
<link rel="canonical" href="https://educoreng.online/">
<title>EduCore — Smarter School Management</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900" rel="stylesheet">
<meta property="og:type" content="website">
<meta property="og:url" content="https://educoreng.online/">
<meta property="og:site_name" content="EduCore">
<meta property="og:title" content="EduCore — Smarter School Management">
<meta property="og:description" content="One connected platform for academics, attendance, finance, staff, results and communication.">
<meta property="og:image" content="https://educoreng.online/brand/og-image.png">
<meta name="twitter:card" content="summary_large_image">
<style>
:root{
  --navy:#071a38;--navy-2:#0c2a59;--navy-3:#143e7a;--gold:#e0aa33;--gold-2:#f2c95e;
  --ink:#13233e;--muted:#69768a;--soft:#f5f7fb;--line:#e4e9f0;--white:#fff;
  --shadow:0 18px 50px rgba(8,27,57,.12);--radius:18px;--font:'Plus Jakarta Sans',system-ui,sans-serif
}
*{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:72px}
body{margin:0;font-family:var(--font);color:var(--ink);background:#fff;overflow-x:hidden}
a{text-decoration:none;color:inherit}
button{font:inherit}
img{max-width:100%;display:block}
.container{width:min(1180px,calc(100% - 48px));margin:0 auto}
.btn{min-height:44px;padding:0 19px;border:1px solid transparent;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:13px;font-weight:800;line-height:1;transition:.2s ease}
.btn:hover{transform:translateY(-1px)}
.btn-gold{background:linear-gradient(180deg,var(--gold-2),var(--gold));color:var(--navy);box-shadow:0 10px 26px rgba(224,170,51,.22)}
.btn-outline-light{border-color:rgba(255,255,255,.5);color:#fff;background:transparent}
.btn-outline{border-color:#cfd7e2;color:var(--navy);background:#fff}
.eyebrow,.kicker{font-size:10px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}
.eyebrow{color:var(--gold-2)}
.kicker{color:#9a6a08}
.icon{width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center}
.icon svg{width:100%;height:100%;stroke:currentColor}

.nav{height:72px;position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #e7ebf1;box-shadow:0 2px 14px rgba(7,26,56,.04)}
.navrow{height:100%;display:flex;align-items:center;gap:28px}
.brand{display:flex;align-items:center;gap:10px;flex:none}
.brand img{width:38px;height:38px}
.brandname{font-size:21px;font-weight:900;letter-spacing:-.04em;color:var(--navy)}
.brandname span{color:var(--gold)}
.links{display:flex;align-items:center;gap:23px;margin-left:auto;font-size:12px;font-weight:700;color:var(--navy)}
.actions{display:flex;gap:8px}
.menu{display:none;margin-left:auto;width:40px;height:40px;border:1px solid #ccd5e1;border-radius:10px;background:#fff;color:var(--navy);font-size:20px}
.drawer{display:none;position:fixed;top:72px;left:0;right:0;z-index:49;background:#fff;border-bottom:1px solid var(--line);box-shadow:0 20px 40px rgba(7,26,56,.14);padding:14px 18px 18px}
.drawer.open{display:grid}
.drawer>a{padding:11px 8px;font-size:13px;font-weight:700;color:var(--navy)}
.drawer-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:9px}

.hero{background:var(--navy);color:#fff;overflow:hidden}
.hero-grid{display:grid;grid-template-columns:minmax(0,1.02fr) minmax(0,.98fr);min-height:510px}
.hero-copy{padding:74px 54px 64px 0;display:flex;flex-direction:column;justify-content:center}
.hero h1{margin:12px 0 18px;font-size:clamp(46px,4.9vw,68px);line-height:.98;letter-spacing:-.055em;font-weight:900;color:#fff;max-width:650px}
.hero h1 span{color:var(--gold-2)}
.hero p{margin:0;max-width:560px;color:#cbd6e5;font-size:14px;line-height:1.75}
.hero-buttons{display:flex;gap:10px;margin-top:24px}
.hero-proof{display:flex;gap:18px;flex-wrap:wrap;margin-top:21px;color:#aebed2;font-size:9px;font-weight:800}
.hero-proof span:before{content:"✓";color:var(--gold-2);margin-right:6px}
.hero-visual{position:relative;min-height:510px;background:#d7e1eb}
.hero-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(7,26,56,.08),rgba(7,26,56,.02))}
.hero-badge{position:absolute;left:24px;bottom:24px;max-width:260px;background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-radius:14px;padding:13px 15px;color:var(--navy);box-shadow:var(--shadow)}
.hero-badge strong{display:block;font-size:12px}
.hero-badge span{display:block;margin-top:3px;font-size:9px;line-height:1.45;color:#68778b}

.role-strip{background:#fff;border-bottom:1px solid var(--line)}
.role-row{min-height:76px;display:grid;grid-template-columns:1.45fr repeat(6,1fr);gap:10px;align-items:center}
.role-lead{font-size:11px;font-weight:900;color:var(--navy)}
.role-pill{display:flex;align-items:center;gap:8px;font-size:9px;font-weight:700;color:#637187}
.role-pill .role-icon{width:28px;height:28px;border-radius:9px;background:#fff5dc;color:#9a6900;display:grid;place-items:center;flex:none}
.role-pill .role-icon svg{width:14px;height:14px;stroke:currentColor}

.section{padding:66px 0}
.soft{background:var(--soft)}
.section-head{max-width:720px;margin:0 auto 30px;text-align:center}
.section-head h2{margin:9px 0 10px;font-size:clamp(30px,3.6vw,46px);line-height:1.08;letter-spacing:-.045em;color:var(--navy)}
.section-head p{margin:0;color:var(--muted);font-size:12px;line-height:1.7}
.module-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.module{border:1px solid var(--line);border-radius:14px;background:#fff;padding:18px;min-height:148px}
.module-icon{width:38px;height:38px;border-radius:11px;background:#fff3d2;color:#986500;display:grid;place-items:center;margin-bottom:13px}
.module-icon svg{width:18px;height:18px;stroke:currentColor}
.module h3{margin:0 0 6px;font-size:13px;line-height:1.3;color:var(--navy)}
.module p{margin:0;color:var(--muted);font-size:9px;line-height:1.55}

.empower{padding:68px 0;background:#fff}
.empower-grid{display:grid;grid-template-columns:1.02fr .98fr;gap:46px;align-items:center}
.empower-photo-wrap{position:relative;border-radius:20px;overflow:hidden;min-height:380px;box-shadow:var(--shadow)}
.empower-photo{width:100%;height:100%;min-height:380px;object-fit:cover}
.empower-card{position:absolute;left:18px;bottom:18px;right:18px;background:rgba(7,26,56,.92);color:#fff;border-radius:14px;padding:16px 18px}
.empower-card strong{display:block;color:var(--gold-2);font-size:13px}
.empower-card span{display:block;margin-top:4px;color:#c7d3e2;font-size:9px;line-height:1.5}
.empower-copy h2{margin:10px 0 14px;font-size:42px;line-height:1.08;letter-spacing:-.045em;color:var(--navy)}
.empower-copy p{margin:0 0 18px;color:var(--muted);font-size:12px;line-height:1.75}
.trust-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.trust-item{display:flex;align-items:flex-start;gap:9px;padding:12px;border:1px solid var(--line);border-radius:12px;background:#fff}
.trust-item b{display:block;font-size:11px;color:var(--navy)}
.trust-item small{display:block;margin-top:3px;font-size:8px;line-height:1.45;color:var(--muted)}
.trust-item .tick{width:26px;height:26px;border-radius:8px;background:#fff3d2;color:#9a6900;display:grid;place-items:center;flex:none;font-weight:900}

.app{background:linear-gradient(145deg,#04142f,var(--navy-2));color:#fff;overflow:hidden}
.app-grid{display:grid;grid-template-columns:1fr .92fr;gap:54px;align-items:center}
.app-copy h2{margin:10px 0 14px;font-size:44px;line-height:1.07;letter-spacing:-.045em;color:#fff}
.app-copy p{margin:0;color:#c4d0df;font-size:12px;line-height:1.75;max-width:560px}
.app-list{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:22px 0}
.app-list span{font-size:10px;font-weight:700;color:#dbe5f1}
.app-list span:before{content:"✓";color:var(--gold-2);margin-right:7px}
.phone-stage{min-height:430px;display:grid;place-items:center;position:relative}
.phone-stage:before{content:"";position:absolute;width:340px;height:340px;border-radius:50%;background:rgba(224,170,51,.9);right:-80px;bottom:-140px}
.phone{position:relative;width:220px;height:410px;border:7px solid #161d28;border-radius:34px;background:#f6f8fb;overflow:hidden;box-shadow:0 32px 70px rgba(0,0,0,.35);transform:rotate(2deg);z-index:1}
.phone-top{background:linear-gradient(150deg,var(--navy),var(--navy-3));padding:29px 16px 19px;color:#fff}
.phone-top img{width:28px;height:28px}
.phone-top small{display:block;margin-top:14px;font-size:7px;color:#bdcada}
.phone-top b{font-size:13px}
.phone-body{padding:11px}
.phone-card{background:#fff;border:1px solid #e2e8ef;border-radius:10px;padding:12px;margin-bottom:8px}
.phone-card b{display:block;font-size:8px;color:var(--navy)}
.phone-card small{font-size:6px;color:#7f8b9b}

.pricing-wrap{display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--line);border-radius:18px;overflow:hidden;background:#fff}
.price-dark{background:var(--navy);color:#fff;padding:34px}
.price-dark h2{margin:8px 0 12px;font-size:31px;line-height:1.1}
.price-dark p{margin:0;color:#bdc9d9;font-size:10px;line-height:1.7}
.price-card{padding:34px}
.price-card small{font-size:8px;font-weight:900;color:#758297;text-transform:uppercase}
.free{font-size:42px;font-weight:900;letter-spacing:-.05em;color:var(--navy)}
.divider{height:1px;background:var(--line);margin:18px 0}
.paid{font-size:30px;font-weight:900;color:var(--navy)}
.price-card p{font-size:10px;color:var(--muted)}

.cta{padding:0 0 66px}
.cta-box{background:linear-gradient(120deg,var(--navy),#104584);border-radius:18px;padding:31px 34px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:24px}
.cta-box h2{margin:0;font-size:29px;line-height:1.15}
.cta-box p{margin:6px 0 0;color:#bdc9d9;font-size:10px}
.cta-actions{display:flex;gap:8px;flex:none}

footer{background:#fff;border-top:1px solid var(--line);color:#7a8799;padding:34px 0 22px}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:32px}
.footer-about p{max-width:320px;font-size:9px;line-height:1.6}
.footer-grid h4{margin:0 0 10px;color:var(--navy);font-size:8px;text-transform:uppercase;letter-spacing:.1em}
.footer-grid a{display:block;padding:4px 0;font-size:9px}
.contact{margin-top:10px}
.contact a{font-weight:700}
.copyright{border-top:1px solid var(--line);margin-top:26px;padding-top:16px;font-size:8px}

@media(max-width:1100px){
  .container{width:min(1180px,calc(100% - 36px))}
  .links{gap:16px}
  .hero h1{font-size:54px}
  .module-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
  .role-row{grid-template-columns:1.2fr repeat(6,1fr)}
}
@media(max-width:900px){
  .links,.actions{display:none}
  .menu{display:block}
  .hero-grid{grid-template-columns:1fr 1fr;min-height:470px}
  .hero-copy{padding:56px 34px 54px 0}
  .hero h1{font-size:46px}
  .hero-visual{min-height:470px}
  .role-row{grid-template-columns:repeat(3,1fr);padding:16px 0}
  .role-lead{grid-column:1/-1;text-align:center}
  .module-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .empower-grid,.app-grid{grid-template-columns:1fr}
  .empower-copy{text-align:center}
  .trust-grid{max-width:720px;margin:0 auto}
  .app-copy{text-align:center}
  .app-copy p{margin:0 auto}
  .app-list{max-width:560px;margin:22px auto}
  .app-copy .btn{margin:auto}
  .phone-stage{min-height:400px}
  .footer-grid{grid-template-columns:1fr 1fr}
  .footer-about{grid-column:1/-1}
}
@media(max-width:620px){
  html{scroll-padding-top:64px}
  .container{width:calc(100% - 28px)}
  .nav{height:64px}
  .drawer{top:64px}
  .brand img{width:32px;height:32px}
  .brandname{font-size:18px}
  .menu{width:38px;height:38px}
  .hero-grid{display:flex;flex-direction:column;min-height:0}
  .hero-copy{order:1;padding:34px 0 26px}
  .hero .eyebrow{font-size:8px}
  .hero h1{font-size:39px;line-height:1.01;margin:10px 0 13px;max-width:340px}
  .hero h1 span{display:block}
  .hero p{font-size:12px;line-height:1.6;max-width:355px}
  .hero-buttons{margin-top:18px;gap:8px}
  .hero-buttons .btn{flex:1;min-width:0;padding:0 13px}
  .hero-proof{gap:10px;margin-top:15px;font-size:7px}
  .hero-visual{order:2;min-height:245px;margin:0 -14px}
  .hero-photo{object-position:center 38%}
  .hero-badge{left:16px;bottom:16px;max-width:220px;padding:10px 12px}
  .hero-badge strong{font-size:10px}
  .hero-badge span{font-size:7.5px}
  .role-row{grid-template-columns:repeat(3,1fr);gap:9px;padding:14px 0}
  .role-pill{justify-content:center;flex-direction:column;text-align:center;gap:5px;font-size:8px}
  .section{padding:50px 0}
  .section-head{margin-bottom:24px}
  .section-head h2{font-size:28px}
  .section-head p{font-size:10.5px}
  .module-grid{grid-template-columns:1fr 1fr;gap:10px}
  .module{min-height:142px;padding:14px}
  .module-icon{width:34px;height:34px;margin-bottom:10px}
  .module h3{font-size:11.5px}
  .module p{font-size:8px}
  .empower{padding:50px 0}
  .empower-grid{gap:28px}
  .empower-photo-wrap,.empower-photo{min-height:300px}
  .empower-copy h2{font-size:29px}
  .empower-copy p{font-size:10.5px}
  .trust-grid{grid-template-columns:1fr 1fr}
  .trust-item{padding:10px}
  .trust-item b{font-size:10px}
  .trust-item small{font-size:7.5px}
  .app-grid{gap:20px}
  .app-copy h2{font-size:30px}
  .app-copy p{font-size:10.5px}
  .app-list{grid-template-columns:1fr 1fr}
  .phone-stage{min-height:360px}
  .phone{width:190px;height:355px}
  .pricing-wrap{grid-template-columns:1fr}
  .price-dark,.price-card{padding:25px 20px}
  .cta{padding-bottom:50px}
  .cta-box{display:block;padding:24px 20px}
  .cta-box h2{font-size:25px}
  .cta-actions{display:grid;grid-template-columns:1fr 1fr;margin-top:17px}
  .footer-grid{grid-template-columns:1fr 1fr;gap:22px}
}
@media(max-width:390px){
  .hero h1{font-size:35px}
  .hero-buttons{display:grid;grid-template-columns:1fr 1fr}
  .module{padding:12px;min-height:138px}
  .module h3{font-size:11px}
  .trust-grid{grid-template-columns:1fr}
}
@media(max-width:360px){
  .container{width:calc(100% - 24px)}
  .hero h1{font-size:33px}
  .hero p{font-size:11px}
  .role-row{grid-template-columns:repeat(2,1fr)}
  .role-lead{grid-column:1/-1}
  .module-grid{grid-template-columns:1fr}
  .module{min-height:0}
  .app-list{grid-template-columns:1fr}
  .cta-actions{grid-template-columns:1fr}
}
@media(prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}.btn{transition:none}
}
</style>
</head>
<body>
<nav class="nav">
  <div class="container navrow">
    <a class="brand" href="{{ route('home') }}">
      <img src="/brand/educore-icon.svg" alt="EduCore">
      <div class="brandname">Edu<span>Core</span></div>
    </a>
    <div class="links">
      <a href="#features">Features</a>
      <a href="#portals">Portals</a>
      <a href="#pricing">Pricing</a>
      <a href="#mobile">Mobile App / Download</a>
      <a href="mailto:support@educoreng.online">Contact</a>
    </div>
    <div class="actions">
      <a class="btn btn-outline" href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">Login</a>
      <a class="btn btn-gold" href="{{ route('school.register') }}">Start Free</a>
    </div>
    <button class="menu" id="menu" aria-label="Open menu" aria-expanded="false" aria-controls="drawer">☰</button>
  </div>
</nav>

<div class="drawer" id="drawer" aria-label="Mobile navigation">
  <a href="#features">Features</a>
  <a href="#portals">Portals</a>
  <a href="#pricing">Pricing</a>
  <a href="#mobile">Mobile App</a>
  <a href="mailto:support@educoreng.online">Contact</a>
  <div class="drawer-actions">
    <a class="btn btn-outline" href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">Login</a>
    <a class="btn btn-gold" href="{{ route('school.register') }}">Start Free</a>
  </div>
</div>

<main>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <div class="eyebrow">Education management, reimagined.</div>
      <h1>Smarter Schools.<br><span>Brighter Futures.</span></h1>
      <p>EduCore gives schools one connected platform for academics, attendance, finance, staff, communication and role-specific access.</p>
      <div class="hero-buttons">
        <a class="btn btn-gold" href="{{ route('school.register') }}">Start Free</a>
        <a class="btn btn-outline-light" href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">Login</a>
      </div>
      <div class="hero-proof"><span>Secure & reliable</span><span>Role-based access</span><span>Built for Nigerian schools</span></div>
    </div>
    <div class="hero-visual">
      <img class="hero-photo" src="https://images.pexels.com/photos/34162714/pexels-photo-34162714.jpeg?auto=compress&cs=tinysrgb&w=1600" alt="African secondary school students studying together in a classroom">
      <div class="hero-overlay"></div>
      <div class="hero-badge">
        <strong>Built around real school workflows</strong>
        <span>Administration, teaching, finance, parent access and student services in one coordinated system.</span>
      </div>
    </div>
  </div>
</section>

<div class="role-strip" id="portals">
  <div class="container role-row">
    <div class="role-lead">One platform. Every school role.</div>
    <div class="role-pill"><span class="role-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"/></svg></span><span>School Admins</span></div>
    <div class="role-pill"><span class="role-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 19.5V6.8a2 2 0 0 1 2-2h12v13H6a2 2 0 0 0-2 1.7Zm0 0a2 2 0 0 0 2 2h12"/></svg></span><span>Teachers</span></div>
    <div class="role-pill"><span class="role-icon"><svg viewBox="0 0 24 24" fill="none"><path d="m3 10 9-5 9 5-9 5-9-5Zm4 3v4c3 2 7 2 10 0v-4"/></svg></span><span>Students</span></div>
    <div class="role-pill"><span class="role-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s8-4 8-11a4 4 0 0 0-7-2.6A4 4 0 0 0 6 10c0 7 6 11 6 11Z"/></svg></span><span>Parents</span></div>
    <div class="role-pill"><span class="role-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16v10H4zM8 11h8M8 14h5"/></svg></span><span>Accountants</span></div>
    <div class="role-pill"><span class="role-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Zm-3 9 2 2 4-4"/></svg></span><span>Officers</span></div>
  </div>
</div>

<section class="section" id="features">
  <div class="container">
    <div class="section-head">
      <div class="kicker">Core modules</div>
      <h2>Everything your school needs, in one connected system.</h2>
      <p>Compact, focused tools for the people who run, teach, support and follow the school.</p>
    </div>
    <div class="module-grid">
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3h10v18H7zM4 7h3m10 0h3M10 8h4M10 12h4M10 16h4"/></svg></div><h3>Admissions & Student Records</h3><p>Applications, enrolment, student profiles, transfers, promotion and graduation records.</p></article>
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4zM8 9h8M8 13h4"/></svg></div><h3>Academics & Examinations</h3><p>Subjects, score entry, exams, report cards, broadsheets and academic records.</p></article>
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M5 4v16M19 4v16M5 8h14M8 12h3M13 12h3M8 16h3"/></svg></div><h3>Attendance & Timetable</h3><p>Student and staff attendance, daily schedules, work hours and timetables.</p></article>
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h3"/></svg></div><h3>Fees, Billing & Payroll</h3><p>Invoices, balances, collections, expenses and staff payroll workflows.</p></article>
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M8 7a4 4 0 1 0 8 0 4 4 0 0 0-8 0ZM4 21c0-4 3-6 8-6s8 2 8 6"/></svg></div><h3>Staff Management</h3><p>Staff records, permissions, assignments, attendance and lifecycle management.</p></article>
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg></div><h3>Parent & Student Portal</h3><p>Role-specific access to results, fees, attendance, timetables and updates.</p></article>
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div><h3>Reports & Analytics</h3><p>Operational, academic, attendance and finance visibility for school leadership.</p></article>
      <article class="module"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M10 18h4"/></svg></div><h3>Mobile App</h3><p>Native Android workflows for attendance, scores, notifications and daily operations.</p></article>
    </div>
  </div>
</section>

<section class="empower">
  <div class="container empower-grid">
    <div class="empower-photo-wrap">
      <img class="empower-photo" src="https://images.pexels.com/photos/6208709/pexels-photo-6208709.jpeg?auto=compress&cs=tinysrgb&w=1400" alt="Students collaborating during a practical science laboratory session">
      <div class="empower-card"><strong>Empowering great schools</strong><span>EduCore keeps everyday school work coordinated without burying users in unnecessary screens.</span></div>
    </div>
    <div class="empower-copy">
      <div class="kicker">Designed for real schools</div>
      <h2>Clear workflows. Better coordination. Stronger visibility.</h2>
      <p>EduCore combines the essential academic, administrative and operational processes schools already run, while keeping each user focused on the work that belongs to them.</p>
      <div class="trust-grid">
        <div class="trust-item"><span class="tick">✓</span><div><b>Secure & Reliable</b><small>Controlled access and structured school data.</small></div></div>
        <div class="trust-item"><span class="tick">✓</span><div><b>Regular Updates</b><small>Continuous product improvements and refinements.</small></div></div>
        <div class="trust-item"><span class="tick">✓</span><div><b>Role-Based Access</b><small>Focused workspaces based on assigned responsibilities.</small></div></div>
        <div class="trust-item"><span class="tick">✓</span><div><b>Built for Nigerian Schools</b><small>Designed around local school operations and workflows.</small></div></div>
      </div>
    </div>
  </div>
</section>

<section class="section app" id="mobile">
  <div class="container app-grid">
    <div class="app-copy">
      <div class="eyebrow">EduCore Mobile App</div>
      <h2>School operations, wherever work happens.</h2>
      <p>Use supported EduCore workflows from the native Android app with the same role-specific access model used on the web platform.</p>
      <div class="app-list"><span>Attendance</span><span>Score entry</span><span>Notifications</span><span>Staff clock-in</span><span>Role-specific dashboards</span><span>School workflows</span></div>
      <a class="btn btn-gold" href="{{ route('app.download') }}">Download Android App</a>
    </div>
    <div class="phone-stage">
      <div class="phone">
        <div class="phone-top"><img src="/brand/educore-icon.svg" alt=""><small>Good morning</small><b>Welcome to EduCore</b></div>
        <div class="phone-body">
          <div class="phone-card"><b>Attendance</b><small>Daily attendance workflows</small></div>
          <div class="phone-card"><b>Score Entry</b><small>Assigned academic tasks</small></div>
          <div class="phone-card"><b>Notifications</b><small>School communication</small></div>
          <div class="phone-card"><b>My Workspace</b><small>Role-specific tools</small></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section soft" id="pricing">
  <div class="container">
    <div class="section-head">
      <div class="kicker">Pricing</div>
      <h2>Start free. Scale when your school grows.</h2>
      <p>A simple model without splitting core school operations into confusing feature tiers.</p>
    </div>
    <div class="pricing-wrap">
      <div class="price-dark">
        <div class="eyebrow">Complete EduCore access</div>
        <h2>One platform. All core features.</h2>
        <p>Web and Android access, role-specific portals and EduCore's connected school-management modules.</p>
      </div>
      <div class="price-card">
        <small>Up to 50 students</small>
        <div class="free">Free</div>
        <div class="divider"></div>
        <small>Above 50 students</small>
        <div class="paid">₦300</div>
        <p>per student / term · all EduCore features included</p>
        <a class="btn btn-gold" style="width:100%;margin-top:14px" href="{{ route('school.register') }}">Create School Account</a>
      </div>
    </div>
  </div>
</section>

<section class="cta">
  <div class="container cta-box">
    <div>
      <h2>Build a smarter school with EduCore.</h2>
      <p>Start free or sign in to your existing school account.</p>
    </div>
    <div class="cta-actions">
      <a class="btn btn-gold" href="{{ route('school.register') }}">Start Free</a>
      <a class="btn btn-outline-light" href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">Login</a>
    </div>
  </div>
</section>
</main>

<footer>
  <div class="container">
    <div class="footer-grid">
      <div class="footer-about">
        <a class="brand" href="{{ route('home') }}"><img src="/brand/educore-icon.svg" alt="EduCore"><div class="brandname">Edu<span>Core</span></div></a>
        <p>Connected school management software built for Nigerian schools.</p>
        <div class="contact"><a href="tel:+2347065595768">07065595768</a><a href="https://wa.me/2347065595768">WhatsApp: +2347065595768</a><a href="mailto:support@educoreng.online">support@educoreng.online</a></div>
      </div>
      <div><h4>Product</h4><a href="#features">Features</a><a href="#pricing">Pricing</a><a href="{{ route('app.download') }}">Android App</a></div>
      <div><h4>Portals</h4><a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">School Admin</a><a href="{{ Route::has('student.login') ? route('student.login') : '#' }}">Student</a><a href="{{ Route::has('parent.login') ? route('parent.login') : '#' }}">Parent</a><a href="{{ Route::has('agent.portal.login') ? route('agent.portal.login') : '#' }}">Agent</a></div>
      <div><h4>Company</h4><a href="mailto:support@educoreng.online">Contact</a><a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('legal.terms') }}">Terms</a></div>
    </div>
    <div class="copyright">EduCore Education Technology © {{ date('Y') }}. All rights reserved.</div>
  </div>
</footer>

<script>
const b=document.getElementById('menu'),d=document.getElementById('drawer');
const closeMenu=()=>{d.classList.remove('open');b.setAttribute('aria-expanded','false')};
b.addEventListener('click',()=>{const open=d.classList.toggle('open');b.setAttribute('aria-expanded',open?'true':'false')});
d.querySelectorAll('a').forEach(a=>a.addEventListener('click',closeMenu));
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeMenu()});
</script>
</body>
</html>