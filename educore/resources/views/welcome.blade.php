<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EduCore School ERP helps Nigerian schools run admissions, academics, attendance, fees, payroll, exams, staff HR, and parent communication from one secure platform.">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#071E45">
<link rel="canonical" href="https://educoreng.online/">
<title>EduCore &mdash; School Management Platform for Nigerian Schools</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900" rel="stylesheet">
<meta property="og:type" content="website">
<meta property="og:url" content="https://educoreng.online/">
<meta property="og:site_name" content="EduCore">
<meta property="og:title" content="EduCore &mdash; School Management Platform for Nigerian Schools">
<meta property="og:description" content="Run admissions, academics, attendance, fees, payroll, exams, staff HR, and parent communication from one secure platform.">
<meta property="og:image" content="https://educoreng.online/brand/og-image.png">
<meta name="twitter:card" content="summary_large_image">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --navy:#071E45;
    --navy-2:#0B2D63;
    --dark:#020817;
    --dark-2:#04132D;
    --gold:#D79A21;
    --gold-light:#F5B72E;
    --ink:#101828;
    --muted:#667085;
    --soft:#F4F7FB;
    --line:#E4EAF2;
    --green:#16794B;
    --green-light:#EAF7F1;
    --red:#D92D20;
    --font:'Plus Jakarta Sans',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
html{scroll-behavior:smooth;scroll-padding-top:84px}
body{font-family:var(--font);color:var(--ink);background:#fff;line-height:1.6;overflow-x:hidden}
body.menu-open{overflow:hidden}
a{text-decoration:none;color:inherit}
button{font:inherit}
img,svg{display:block}
.container{width:min(1220px,calc(100% - 48px));margin:0 auto}
.btn{
    min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:10px;
    border:1px solid transparent;border-radius:12px;padding:12px 20px;font-size:14px;
    font-weight:800;transition:transform .2s,box-shadow .2s,background .2s,border-color .2s;
}
.btn:hover{transform:translateY(-2px)}
.btn svg{width:18px;height:18px}
.btn-gold{color:#071E45;background:linear-gradient(180deg,var(--gold-light),var(--gold));box-shadow:0 14px 34px rgba(215,154,33,.24)}
.btn-gold:hover{box-shadow:0 18px 42px rgba(215,154,33,.34)}
.btn-dark{color:#fff;background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.22)}
.btn-dark:hover{background:rgba(255,255,255,.1);border-color:rgba(245,183,46,.6)}
.btn-navy{color:#fff;background:var(--navy);box-shadow:0 14px 32px rgba(7,30,69,.16)}
.btn-white{color:var(--navy);background:#fff;border-color:#E4EAF2}

/* Navigation */
.site-nav{
    position:fixed;inset:0 0 auto;z-index:100;height:78px;background:rgba(2,8,23,.88);
    border-bottom:1px solid rgba(255,255,255,.08);backdrop-filter:blur(18px);
}
.nav-inner{height:100%;display:flex;align-items:center;justify-content:space-between;gap:32px}
.brand{display:flex;align-items:center;gap:11px;flex:0 0 auto}
.brand img{width:42px;height:42px;border-radius:11px}
.brand-copy{line-height:1}
.brand-name{display:block;color:#fff;font-size:21px;font-weight:900;letter-spacing:-.055em}
.brand-name span{color:var(--gold-light)}
.brand-copy small{display:block;color:rgba(255,255,255,.62);font-size:10px;font-weight:600;margin-top:5px}
.nav-links{display:flex;align-items:center;gap:28px;margin-left:auto}
.nav-links a{position:relative;color:rgba(255,255,255,.76);font-size:13px;font-weight:700;padding:28px 0}
.nav-links a::after{content:"";position:absolute;left:0;right:100%;bottom:20px;height:2px;background:var(--gold-light);transition:right .2s}
.nav-links a:hover{color:#fff}
.nav-links a:hover::after{right:0}
.nav-actions{display:flex;align-items:center;gap:10px}
.menu-toggle{
    display:none;width:44px;height:44px;border:1px solid rgba(255,255,255,.16);
    border-radius:11px;color:#fff;background:rgba(255,255,255,.06);place-items:center;cursor:pointer;
}
.menu-toggle svg{width:22px;height:22px}
.mobile-menu{
    display:none;position:fixed;inset:78px 0 auto;z-index:99;background:#06142E;
    border-bottom:1px solid rgba(255,255,255,.1);padding:18px 24px 24px;box-shadow:0 24px 60px rgba(0,0,0,.32);
}
.mobile-menu.open{display:grid}
.mobile-menu a{color:#fff;font-size:14px;font-weight:700;padding:12px 10px;border-radius:10px}
.mobile-menu a:hover{background:rgba(255,255,255,.07)}
.mobile-menu .mobile-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}

/* Hero */
.hero{
    position:relative;isolation:isolate;min-height:840px;padding:150px 0 88px;color:#fff;
    background:
        radial-gradient(circle at 82% 23%,rgba(23,81,159,.35),transparent 30%),
        radial-gradient(circle at 10% 82%,rgba(215,154,33,.11),transparent 28%),
        linear-gradient(145deg,#020817 0%,#04142E 58%,#071E45 100%);
    overflow:hidden;
}
.hero::before{
    content:"";position:absolute;inset:0;z-index:-2;opacity:.22;
    background-image:linear-gradient(rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px);
    background-size:72px 72px;mask-image:linear-gradient(to right,#000,transparent 72%);
}
.hero::after{
    content:"";position:absolute;z-index:-1;width:580px;height:580px;right:-210px;bottom:-330px;
    border:1px solid rgba(245,183,46,.16);border-radius:50%;box-shadow:
        0 0 0 65px rgba(245,183,46,.035),0 0 0 130px rgba(245,183,46,.025),0 0 0 195px rgba(245,183,46,.018);
}
.hero-grid{display:grid;grid-template-columns:minmax(0,.88fr) minmax(570px,1.12fr);align-items:center;gap:58px}
.eyebrow-pill{
    display:inline-flex;align-items:center;gap:9px;padding:7px 11px;border:1px solid rgba(245,183,46,.26);
    background:rgba(245,183,46,.08);border-radius:999px;color:#FFD778;font-size:11px;font-weight:800;
    letter-spacing:.08em;text-transform:uppercase;margin-bottom:23px;
}
.eyebrow-pill span{width:7px;height:7px;border-radius:50%;background:var(--gold-light);box-shadow:0 0 0 5px rgba(245,183,46,.1)}
.hero h1{max-width:650px;font-size:clamp(46px,5.1vw,72px);line-height:1.02;letter-spacing:-.06em;font-weight:900}
.hero h1 em{color:var(--gold-light);font-style:normal}
.hero-copy>p{max-width:610px;margin-top:24px;color:rgba(255,255,255,.72);font-size:18px;line-height:1.75}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:32px}
.hero-proof{display:flex;align-items:center;gap:20px;flex-wrap:wrap;margin-top:30px}
.proof-item{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.66);font-size:11px;font-weight:700}
.proof-item svg{width:17px;height:17px;color:var(--gold-light)}

/* Native product mockup */
.product-stage{position:relative;height:580px;perspective:1400px}
.stage-glow{position:absolute;inset:8% 3% 2% 8%;background:rgba(45,112,207,.22);filter:blur(65px);border-radius:50%}
.desktop-device{
    position:absolute;top:24px;left:0;width:93%;height:505px;background:#0B0F17;border:1px solid rgba(255,255,255,.25);
    border-radius:22px;padding:9px;box-shadow:0 45px 90px rgba(0,0,0,.5),0 10px 26px rgba(0,0,0,.36);
    transform:rotateY(-5deg) rotateX(1deg) rotateZ(-1.3deg);transform-origin:center;
}
.browser-shell{height:100%;overflow:hidden;border-radius:14px;background:#F7F9FC}
.browser-bar{height:37px;background:#fff;border-bottom:1px solid #E7EBF1;display:flex;align-items:center;padding:0 14px;gap:6px}
.browser-dot{width:7px;height:7px;border-radius:50%}.browser-dot.red{background:#FF6B6B}.browser-dot.yellow{background:#FFBC42}.browser-dot.green{background:#2BC56F}
.browser-address{width:34%;height:8px;border-radius:999px;background:#EDF1F6;margin-left:13px}
.dashboard-shell{height:calc(100% - 37px);display:grid;grid-template-columns:126px 1fr}
.dash-sidebar{background:linear-gradient(180deg,#071E45,#04142F);padding:17px 12px;color:#fff}
.dash-brand{display:flex;align-items:center;gap:7px;margin-bottom:22px}
.dash-brand img{width:24px;height:24px;border-radius:6px}
.dash-brand strong{font-size:10px}
.dash-menu{display:grid;gap:5px}
.dash-link{display:flex;align-items:center;gap:7px;padding:7px 8px;border-radius:7px;color:rgba(255,255,255,.58);font-size:6.8px;font-weight:700}
.dash-link svg{width:10px;height:10px}
.dash-link.active{color:#071E45;background:linear-gradient(180deg,var(--gold-light),var(--gold))}
.dash-main{padding:15px 16px 16px;overflow:hidden;color:#14213A}
.dash-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.dash-top h3{font-size:12px;letter-spacing:-.025em}
.dash-top p{font-size:6.8px;color:#7A8699}
.role-badge{
    display:inline-flex;align-items:center;gap:5px;background:#FFF8E7;color:#8A5A00;border:1px solid #F2D897;
    padding:5px 7px;border-radius:7px;font-size:6.8px;font-weight:800;white-space:nowrap;
}
.role-badge svg{width:9px;height:9px}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:7px}
.stat-card{background:#fff;border:1px solid #E3E8EF;border-radius:9px;padding:9px;box-shadow:0 3px 10px rgba(16,24,40,.035)}
.stat-label{font-size:6.4px;color:#7C8798;font-weight:700}
.stat-value{font-size:13px;font-weight:900;color:#0A2349;margin-top:2px}
.stat-change{font-size:5.7px;color:#168557;margin-top:2px;font-weight:700}
.dashboard-panels{display:grid;grid-template-columns:1.45fr .8fr;gap:8px;margin-top:8px}
.panel{background:#fff;border:1px solid #E3E8EF;border-radius:10px;padding:9px}
.panel-heading{display:flex;justify-content:space-between;align-items:center;font-size:7px;font-weight:800;color:#14213A}
.panel-heading span{font-size:5.7px;color:#8A96A8;font-weight:600}
.chart-wrap{height:96px;position:relative;margin-top:6px;border-bottom:1px solid #EBEFF4;background:repeating-linear-gradient(to bottom,transparent 0,transparent 23px,#EEF2F6 24px)}
.chart-wrap svg{width:100%;height:100%;overflow:visible}
.fee-line{fill:none;stroke:#D79A21;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
.fee-area{fill:url(#feeGradient)}
.chart-point{fill:#D79A21;stroke:#fff;stroke-width:1.5}
.months{display:flex;justify-content:space-between;margin-top:4px;font-size:5.2px;color:#98A2B3}
.attendance-panel{display:grid;place-items:center}
.attendance-donut{
    width:78px;height:78px;border-radius:50%;display:grid;place-items:center;margin:13px auto 7px;
    background:conic-gradient(#1B9C63 0 92%,#F2B233 92% 96%,#E9EEF4 96%);
    position:relative;
}
.attendance-donut::after{content:"";position:absolute;width:52px;height:52px;border-radius:50%;background:#fff}
.donut-copy{position:relative;z-index:1;text-align:center;font-size:6px;color:#7A8699;line-height:1.1}
.donut-copy strong{display:block;font-size:14px;color:#0A2349}
.mini-legend{display:flex;gap:8px;font-size:5.6px;color:#7A8699}
.mini-legend span{display:flex;align-items:center;gap:3px}.mini-legend i{width:5px;height:5px;border-radius:50%;display:block}
.dashboard-bottom{display:grid;grid-template-columns:1.25fr 1fr;gap:8px;margin-top:8px}
.activity-list{display:grid;gap:5px;margin-top:8px}
.activity-row{display:flex;align-items:center;justify-content:space-between;font-size:5.7px;color:#566176}
.activity-info{display:flex;align-items:center;gap:5px}.activity-info i{width:6px;height:6px;border-radius:50%;background:#DDF5E9;border:2px solid #4AB781}
.quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;margin-top:8px}
.quick-item{padding:6px 4px;text-align:center;border-radius:6px;background:#F5F7FA;color:#516078;font-size:5.5px;font-weight:700}
.quick-item svg{width:11px;height:11px;color:#0B5EB8;margin:0 auto 3px}
.device-base{position:absolute;left:5%;right:2%;bottom:25px;height:18px;background:linear-gradient(180deg,#2E3440,#0D1118);clip-path:polygon(3% 0,97% 0,100% 100%,0 100%);border-radius:0 0 48% 48%;box-shadow:0 15px 18px rgba(0,0,0,.32)}
.phone-device{
    position:absolute;right:-2px;bottom:0;width:188px;height:394px;border:7px solid #171B22;border-radius:31px;
    background:#fff;overflow:hidden;box-shadow:0 35px 60px rgba(0,0,0,.48),inset 0 0 0 1px rgba(255,255,255,.2);
    transform:rotate(3deg);
}
.phone-notch{position:absolute;z-index:5;top:0;left:50%;transform:translateX(-50%);width:78px;height:18px;background:#171B22;border-radius:0 0 13px 13px}
.phone-status{height:25px;padding:8px 12px 0;background:#071E45;color:#fff;display:flex;justify-content:space-between;font-size:6px;font-weight:800}
.phone-header{height:70px;background:linear-gradient(140deg,#071E45,#0C316A);padding:13px 11px;color:#fff}
.phone-brand{display:flex;align-items:center;gap:6px;font-size:8px;font-weight:800}
.phone-brand img{width:18px;height:18px;border-radius:5px}
.phone-role{display:flex;align-items:center;gap:7px;margin-top:9px;padding:7px;border:1px solid rgba(245,183,46,.26);background:rgba(245,183,46,.09);border-radius:9px}
.phone-role-icon{width:25px;height:25px;display:grid;place-items:center;color:#071E45;background:var(--gold-light);border-radius:7px}
.phone-role-icon svg{width:14px;height:14px}
.phone-role strong{display:block;font-size:7px}.phone-role span{display:block;color:rgba(255,255,255,.58);font-size:5.3px}
.phone-content{height:260px;padding:10px;background:#F4F7FB;color:#12203A}
.phone-section-label{font-size:6px;text-transform:uppercase;letter-spacing:.08em;font-weight:900;color:#758198;margin-bottom:6px}
.phone-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:5px}
.phone-metric{background:#fff;border:1px solid #E5EAF1;border-radius:7px;padding:7px 4px;text-align:center}
.phone-metric strong{display:block;font-size:11px}.phone-metric span{font-size:5px;color:#78859A}.phone-metric.present strong{color:#168557}.phone-metric.absent strong{color:#D92D20}.phone-metric.late strong{color:#B7790A}
.phone-fee{margin-top:8px;background:#fff;border:1px solid #E5EAF1;border-radius:9px;padding:9px}
.phone-fee-head{display:flex;justify-content:space-between;font-size:6px;font-weight:800}.phone-fee strong{display:block;font-size:13px;color:#071E45;margin-top:4px}
.progress{height:5px;background:#E8EDF3;border-radius:999px;overflow:hidden;margin-top:6px}.progress span{display:block;height:100%;width:82%;background:#168557;border-radius:inherit}
.phone-action-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px}
.phone-action{display:flex;align-items:center;gap:5px;background:#fff;border:1px solid #E5EAF1;border-radius:8px;padding:7px;font-size:5.5px;font-weight:800}
.phone-action svg{width:12px;height:12px;color:#D79A21}
.phone-nav{position:absolute;left:0;right:0;bottom:0;height:39px;border-top:1px solid #E6EBF2;background:#fff;display:flex;align-items:center;justify-content:space-around}
.phone-nav span{display:grid;place-items:center;gap:2px;color:#98A2B3;font-size:4.7px}.phone-nav svg{width:12px;height:12px}.phone-nav .active{color:#D79A21}
.floating-trust{
    position:absolute;right:38px;top:0;display:flex;align-items:center;gap:8px;padding:9px 12px;
    border:1px solid rgba(255,255,255,.14);border-radius:11px;background:rgba(7,30,69,.74);
    box-shadow:0 14px 34px rgba(0,0,0,.22);backdrop-filter:blur(10px);font-size:8px;font-weight:800;color:#fff;z-index:4;
}
.floating-trust svg{width:14px;height:14px;color:#F5B72E}

/* Trust strip */
.role-strip{position:relative;z-index:3;margin-top:-34px}
.role-strip-inner{
    background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 22px 60px rgba(7,30,69,.13);
    display:grid;grid-template-columns:1.25fr repeat(6,1fr);align-items:center;min-height:74px;padding:10px 22px;
}
.role-strip-title{font-size:12px;font-weight:900;color:var(--navy)}
.role-name{display:flex;align-items:center;justify-content:center;gap:6px;color:#667085;font-size:10px;font-weight:700}
.role-name i{width:7px;height:7px;border-radius:50%;background:#D79A21}

/* Sections */
.section{padding:102px 0}
.section-soft{background:var(--soft)}
.section-dark{color:#fff;background:linear-gradient(145deg,#031127,#071E45)}
.section-head{max-width:760px;margin:0 auto 48px;text-align:center}
.section-kicker{color:#B7790A;font-size:11px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}
.section-dark .section-kicker{color:#F5C95C}
.section-title{margin-top:12px;color:var(--navy);font-size:clamp(32px,4vw,50px);line-height:1.12;letter-spacing:-.05em;font-weight:900}
.section-dark .section-title{color:#fff}
.section-sub{max-width:680px;margin:16px auto 0;color:var(--muted);font-size:16px}
.section-dark .section-sub{color:rgba(255,255,255,.66)}

/* Feature bento */
.feature-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:18px}
.feature-card{
    position:relative;overflow:hidden;grid-column:span 4;min-height:270px;padding:28px;border:1px solid var(--line);
    border-radius:22px;background:#fff;box-shadow:0 14px 40px rgba(16,24,40,.055);
}
.feature-card.large{grid-column:span 7;min-height:330px;background:linear-gradient(145deg,#071E45,#0B326D);color:#fff;border:0}
.feature-card.wide{grid-column:span 5;min-height:330px}
.feature-card.half{grid-column:span 6}
.feature-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:14px;background:#FFF5DD;color:#A86E00;margin-bottom:22px}
.feature-icon svg{width:23px;height:23px}
.feature-card.large .feature-icon{background:rgba(245,183,46,.13);color:#F5C95C;border:1px solid rgba(245,183,46,.22)}
.feature-card h3{font-size:19px;color:var(--navy);letter-spacing:-.025em}
.feature-card.large h3{color:#fff;font-size:25px}
.feature-card p{max-width:420px;margin-top:9px;color:var(--muted);font-size:13px}
.feature-card.large p{color:rgba(255,255,255,.68)}
.feature-tags{display:flex;gap:7px;flex-wrap:wrap;margin-top:22px}
.feature-tags span{padding:6px 9px;border:1px solid #E4EAF2;border-radius:999px;background:#F8FAFC;color:#59677C;font-size:9px;font-weight:800}
.feature-card.large .feature-tags span{border-color:rgba(255,255,255,.13);background:rgba(255,255,255,.06);color:rgba(255,255,255,.76)}
.mini-report{position:absolute;right:-28px;bottom:-28px;width:280px;padding:16px;background:#fff;border-radius:18px;box-shadow:0 20px 50px rgba(0,0,0,.22);transform:rotate(-3deg);color:#14213A}
.mini-report-title{display:flex;justify-content:space-between;font-size:8px;font-weight:800;margin-bottom:10px}.mini-report-title span{color:#168557}
.mini-bars{height:88px;display:flex;align-items:flex-end;gap:8px;padding-top:8px;border-bottom:1px solid #E5EAF1;background:repeating-linear-gradient(to bottom,transparent 0,transparent 21px,#EEF2F6 22px)}
.mini-bars i{flex:1;border-radius:4px 4px 0 0;background:linear-gradient(180deg,#F5B72E,#D79A21)}
.mini-bars i:nth-child(1){height:38%}.mini-bars i:nth-child(2){height:52%}.mini-bars i:nth-child(3){height:68%}.mini-bars i:nth-child(4){height:84%}.mini-bars i:nth-child(5){height:100%}
.security-orbit{position:absolute;right:-35px;bottom:-35px;width:190px;height:190px;border:1px solid #DCE4EE;border-radius:50%;display:grid;place-items:center;box-shadow:0 0 0 32px #F7F9FC,0 0 0 33px #E9EEF4}
.security-orbit div{width:72px;height:72px;border-radius:22px;background:#071E45;color:#F5B72E;display:grid;place-items:center;box-shadow:0 16px 36px rgba(7,30,69,.24)}
.security-orbit svg{width:34px;height:34px}

/* Portals */
.portal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.portal-card{
    display:block;position:relative;overflow:hidden;min-height:220px;padding:25px;border:1px solid rgba(255,255,255,.12);
    border-radius:20px;background:rgba(255,255,255,.055);transition:.24s;
}
.portal-card:hover{transform:translateY(-4px);border-color:rgba(245,183,46,.5);background:rgba(255,255,255,.09)}
.portal-top{display:flex;align-items:center;justify-content:space-between}
.portal-icon{width:45px;height:45px;border-radius:13px;background:rgba(245,183,46,.12);color:#F5C95C;display:grid;place-items:center;border:1px solid rgba(245,183,46,.18)}
.portal-icon svg{width:22px;height:22px}
.portal-arrow{width:30px;height:30px;border-radius:50%;display:grid;place-items:center;border:1px solid rgba(255,255,255,.14);color:#fff}
.portal-arrow svg{width:14px;height:14px}
.portal-card h3{font-size:18px;margin-top:25px}
.portal-card p{color:rgba(255,255,255,.58);font-size:12px;margin-top:8px}
.permission-chip{display:inline-flex;align-items:center;gap:6px;margin-top:19px;color:#F6D47A;font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.07em}
.permission-chip svg{width:12px;height:12px}

/* Mobile product section */
.mobile-product-grid{display:grid;grid-template-columns:.9fr 1.1fr;align-items:center;gap:80px}
.mobile-copy .section-title{max-width:590px}
.mobile-copy .section-sub{margin-left:0;max-width:570px}
.check-list{display:grid;grid-template-columns:1fr 1fr;gap:14px 22px;margin:30px 0}
.check-item{display:flex;align-items:flex-start;gap:9px;color:#475467;font-size:12px;font-weight:700}
.check-item span{flex:0 0 auto;width:19px;height:19px;border-radius:50%;display:grid;place-items:center;background:var(--green-light);color:var(--green)}
.check-item svg{width:11px;height:11px}
.mobile-showcase{position:relative;min-height:520px;display:grid;place-items:center}
.showcase-halo{position:absolute;width:440px;height:440px;border-radius:50%;background:linear-gradient(145deg,#E7EEF8,#F9FBFD);box-shadow:inset 0 0 0 1px #DFE7F1,0 22px 60px rgba(7,30,69,.08)}
.showcase-card{position:absolute;background:#fff;border:1px solid #E4EAF2;border-radius:15px;padding:13px 16px;box-shadow:0 16px 40px rgba(16,24,40,.11)}
.showcase-card.one{left:1%;top:24%;transform:rotate(-4deg)}
.showcase-card.two{right:0;bottom:20%;transform:rotate(4deg)}
.showcase-card strong{display:block;font-size:12px;color:#071E45}.showcase-card span{font-size:8px;color:#7A8699}
.showcase-phone{position:relative;width:238px;height:490px;border:8px solid #171B22;border-radius:38px;background:#F4F7FB;overflow:hidden;box-shadow:0 38px 75px rgba(7,30,69,.28);transform:rotate(2deg)}
.showcase-phone .phone-notch{width:92px;height:21px}
.showcase-phone-head{height:148px;padding:35px 17px 17px;color:#fff;background:linear-gradient(145deg,#071E45,#0D3978)}
.showcase-appbar{display:flex;align-items:center;justify-content:space-between}.showcase-appbar div{display:flex;align-items:center;gap:8px;font-size:10px;font-weight:800}.showcase-appbar img{width:25px;height:25px;border-radius:6px}.showcase-appbar svg{width:17px;height:17px}
.showcase-greeting{margin-top:24px;font-size:10px;color:rgba(255,255,255,.65)}.showcase-greeting strong{display:block;color:#fff;font-size:17px;margin-top:2px}
.showcase-body{padding:14px}
.showcase-rbac{display:flex;align-items:center;gap:10px;padding:11px;background:#FFF7E4;border:1px solid #F2D99A;border-radius:12px;color:#6C4A00}
.showcase-rbac-icon{width:32px;height:32px;border-radius:9px;background:#D79A21;color:#fff;display:grid;place-items:center}.showcase-rbac svg{width:17px;height:17px}.showcase-rbac strong{display:block;font-size:9px}.showcase-rbac span{display:block;font-size:6.5px;color:#9B7425}
.showcase-title{font-size:8px;text-transform:uppercase;letter-spacing:.08em;color:#7A8699;font-weight:900;margin:16px 0 8px}
.showcase-tiles{display:grid;grid-template-columns:1fr 1fr;gap:8px}.showcase-tile{background:#fff;border:1px solid #E3E8EF;border-radius:11px;padding:12px}.showcase-tile svg{width:19px;height:19px;color:#D79A21}.showcase-tile strong{display:block;font-size:8px;margin-top:8px;color:#071E45}.showcase-tile span{display:block;font-size:6px;color:#8A96A8;margin-top:2px}
.showcase-bottom{position:absolute;left:0;right:0;bottom:0;height:50px;background:#fff;border-top:1px solid #E1E7EF;display:flex;align-items:center;justify-content:space-around}.showcase-bottom span{display:grid;place-items:center;gap:3px;font-size:5px;color:#98A2B3}.showcase-bottom svg{width:14px;height:14px}.showcase-bottom .active{color:#D79A21}

/* Pricing */
.pricing-shell{
    position:relative;overflow:hidden;display:grid;grid-template-columns:.85fr 1.15fr;border:1px solid var(--line);
    border-radius:28px;background:#fff;box-shadow:0 26px 80px rgba(7,30,69,.12);
}
.pricing-copy{padding:54px;background:linear-gradient(145deg,#071E45,#0B326D);color:#fff}
.pricing-copy .section-kicker{color:#F5C95C}.pricing-copy h2{font-size:clamp(34px,4vw,48px);line-height:1.08;letter-spacing:-.055em;margin-top:13px}.pricing-copy p{color:rgba(255,255,255,.67);font-size:14px;margin-top:17px}
.price-proof{display:grid;gap:13px;margin-top:30px}.price-proof div{display:flex;align-items:center;gap:9px;font-size:11px;font-weight:700;color:rgba(255,255,255,.8)}.price-proof svg{width:17px;height:17px;color:#F5C95C}
.pricing-card{padding:48px 52px}
.pricing-label{font-size:11px;color:#667085;font-weight:800;text-transform:uppercase;letter-spacing:.08em}
.free-price{font-size:54px;color:#071E45;font-weight:900;letter-spacing:-.055em;line-height:1;margin-top:13px}.free-price small{font-size:13px;color:#667085;letter-spacing:0}
.pricing-divider{height:1px;background:#E5EAF1;margin:28px 0}
.paid-price{display:flex;align-items:flex-end;gap:9px}.paid-price strong{font-size:38px;line-height:1;color:#071E45;letter-spacing:-.04em}.paid-price span{font-size:11px;color:#667085;padding-bottom:3px}
.pricing-note{font-size:11px;color:#667085;margin-top:9px}
.pricing-card .btn{width:100%;margin-top:28px}

/* Closing CTA */
.cta{padding:0 0 102px}
.cta-card{
    position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:32px;
    padding:48px 52px;border-radius:26px;color:#fff;background:
        radial-gradient(circle at 85% 20%,rgba(245,183,46,.18),transparent 30%),
        linear-gradient(135deg,#020817,#071E45);
    box-shadow:0 24px 70px rgba(7,30,69,.2);
}
.cta-card::after{content:"";position:absolute;width:260px;height:260px;border:1px solid rgba(245,183,46,.15);border-radius:50%;right:-100px;bottom:-170px;box-shadow:0 0 0 45px rgba(245,183,46,.035)}
.cta-copy{position:relative;z-index:1}.cta-copy h2{font-size:clamp(28px,3.5vw,42px);line-height:1.15;letter-spacing:-.045em}.cta-copy p{color:rgba(255,255,255,.66);font-size:13px;margin-top:9px}.cta-actions{position:relative;z-index:1;display:flex;gap:10px;flex-wrap:wrap}

/* Footer */
footer{background:#020817;color:rgba(255,255,255,.62);padding:68px 0 30px}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;margin-bottom:45px}
.footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:15px}.footer-brand img{width:38px;height:38px;border-radius:9px}.footer-brand span{font-size:20px;font-weight:900;color:#fff;letter-spacing:-.045em}.footer-brand b{color:var(--gold-light)}
.footer-about>p{max-width:360px;font-size:12px}
.contact-list{display:grid;gap:10px;margin-top:19px}.contact-row{display:flex;align-items:center;gap:9px}.contact-row svg{width:15px;height:15px;color:#D79A21;flex:0 0 auto}.contact-row a{font-size:11px;color:rgba(255,255,255,.72);font-weight:700}.contact-row a:hover,.footer-column a:hover{color:#F5B72E}
.footer-column h4{font-size:10px;text-transform:uppercase;letter-spacing:.11em;color:#fff;margin-bottom:14px}.footer-column a{display:block;color:rgba(255,255,255,.56);font-size:11px;padding:4px 0}
.footer-bottom{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;padding-top:24px;border-top:1px solid rgba(255,255,255,.08);font-size:10px}
.footer-links{display:flex;gap:20px}.footer-links a:hover{color:#F5B72E}

/* Responsive */
@media(max-width:1120px){
    .nav-links,.nav-actions{display:none}.menu-toggle{display:grid}
    .hero{padding-top:130px}.hero-grid{grid-template-columns:1fr;gap:46px}.hero-copy{text-align:center}.hero h1,.hero-copy>p{margin-left:auto;margin-right:auto}.hero-actions,.hero-proof{justify-content:center}
    .product-stage{width:min(780px,100%);margin:0 auto}
    .role-strip-inner{grid-template-columns:repeat(3,1fr);gap:12px;padding:18px}.role-strip-title{grid-column:1/-1;text-align:center}.role-name{justify-content:flex-start}
    .feature-card{grid-column:span 6}.feature-card.large,.feature-card.wide{grid-column:span 6}
    .portal-grid{grid-template-columns:repeat(2,1fr)}
    .mobile-product-grid{gap:45px}
}
@media(max-width:860px){
    .container{width:min(100% - 36px,1220px)}
    .section{padding:80px 0}
    .mobile-product-grid{grid-template-columns:1fr}.mobile-copy{text-align:center}.mobile-copy .section-sub{margin-left:auto}.check-list{text-align:left;max-width:600px;margin-left:auto;margin-right:auto}.mobile-copy .btn{margin:0 auto}
    .pricing-shell{grid-template-columns:1fr}.pricing-copy,.pricing-card{padding:40px}
    .footer-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:680px){
    .container{width:min(100% - 28px,1220px)}
    .site-nav{height:70px}.mobile-menu{top:70px}.brand img{width:37px;height:37px}.brand-name{font-size:19px}
    .hero{min-height:auto;padding:116px 0 76px}.hero h1{font-size:clamp(40px,12vw,56px)}.hero-copy>p{font-size:15px}
    .hero-actions{display:grid}.hero-actions .btn{width:100%}.hero-proof{gap:12px;justify-content:flex-start;text-align:left}
    .product-stage{height:430px;margin-top:6px}.desktop-device{height:375px;width:100%;padding:6px;border-radius:15px;transform:none}.browser-shell{border-radius:10px}.browser-bar{height:28px}.dashboard-shell{height:calc(100% - 28px);grid-template-columns:1fr}.dash-sidebar{display:none}.dash-main{padding:11px}.dash-top{margin-bottom:8px}.dash-top h3{font-size:10px}.stat-grid{grid-template-columns:repeat(2,1fr)}.stat-card:nth-child(n+3){display:none}.dashboard-panels{grid-template-columns:1fr}.attendance-panel{display:none}.chart-wrap{height:90px}.dashboard-bottom{grid-template-columns:1fr}.dashboard-bottom .panel:last-child{display:none}.device-base{bottom:39px}.phone-device{width:142px;height:298px;right:-4px;border-width:5px;border-radius:24px}.phone-notch{width:58px;height:13px}.phone-header{height:62px;padding:10px 8px}.phone-status{height:19px}.phone-role{padding:5px;margin-top:6px}.phone-role-icon{width:20px;height:20px}.phone-content{height:190px;padding:7px}.phone-metric{padding:5px 2px}.phone-fee{padding:7px}.phone-action-grid{display:none}.phone-nav{height:30px}.floating-trust{right:10px;top:-8px}
    .role-strip{margin-top:-27px}.role-strip-inner{grid-template-columns:1fr 1fr}.role-strip-title{grid-column:1/-1}.role-name{font-size:9px}
    .section-head{margin-bottom:34px}.section-title{font-size:34px}.section-sub{font-size:14px}
    .feature-grid{grid-template-columns:1fr}.feature-card,.feature-card.large,.feature-card.wide,.feature-card.half{grid-column:auto;min-height:260px}.mini-report{opacity:.85;right:-70px}
    .portal-grid{grid-template-columns:1fr}.portal-card{min-height:190px}
    .check-list{grid-template-columns:1fr}
    .mobile-showcase{min-height:480px;transform:scale(.9);margin:-24px 0}.showcase-card.one{left:-4%}.showcase-card.two{right:-5%}
    .pricing-copy,.pricing-card{padding:34px 25px}.free-price{font-size:46px}.paid-price strong{font-size:32px}
    .cta{padding-bottom:80px}.cta-card{padding:38px 25px;align-items:flex-start;flex-direction:column}.cta-actions{display:grid;width:100%}.cta-actions .btn{width:100%}
    footer{padding-top:55px}.footer-grid{grid-template-columns:1fr;gap:30px}.footer-bottom{align-items:flex-start;flex-direction:column}
}
@media(max-width:390px){
    .mobile-menu .mobile-actions{grid-template-columns:1fr}
    .hero h1{font-size:38px}.proof-item{width:100%}
    .product-stage{height:390px}.desktop-device{height:340px}.phone-device{width:132px;height:278px}
    .showcase-halo{width:360px;height:360px}.showcase-card{display:none}
}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.btn,.portal-card{transition:none}}
</style>
</head>
<body>
<nav class="site-nav" aria-label="Primary navigation">
    <div class="container nav-inner">
        <a href="{{ route('home') }}" class="brand" aria-label="EduCore home">
            <img src="/brand/educore-icon.svg" alt="">
            <span class="brand-copy">
                <strong class="brand-name">Edu<span>Core</span></strong>
                <small>School ERP</small>
            </span>
        </a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#portals">Portals</a>
            <a href="#pricing">Pricing</a>
            <a href="#testimonials">Reviews</a>
            <a href="#download">Download</a>
            <a href="mailto:support@educoreng.online">Contact</a>
        </div>
        <div class="nav-actions">
            <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}" class="btn btn-dark">Login</a>
            <a href="{{ route('school.register') }}" class="btn btn-gold">Get Started &rarr;</a>
        </div>
        <button class="menu-toggle" id="menuToggle" type="button" aria-expanded="false" aria-controls="mobileMenu" aria-label="Open menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>
</nav>
<div class="mobile-menu" id="mobileMenu">
    <a href="#features">Features</a>
    <a href="#portals">Portals</a>
    <a href="#pricing">Pricing</a>
    <a href="#testimonials">Reviews</a>
    <a href="#download">Download</a>
    <a href="mailto:support@educoreng.online">Contact</a>
    <div class="mobile-actions">
        <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}" class="btn btn-dark">Login</a>
        <a href="{{ route('school.register') }}" class="btn btn-gold">Get Started</a>
    </div>
</div>

<main>
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <div class="eyebrow-pill"><span></span>Built for Nigerian schools</div>
                <h1>One platform. Every school operation, <em>perfectly connected.</em></h1>
                <p>Run admissions, academics, attendance, fees, payroll, exams, staff HR, and parent communication from one secure, beautifully simple system.</p>
                <div class="hero-actions">
                    <a href="{{ route('school.register') }}" class="btn btn-gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        Start Free
                    </a>
                    <a href="{{ route('app.download') }}" class="btn btn-dark">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.6 2.7 14 12 3.6 21.3c-.4-.3-.6-.8-.6-1.4V4.1c0-.6.2-1.1.6-1.4Zm11.6 10.4 2.5 2.2L6 22l9.2-8.9Zm3.8-3.4 2.2 1.2c1 .6 1 1.6 0 2.2L19 14.3 16.4 12 19 9.7ZM6 2l11.7 6.7-2.5 2.2L6 2Z"/></svg>
                        Download Android App
                    </a>
                </div>
                <div class="hero-proof">
                    <div class="proof-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-5"/><path d="M12 3 4.5 6v5.5c0 4.6 3.2 7.8 7.5 9.5 4.3-1.7 7.5-4.9 7.5-9.5V6L12 3Z"/></svg>
                        Strict role-based access
                    </div>
                    <div class="proof-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
                        Web and Android access
                    </div>
                    <div class="proof-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7 9 18l-5-5"/></svg>
                        All features included
                    </div>
                </div>
            </div>

            <div class="product-stage" aria-label="EduCore web dashboard and Android application preview">
                <div class="stage-glow"></div>
                <div class="floating-trust">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    Enterprise security with RBAC
                </div>
                <div class="desktop-device">
                    <div class="browser-shell">
                        <div class="browser-bar">
                            <span class="browser-dot red"></span><span class="browser-dot yellow"></span><span class="browser-dot green"></span>
                            <span class="browser-address"></span>
                        </div>
                        <div class="dashboard-shell">
                            <aside class="dash-sidebar">
                                <div class="dash-brand"><img src="/brand/educore-icon.svg" alt=""><strong>EduCore</strong></div>
                                <div class="dash-menu">
                                    <div class="dash-link active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z"/></svg>Dashboard</div>
                                    <div class="dash-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Students</div>
                                    <div class="dash-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10h18M5 6h14l2 4v10H3V10l2-4ZM8 14h3M14 14h2"/></svg>Admissions</div>
                                    <div class="dash-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4M16 2v4M3 9h18M5 4h14a2 2 0 0 1 2 2v15H3V6a2 2 0 0 1 2-2Z"/></svg>Attendance</div>
                                    <div class="dash-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/></svg>Fees</div>
                                    <div class="dash-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/></svg>Reports</div>
                                    <div class="dash-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg>Settings</div>
                                </div>
                            </aside>
                            <div class="dash-main">
                                <div class="dash-top">
                                    <div><h3>School Command Centre</h3><p>Welcome back, Administrator</p></div>
                                    <div class="role-badge">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                        RBAC: School Admin
                                    </div>
                                </div>
                                <div class="stat-grid">
                                    <div class="stat-card"><div class="stat-label">Students</div><div class="stat-value">1,250</div><div class="stat-change">+8.5% this term</div></div>
                                    <div class="stat-card"><div class="stat-label">Staff</div><div class="stat-value">87</div><div class="stat-change">+3.1% this term</div></div>
                                    <div class="stat-card"><div class="stat-label">Classes</div><div class="stat-value">56</div><div class="stat-change">All active</div></div>
                                    <div class="stat-card"><div class="stat-label">Fees collected</div><div class="stat-value">&#8358;12.6M</div><div class="stat-change">+12.7% this term</div></div>
                                </div>
                                <div class="dashboard-panels">
                                    <div class="panel">
                                        <div class="panel-heading">Fee Collection Trend <span>January &ndash; June</span></div>
                                        <div class="chart-wrap">
                                            <svg viewBox="0 0 300 100" preserveAspectRatio="none" aria-hidden="true">
                                                <defs><linearGradient id="feeGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F5B72E" stop-opacity=".28"/><stop offset="1" stop-color="#F5B72E" stop-opacity="0"/></linearGradient></defs>
                                                <path class="fee-area" d="M0,82 L48,72 L96,62 L144,43 L192,59 L240,27 L300,38 L300,100 L0,100 Z"/>
                                                <path class="fee-line" d="M0,82 L48,72 L96,62 L144,43 L192,59 L240,27 L300,38"/>
                                                <circle class="chart-point" cx="48" cy="72" r="3"/><circle class="chart-point" cx="96" cy="62" r="3"/><circle class="chart-point" cx="144" cy="43" r="3"/><circle class="chart-point" cx="192" cy="59" r="3"/><circle class="chart-point" cx="240" cy="27" r="3"/><circle class="chart-point" cx="300" cy="38" r="3"/>
                                            </svg>
                                        </div>
                                        <div class="months"><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span></div>
                                    </div>
                                    <div class="panel attendance-panel">
                                        <div class="panel-heading" style="width:100%">Attendance Overview <span>Today</span></div>
                                        <div class="attendance-donut"><div class="donut-copy"><strong>92%</strong>Present</div></div>
                                        <div class="mini-legend"><span><i style="background:#1B9C63"></i>Present</span><span><i style="background:#F2B233"></i>Late</span></div>
                                    </div>
                                </div>
                                <div class="dashboard-bottom">
                                    <div class="panel">
                                        <div class="panel-heading">Recent Activity <span>View all</span></div>
                                        <div class="activity-list">
                                            <div class="activity-row"><span class="activity-info"><i></i>New admission: Jide Adeyemi</span><span>2 mins ago</span></div>
                                            <div class="activity-row"><span class="activity-info"><i></i>Fee payment received: &#8358;75,000</span><span>15 mins ago</span></div>
                                            <div class="activity-row"><span class="activity-info"><i></i>Staff attendance completed</span><span>1 hour ago</span></div>
                                        </div>
                                    </div>
                                    <div class="panel">
                                        <div class="panel-heading">Quick Actions <span>Manage</span></div>
                                        <div class="quick-grid">
                                            <div class="quick-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add student</div>
                                            <div class="quick-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10h18M5 6h14l2 4v10H3V10l2-4Z"/></svg>Create bill</div>
                                            <div class="quick-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/></svg>Reports</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="device-base"></div>
                <div class="phone-device">
                    <div class="phone-notch"></div>
                    <div class="phone-status"><span>9:41</span><span>4G&nbsp; 92%</span></div>
                    <div class="phone-header">
                        <div class="phone-brand"><img src="/brand/educore-icon.svg" alt="">EduCore Mobile</div>
                        <div class="phone-role">
                            <div class="phone-role-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div>
                            <div><strong>RBAC: Staff Portal</strong><span>Secure role access</span></div>
                        </div>
                    </div>
                    <div class="phone-content">
                        <div class="phone-section-label">Today&rsquo;s overview</div>
                        <div class="phone-metrics">
                            <div class="phone-metric present"><strong>32</strong><span>Present</span></div>
                            <div class="phone-metric absent"><strong>3</strong><span>Absent</span></div>
                            <div class="phone-metric late"><strong>1</strong><span>Late</span></div>
                        </div>
                        <div class="phone-fee">
                            <div class="phone-fee-head"><span>Fee Collection</span><span>This term</span></div>
                            <strong>&#8358;3,450,000</strong>
                            <div class="progress"><span></span></div>
                        </div>
                        <div class="phone-action-grid">
                            <div class="phone-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4M16 2v4M3 9h18M5 4h14a2 2 0 0 1 2 2v15H3V6a2 2 0 0 1 2-2Z"/></svg>Attendance</div>
                            <div class="phone-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/></svg>Scores</div>
                        </div>
                    </div>
                    <div class="phone-nav">
                        <span class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 9-8 9 8v10h-6v-7H9v7H3V11Z"/></svg>Home</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4M16 2v4M3 9h18M5 4h14a2 2 0 0 1 2 2v15H3V6a2 2 0 0 1 2-2Z"/></svg>My day</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/></svg>Messages</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg>More</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="role-strip">
        <div class="container role-strip-inner">
            <div class="role-strip-title">One secure login. The right access for every role.</div>
            <div class="role-name"><i></i>School Admin</div>
            <div class="role-name"><i></i>Teachers</div>
            <div class="role-name"><i></i>Accountants</div>
            <div class="role-name"><i></i>Students</div>
            <div class="role-name"><i></i>Parents</div>
            <div class="role-name"><i></i>Officers</div>
        </div>
    </div>

    <section class="section" id="features">
        <div class="container">
            <div class="section-head">
                <div class="section-kicker">Complete school operations</div>
                <h2 class="section-title">Everything your school needs. Nothing it doesn&rsquo;t.</h2>
                <p class="section-sub">Replace disconnected tools and manual processes with one integrated platform built around how Nigerian schools actually work.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card large">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/></svg></div>
                    <h3>Make informed decisions at a glance.</h3>
                    <p>Live dashboards connect attendance, academics, finance, and daily school operations in one reliable view.</p>
                    <div class="feature-tags"><span>Fee collection</span><span>Attendance</span><span>Risk flags</span><span>Performance</span></div>
                    <div class="mini-report">
                        <div class="mini-report-title">Collection Performance <span>+18.4%</span></div>
                        <div class="mini-bars"><i></i><i></i><i></i><i></i><i></i></div>
                    </div>
                </article>
                <article class="feature-card wide">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div>
                    <h3>Strict role-based access control</h3>
                    <p>Every user sees only the modules, records, and actions permitted for their role.</p>
                    <div class="feature-tags"><span>Permission-led</span><span>Audit-ready</span><span>Secure sessions</span></div>
                    <div class="security-orbit"><div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div></div>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10h18M5 6h14l2 4v10H3V10l2-4ZM8 14h3"/></svg></div>
                    <h3>Admissions &amp; Records</h3>
                    <p>Manage applications, student records, classes, subjects, transfers, and promotion from enrolment to graduation.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4M16 2v4M3 9h18M5 4h14a2 2 0 0 1 2 2v15H3V6a2 2 0 0 1 2-2Z"/></svg></div>
                    <h3>Attendance &amp; Timetable</h3>
                    <p>Track student and staff attendance, configure geo-fencing, manage timetables, and review detailed reports.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/></svg></div>
                    <h3>Fees, Billing &amp; Payroll</h3>
                    <p>Prepare fee bills, receive parent payments, track balances, manage expenses, and run staff payroll.</p>
                </article>
                <article class="feature-card half">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2.5ZM8 7h6M8 11h6"/></svg></div>
                    <h3>Academics, Exams &amp; Report Cards</h3>
                    <p>Plan lessons, enter subject scores, conduct CBT exams, publish results, and give students and parents a clear term-by-term performance breakdown.</p>
                </article>
                <article class="feature-card half">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 21v-2a7 7 0 0 1 14 0v2M12 11a4 4 0 1 0 0-8M3 7h4M5 5v4"/></svg></div>
                    <h3>Staff HR, Health &amp; Transport</h3>
                    <p>Coordinate staff records, leave, payroll, health visits, student medical records, routes, vehicles, drivers, and trip operations.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-dark" id="portals">
        <div class="container">
            <div class="section-head">
                <div class="section-kicker">Purpose-built experiences</div>
                <h2 class="section-title">The right tools for every member of your school community.</h2>
                <p class="section-sub">A unified platform with dedicated, permission-controlled experiences for every role.</p>
            </div>
            <div class="portal-grid">
                <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}" class="portal-card">
                    <div class="portal-top"><span class="portal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg></span><span class="portal-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span></div>
                    <h3>School Administrator</h3><p>Manage school records, staff attendance, classes, students, subjects, finance, settings, and reports.</p>
                    <span class="permission-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Administrative access</span>
                </a>
                <a href="{{ Route::has('student.login') ? route('student.login') : '#' }}" class="portal-card">
                    <div class="portal-top"><span class="portal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 10 10-5 10 5-10 5L2 10Z"/><path d="M6 12.5V17c3.5 2.5 8.5 2.5 12 0v-4.5"/></svg></span><span class="portal-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span></div>
                    <h3>Students</h3><p>View term results, report-card breakdowns, attendance, timetable, CBT exams, fees, and announcements.</p>
                    <span class="permission-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Personal records only</span>
                </a>
                <a href="{{ Route::has('parent.login') ? route('parent.login') : '#' }}" class="portal-card">
                    <div class="portal-top"><span class="portal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M17 11a4 4 0 0 1 4 4v6"/></svg></span><span class="portal-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span></div>
                    <h3>Parents &amp; Guardians</h3><p>Monitor each child, view results and attendance, receive updates, review bills, and make payments.</p>
                    <span class="permission-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Linked children only</span>
                </a>
                <div class="portal-card">
                    <div class="portal-top"><span class="portal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M19 8v6M16 11h6"/></svg></span></div>
                    <h3>Teachers &amp; Staff</h3><p>Manage assigned classes, take attendance, enter scores, view timetables, clock in, and follow daily work.</p>
                    <span class="permission-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Assignment-based access</span>
                </div>
                <div class="portal-card">
                    <div class="portal-top"><span class="portal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/></svg></span></div>
                    <h3>Accountants &amp; Officers</h3><p>Dedicated finance, payroll, admissions, transport, and health workspaces without unrelated academic permissions.</p>
                    <span class="permission-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Function-specific access</span>
                </div>
                <a href="{{ Route::has('agent.portal.login') ? route('agent.portal.login') : '#' }}" class="portal-card">
                    <div class="portal-top"><span class="portal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 12h8M12 8v8"/><circle cx="12" cy="12" r="9"/></svg></span><span class="portal-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span></div>
                    <h3>Agents &amp; Platform</h3><p>Register schools, manage referrals and commissions, oversee plans, and monitor platform operations.</p>
                    <span class="permission-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Platform-controlled access</span>
                </a>
            </div>
        </div>
    </section>

    <section class="section" id="testimonials">
        <div class="container mobile-product-grid">
            <div class="mobile-copy">
                <div class="section-kicker">EduCore Mobile</div>
                <h2 class="section-title">Your school keeps moving, wherever the day takes you.</h2>
                <p class="section-sub">A true Android application designed around mobile work&mdash;not a web page squeezed into a phone. Users can also open the complete web platform when they need it.</p>
                <div class="check-list">
                    <div class="check-item"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>Role-specific dashboards</div>
                    <div class="check-item"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>Attendance and score entry</div>
                    <div class="check-item"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>Parent fee payments</div>
                    <div class="check-item"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>Results and timetable access</div>
                    <div class="check-item"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>Staff clock-in with geo-fencing</div>
                    <div class="check-item"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>Direct access to the web version</div>
                </div>
                <a href="{{ route('app.download') }}" class="btn btn-navy">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.6 2.7 14 12 3.6 21.3c-.4-.3-.6-.8-.6-1.4V4.1c0-.6.2-1.1.6-1.4Zm11.6 10.4 2.5 2.2L6 22l9.2-8.9Zm3.8-3.4 2.2 1.2c1 .6 1 1.6 0 2.2L19 14.3 16.4 12 19 9.7ZM6 2l11.7 6.7-2.5 2.2L6 2Z"/></svg>
                    Download for Android
                </a>
            </div>
            <div class="mobile-showcase" aria-label="EduCore Android application preview">
                <div class="showcase-halo"></div>
                <div class="showcase-card one"><strong>92% attendance</strong><span>Live school overview</span></div>
                <div class="showcase-card two"><strong>Secure RBAC</strong><span>Only permitted actions appear</span></div>
                <div class="showcase-phone">
                    <div class="phone-notch"></div>
                    <div class="showcase-phone-head">
                        <div class="showcase-appbar"><div><img src="/brand/educore-icon.svg" alt="">EduCore Mobile</div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></div>
                        <div class="showcase-greeting">Good morning<strong>Welcome to EduCore</strong></div>
                    </div>
                    <div class="showcase-body">
                        <div class="showcase-rbac"><div class="showcase-rbac-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div><div><strong>School Administrator</strong><span>Full authorised school access</span></div></div>
                        <div class="showcase-title">Quick management</div>
                        <div class="showcase-tiles">
                            <div class="showcase-tile"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8M19 8v6M16 11h6"/></svg><strong>Students</strong><span>Add and manage records</span></div>
                            <div class="showcase-tile"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M19 8v6M16 11h6"/></svg><strong>Staff</strong><span>People and attendance</span></div>
                            <div class="showcase-tile"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13"/></svg><strong>Classes</strong><span>Structure and subjects</span></div>
                            <div class="showcase-tile"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/></svg><strong>Finance</strong><span>Billing and payroll</span></div>
                        </div>
                    </div>
                    <div class="showcase-bottom">
                        <span class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 9-8 9 8v10h-6v-7H9v7H3V11Z"/></svg>Home</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9M10 19V5M16 19v-7M22 19V3"/></svg>Reports</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/></svg>Messages</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg>More</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-soft" id="pricing">
        <div class="container">
            <div class="section-head">
                <div class="section-kicker">Straightforward pricing</div>
                <h2 class="section-title">All the features. One transparent model.</h2>
                <p class="section-sub">Start at no cost, then grow with predictable termly pricing. No feature tiers and no percentage discounts.</p>
            </div>
            <div class="pricing-shell">
                <div class="pricing-copy">
                    <div class="section-kicker">Every feature included</div>
                    <h2>Simple enough to understand in seconds.</h2>
                    <p>Every school gets access to the complete EduCore platform, including the web system and Android application.</p>
                    <div class="price-proof">
                        <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>No setup percentage or hidden add-ons</div>
                        <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>Web and Android access included</div>
                        <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>Strict RBAC across every supported role</div>
                    </div>
                </div>
                <div class="pricing-card">
                    <div class="pricing-label">Schools with up to 50 students</div>
                    <div class="free-price">Free <small>/ term</small></div>
                    <div class="pricing-divider"></div>
                    <div class="pricing-label">Schools above 50 students</div>
                    <div class="paid-price"><strong>&#8358;300</strong><span>per student / term</span></div>
                    <p class="pricing-note">One rate. All EduCore features included.</p>
                    <a href="{{ route('school.register') }}" class="btn btn-gold">Create Your School Account &rarr;</a>
                </div>
            </div>
        </div>
    </section>

    <section class="cta" id="download">
        <div class="container cta-card">
            <div class="cta-copy">
                <h2>Ready to run your school with clarity?</h2>
                <p>Start free with up to 50 students or take EduCore with you on Android.</p>
            </div>
            <div class="cta-actions">
                <a href="{{ route('school.register') }}" class="btn btn-gold">Get Started Free &rarr;</a>
                <a href="{{ route('app.download') }}" class="btn btn-dark">Download Android App</a>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-brand"><img src="/brand/educore-icon.svg" alt="EduCore"><span>Edu<b>Core</b></span></div>
                <p>The complete school management platform built for Nigerian K-12 institutions.</p>
                <div class="contact-list">
                    <div class="contact-row">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1.1.5 1.1 1.1V20c0 .6-.5 1.1-1.1 1.1C10.9 21.1 2.9 13.1 2.9 3.2c0-.6.5-1.1 1.1-1.1h3.5c.6 0 1.1.5 1.1 1.1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1l-2.4 3Z"/></svg>
                        <a href="tel:+2347065595768">07065595768</a>
                    </div>
                    <div class="contact-row">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.2-1.3c1.5.8 3.1 1.3 4.8 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2Zm4.5 12c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8.9-.1.2-.3.2-.6.1-1.8-.8-3-1.9-3.8-3.5-.1-.2 0-.4.1-.5l.6-.8c.1-.2 0-.4 0-.5L8.9 7c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.7.3-.8.8-1.1 1.8-.8 2.8.5 2.3 2.1 4.4 4.2 5.6 2.1 1.2 4.1 1.7 5.3.8.8-.6 1.1-1.5.9-2Z"/></svg>
                        <a href="https://wa.me/2347065595768" target="_blank" rel="noopener">WhatsApp: +2347065595768</a>
                    </div>
                    <div class="contact-row">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>
                        <a href="mailto:support@educoreng.online">support@educoreng.online</a>
                    </div>
                </div>
            </div>
            <div class="footer-column">
                <h4>Product</h4>
                <a href="#features">Features</a><a href="#pricing">Pricing</a><a href="#portals">Portals</a><a href="#testimonials">Reviews</a><a href="#download">Download</a>
            </div>
            <div class="footer-column">
                <h4>Portals</h4>
                <a href="{{ Route::has('admin.login') ? route('admin.login') : '#' }}">School Admin</a>
                <a href="{{ Route::has('student.login') ? route('student.login') : '#' }}">Student</a>
                <a href="{{ Route::has('parent.login') ? route('parent.login') : '#' }}">Parent</a>
                <a href="{{ Route::has('agent.portal.login') ? route('agent.portal.login') : '#' }}">Agent</a>
            </div>
            <div class="footer-column">
                <h4>Company</h4>
                <a href="mailto:support@educoreng.online">Contact</a><a href="mailto:support@educoreng.online">Support</a>
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a><a href="{{ route('legal.terms') }}">Terms</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p><span style="color:#fff;font-weight:900">Edu<span style="color:var(--gold-light)">Core</span></span> Education Technology &copy; {{ date('Y') }}. All rights reserved.</p>
            <div class="footer-links"><a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('legal.terms') }}">Terms</a><a href="mailto:support@educoreng.online">Contact</a></div>
        </div>
    </div>
</footer>
<script>
const menuToggle=document.getElementById('menuToggle');
const mobileMenu=document.getElementById('mobileMenu');
menuToggle.addEventListener('click',()=>{
    const open=mobileMenu.classList.toggle('open');
    menuToggle.setAttribute('aria-expanded',open?'true':'false');
    document.body.classList.toggle('menu-open',open);
});
mobileMenu.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{
    mobileMenu.classList.remove('open');
    menuToggle.setAttribute('aria-expanded','false');
    document.body.classList.remove('menu-open');
}));
</script>
</body>
</html>
