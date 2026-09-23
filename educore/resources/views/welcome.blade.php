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
.module-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.module{position:relative;border:1px solid #dfe6ef;border-radius:18px;background:linear-gradient(180deg,#fff 0%,#fbfcfe 100%);padding:22px;min-height:184px;box-shadow:0 10px 24px rgba(7,26,56,.06);overflow:hidden;transition:.2s ease}
.module:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,var(--gold),var(--gold-2));opacity:.95}
.module:hover{transform:translateY(-3px);box-shadow:0 16px 36px rgba(7,26,56,.10)}
.module-icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(180deg,#fff5d9,#ffefbd);color:#986500;display:grid;place-items:center;margin:4px 0 16px;box-shadow:inset 0 0 0 1px rgba(224,170,51,.16)}
.module-icon svg{width:20px;height:20px;stroke:currentColor}
.module h3{margin:0 0 9px;font-size:14px;line-height:1.35;color:var(--navy)}
.module p{margin:0;color:#65748a;font-size:10px;line-height:1.65}

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
.phone-stage{min-height:470px;display:grid;place-items:center;position:relative}
.phone-stage:before{content:"";position:absolute;width:360px;height:360px;border-radius:50%;background:rgba(224,170,51,.9);right:-90px;bottom:-155px}
.phone-real{position:relative;width:min(286px,78%);z-index:1;filter:drop-shadow(0 32px 55px rgba(0,0,0,.38));transform:rotate(1.5deg)}
.phone-real:before{content:"";position:absolute;inset:-7px;border:7px solid #121923;border-radius:35px;pointer-events:none}
.phone-real img,.phone-real .embedded-mobile-dashboard,.phone-real .embedded-mobile-dashboard svg{width:100%;height:auto;border-radius:29px;display:block;background:#f4f6fa}.phone-real .embedded-mobile-dashboard{overflow:hidden}

.pricing-wrap{display:grid;grid-template-columns:1.02fr .98fr;border:1px solid #dce4ee;border-radius:22px;overflow:hidden;background:#fff;box-shadow:0 18px 50px rgba(7,26,56,.08)}
.price-dark{position:relative;background:linear-gradient(145deg,#071a38 0%,#0b2d5e 100%);color:#fff;padding:46px}
.price-dark:after{content:"";position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(224,170,51,.12);right:-70px;bottom:-90px}
.price-dark h2{margin:10px 0 14px;font-size:36px;line-height:1.08;letter-spacing:-.03em}
.price-dark p{margin:0;color:#c9d5e5;font-size:11px;line-height:1.8;max-width:460px}
.price-benefits{display:grid;gap:10px;margin-top:24px}.price-benefits span{display:flex;align-items:center;gap:9px;font-size:10px;color:#e5edf6}.price-benefits span:before{content:"✓";display:grid;place-items:center;width:22px;height:22px;border-radius:50%;background:rgba(242,201,94,.16);color:var(--gold-2);font-weight:900}
.price-card{padding:42px 40px;background:linear-gradient(180deg,#fff,#fbfcfe)}
.price-card small{font-size:8px;font-weight:900;color:#758297;text-transform:uppercase;letter-spacing:.08em}
.free{font-size:46px;font-weight:900;letter-spacing:-.05em;color:var(--navy);margin-top:5px}
.divider{height:1px;background:var(--line);margin:20px 0}
.paid{font-size:34px;font-weight:900;color:var(--navy);margin-top:5px}
.price-card p{font-size:10px;color:var(--muted);line-height:1.6}
.price-note{margin-top:16px;padding:12px 14px;border-radius:12px;background:#fff7df;color:#79570a;font-size:9px;line-height:1.55;border:1px solid #f2df9c}

.cta{padding:0 0 72px}
.cta-box{position:relative;overflow:hidden;background:linear-gradient(120deg,#071a38 0%,#0d3975 72%,#14539e 100%);border-radius:22px;padding:38px 42px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:30px;box-shadow:0 18px 45px rgba(7,26,56,.16)}
.cta-box:after{content:"";position:absolute;width:240px;height:240px;border-radius:50%;background:rgba(224,170,51,.18);right:-80px;top:-120px}
.cta-box>div{position:relative;z-index:1}
.cta-box h2{margin:0;font-size:34px;line-height:1.12;color:#fff;letter-spacing:-.03em}
.cta-box p{margin:8px 0 0;color:#cbd7e7;font-size:11px}
.cta-actions{display:flex;gap:10px;flex:none;position:relative;z-index:1}

footer{background:linear-gradient(135deg,#04152f 0%,#082b59 58%,#061d3d 100%);border-top:4px solid var(--gold);color:#d9e2ef;padding:58px 0 0}
.footer-grid{display:grid;grid-template-columns:1.45fr .7fr .7fr .7fr 1.45fr;gap:34px;align-items:start}
.footer-about .brand{color:#fff}.footer-about .brand img{width:46px;height:46px}.footer-about .brandname{color:#fff;font-size:24px}.footer-about p{max-width:330px;font-size:10px;line-height:1.85;color:#c4cfdd;margin:20px 0 0}.footer-tagline{color:var(--gold-2);font-size:14px;font-weight:800;margin-top:22px;letter-spacing:.01em}
.footer-grid h4{margin:0 0 16px;color:#fff;font-size:10px;text-transform:uppercase;letter-spacing:.13em}.footer-grid a{display:block;padding:5px 0;font-size:9.5px;color:#cbd5e1}.footer-grid a:hover{color:var(--gold-2)}
.footer-contact{padding:20px;border:1px solid rgba(255,255,255,.12);border-radius:16px;background:rgba(255,255,255,.035);box-shadow:inset 0 1px 0 rgba(255,255,255,.04)}.footer-contact h3{margin:0 0 7px;color:#fff;font-size:18px}.footer-contact>p{margin:0 0 14px;font-size:9px;line-height:1.65;color:#aebed1}.contact-card{display:flex!important;align-items:center;gap:11px;border:1px solid rgba(255,255,255,.16);border-radius:12px;padding:11px 12px!important;margin:9px 0;color:#fff!important;background:rgba(255,255,255,.025)}.contact-card svg{width:21px;height:21px;flex:0 0 21px}.contact-card span{display:block}.contact-card small{display:block;color:#91a3ba;font-size:7px;margin-bottom:2px}.contact-card strong{font-size:9.5px;color:#fff}.contact-card.whatsapp{border-color:rgba(37,211,102,.55);background:rgba(37,211,102,.04)}.contact-card.whatsapp svg{fill:#25D366}.contact-card.phone svg{fill:var(--gold)}.contact-card.email svg{fill:#fff}
.footer-lower{display:flex;justify-content:space-between;align-items:center;gap:24px;border-top:1px solid rgba(255,255,255,.14);margin-top:40px;padding:26px 0}.app-download h3{color:#fff;font-size:14px;margin:0 0 5px}.app-download p{font-size:8.5px;margin:0 0 11px;color:#9fb0c5}.footer-app-btn{display:inline-flex!important;align-items:center;gap:8px;background:linear-gradient(180deg,var(--gold-2),var(--gold));color:var(--navy)!important;border-radius:9px;padding:10px 14px!important;font-weight:900;font-size:9px!important;box-shadow:0 8px 18px rgba(224,170,51,.2)}.footer-app-btn svg{width:18px;height:18px;fill:var(--navy)}.footer-promise{display:flex;gap:16px;align-items:center;color:#fff;font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}.footer-promise span+span{border-left:1px solid rgba(255,255,255,.3);padding-left:16px}.copyright{border-top:1px solid rgba(255,255,255,.14);padding:17px 0 22px;font-size:8px;color:#8fa1b8}

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
  .phone-stage{min-height:430px}
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
  .phone-stage{min-height:390px}
  .phone-real{width:min(232px,72%)}
  .pricing-wrap{grid-template-columns:1fr}
  .price-dark,.price-card{padding:25px 20px}
  .cta{padding-bottom:50px}
  .cta-box{display:block;padding:24px 20px}
  .cta-box h2{font-size:25px}
  .cta-actions{display:grid;grid-template-columns:1fr 1fr;margin-top:17px}
  .footer-grid{grid-template-columns:1fr 1fr;gap:22px}
  .footer-contact{grid-column:1/-1}.footer-lower{align-items:flex-start;flex-direction:column}.footer-promise{width:100%;justify-content:flex-start}
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
      <div class="phone-real">
        <div class="embedded-mobile-dashboard" role="img" aria-label="EduCore mobile app teacher dashboard showing classes, students, timetable and quick actions"><svg xmlns="http://www.w3.org/2000/svg" width="390" height="844" viewBox="0 0 390 844" role="img" aria-labelledby="t d">
<title id="t">EduCore mobile teacher dashboard</title><desc id="d">A faithful marketing mockup of the EduCore native mobile home dashboard.</desc>
<rect width="390" height="844" rx="34" fill="#F4F6FA"/>
<rect width="390" height="118" fill="#082653"/>
<text x="20" y="38" fill="#fff" font-family="Arial,sans-serif" font-size="13" font-weight="700">12:41</text>
<text x="20" y="77" fill="#fff" font-family="Arial,sans-serif" font-size="18" font-weight="800">Edu<tspan fill="#E4AE32">Core</tspan> · Home</text>
<text x="20" y="98" fill="#CFD8E7" font-family="Arial,sans-serif" font-size="12" font-weight="600">Greenfield Academy · 2026/2027 · 1st Term</text>
<text x="352" y="84" text-anchor="middle" fill="#E4AE32" font-family="Arial,sans-serif" font-size="26">↻</text>
<circle cx="55" cy="172" r="29" fill="#E5EBF4"/><circle cx="55" cy="161" r="9" fill="#73849A"/><path d="M38 190c3-15 31-15 34 0" fill="#73849A"/>
<text x="92" y="160" fill="#0B1830" font-family="Arial,sans-serif" font-size="20" font-weight="800">Good afternoon,</text>
<text x="92" y="184" fill="#0B1830" font-family="Arial,sans-serif" font-size="20" font-weight="800">Mujah eed Mustapha</text>
<text x="92" y="207" fill="#46546A" font-family="Arial,sans-serif" font-size="14">Form &amp; Subject Teacher · STF1006</text>
<g transform="translate(18 237)"><rect width="354" height="116" rx="13" fill="#fff" stroke="#D7DDE7"/>
<text x="14" y="28" fill="#41506A" font-family="Arial,sans-serif" font-size="16" font-weight="800">Subscription status</text>
<rect x="266" y="12" width="75" height="28" rx="9" fill="#DCF7E8"/><text x="303.5" y="31" text-anchor="middle" fill="#19714C" font-family="Arial,sans-serif" font-size="12" font-weight="700">Free plan</text>
<text x="14" y="56" fill="#0B1830" font-family="Arial,sans-serif" font-size="18" font-weight="800">No expiry</text>
<text x="14" y="84" fill="#5B687C" font-family="Arial,sans-serif" font-size="12">Your school currently has no subscription expiry date.</text></g>
<g transform="translate(18 368)"><rect width="354" height="74" rx="12" fill="#fff" stroke="#D7DDE7"/>
<text x="14" y="26" fill="#09234B" font-family="Arial,sans-serif" font-size="16" font-weight="800">Today at a glance</text>
<text x="14" y="48" fill="#5B687C" font-family="Arial,sans-serif" font-size="11">Teaching, classes and tasks that need attention</text>
<text x="14" y="65" fill="#5B687C" font-family="Arial,sans-serif" font-size="10">Updated 2026-09-23 12:41</text></g>
<g font-family="Arial,sans-serif">
<g transform="translate(18 456)"><rect width="168" height="88" rx="13" fill="#fff" stroke="#D7DDE7"/><rect x="14" y="21" width="36" height="36" rx="9" fill="#EDF3FB"/><text x="32" y="45" text-anchor="middle" fill="#0A2B5C" font-size="18">▣</text><text x="61" y="35" fill="#0B1830" font-size="17" font-weight="800">4</text><text x="61" y="55" fill="#43516A" font-size="13">Assigned classes</text></g>
<g transform="translate(204 456)"><rect width="168" height="88" rx="13" fill="#fff" stroke="#D7DDE7"/><rect x="14" y="21" width="36" height="36" rx="9" fill="#E5F8EE"/><text x="32" y="45" text-anchor="middle" fill="#13734C" font-size="17">●●</text><text x="61" y="35" fill="#0B1830" font-size="17" font-weight="800">8</text><text x="61" y="55" fill="#43516A" font-size="13">Students</text></g>
<g transform="translate(18 558)"><rect width="168" height="88" rx="13" fill="#fff" stroke="#D7DDE7"/><rect x="14" y="21" width="36" height="36" rx="9" fill="#EDF3FB"/><text x="32" y="45" text-anchor="middle" fill="#0A2B5C" font-size="18">▦</text><text x="61" y="35" fill="#0B1830" font-size="17" font-weight="800">0</text><text x="61" y="55" fill="#43516A" font-size="13">Periods today</text></g>
<g transform="translate(204 558)"><rect width="168" height="88" rx="13" fill="#fff" stroke="#D7DDE7"/><rect x="14" y="21" width="36" height="36" rx="9" fill="#EDF3FB"/><text x="32" y="45" text-anchor="middle" fill="#0A2B5C" font-size="18">▪</text><text x="61" y="35" fill="#0B1830" font-size="17" font-weight="800">0</text><text x="61" y="55" fill="#43516A" font-size="13">Upcoming duties</text></g>
<g transform="translate(18 660)"><rect width="354" height="58" rx="12" fill="#fff" stroke="#D7DDE7"/><text x="14" y="25" fill="#09234B" font-size="16" font-weight="800">Quick actions</text><text x="14" y="44" fill="#5B687C" font-size="11">Most common teaching tasks</text></g>
</g>
<rect y="744" width="390" height="100" fill="#E9EDF3"/>
<g fill="#52637A" font-family="Arial,sans-serif" text-anchor="middle"><text x="39" y="780" font-size="21" fill="#0A2B5C">⌂</text><text x="39" y="807" font-size="11" font-weight="700" fill="#0A2B5C">Home</text><text x="117" y="780" font-size="20">◆</text><text x="117" y="807" font-size="11" font-weight="700">Classes</text><text x="195" y="780" font-size="20">◷</text><text x="195" y="807" font-size="11" font-weight="700">Timetable</text><text x="273" y="780" font-size="20">♟</text><text x="273" y="807" font-size="11" font-weight="700">Inbox</text><text x="351" y="780" font-size="22">•••</text><text x="351" y="807" font-size="11" font-weight="700">More</text></g>
</svg></div>
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
        <div class="price-benefits"><span>No fragmented feature tiers</span><span>Web + Android access included</span><span>Designed for growing Nigerian schools</span></div>
      </div>
      <div class="price-card">
        <small>Up to 50 students</small>
        <div class="free">Free</div>
        <div class="divider"></div>
        <small>Above 50 students</small>
        <div class="paid">₦300</div>
        <p>per student / term · all EduCore features included</p>
        <div class="price-note">Start free with up to 50 students, then scale transparently as your enrolment grows.</div>
        <a class="btn btn-gold" style="width:100%;margin-top:16px" href="{{ route('school.register') }}">Create School Account</a>
      </div>
    </div>
  </div>
</section>

<section class="cta">
  <div class="container cta-box">
    <div>
      <h2>Ready to simplify how your school works?</h2>
      <p>Bring academics, administration, finance and communication together in one connected EduCore workspace.</p>
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
        <p>Connected school management software built for Nigerian schools. Empowering better learning through technology.</p>
        <div class="footer-tagline">Together for a brighter education.</div>
      </div>
      <div><h4>Product</h4><a href="#features">Features</a><a href="#pricing">Pricing</a><a href="{{ route('app.download') }}">Android App</a><a href="mailto:support@educoreng.online">Help &amp; Support</a></div>
      <div><h4>Portals</h4><a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">School Admin</a><a href="{{ Route::has('student.login') ? route('student.login') : '#' }}">Student</a><a href="{{ Route::has('parent.login') ? route('parent.login') : '#' }}">Parent</a><a href="{{ Route::has('agent.portal.login') ? route('agent.portal.login') : '#' }}">Agent</a></div>
      <div><h4>Company</h4><a href="mailto:support@educoreng.online">Contact</a><a href="{{ route('legal.privacy') }}">Privacy Policy</a><a href="{{ route('legal.terms') }}">Terms of Service</a></div>
      <div class="footer-contact"><h3>Get in Touch</h3><p>Have questions, need support or want to partner with us? We’re here to help.</p>
        <a class="contact-card phone" href="tel:+2347065595768"><svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1.1.5 1.1 1.1V20c0 .6-.5 1.1-1.1 1.1C10.9 21.1 2.9 13.1 2.9 3.2c0-.6.5-1.1 1.1-1.1h3.5c.6 0 1.1.5 1.1 1.1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1l-2.4 3z"/></svg><span><small>Call Us</small><strong>07065595768</strong></span></a>
        <a class="contact-card whatsapp" href="https://wa.me/2348083070142" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.2-1.3c1.5.8 3.1 1.3 4.8 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2zm4.5 12c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8.9-.1.2-.3.2-.6.1-1.7-.8-2.8-1.5-3.9-3.4-.2-.3.2-.3.6-1.1.1-.2 0-.4 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4-1.5 0-2.2 1.1-2.2 2.4 0 2.8 2.1 5.5 2.4 5.8.3.4 4.1 6.3 10.1 4.4 1.2-.4 2.1-1.7 2.2-2.8.1-.4.1-.8 0-.9-.1-.1-.3-.2-.6-.3z"/></svg><span><small>Chat on WhatsApp</small><strong>08083070142</strong></span></a>
        <a class="contact-card email" href="mailto:support@educoreng.online"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg><span><small>Email Us</small><strong>support@educoreng.online</strong></span></a>
      </div>
    </div>
    <div class="footer-lower"><div class="app-download"><h3>Download the EduCore Mobile App</h3><p>Manage your school anytime, anywhere.</p><a class="footer-app-btn" href="{{ route('app.download') }}"><svg viewBox="0 0 24 24"><path d="M3 20.5v-17c0-.8.9-1.3 1.6-.8l14.7 8.5c.7.4.7 1.4 0 1.8L4.6 21.3c-.7.4-1.6 0-1.6-.8z"/></svg>Download Android App</a></div><div class="footer-promise"><span>Schools</span><span>People</span><span>Progress</span></div></div>
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