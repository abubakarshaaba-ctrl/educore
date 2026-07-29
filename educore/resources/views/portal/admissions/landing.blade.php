<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admissions — {{ $tenant->name }}</title>
<meta name="description" content="Apply for admission to {{ $tenant->name }} and track your application online.">
<link rel="icon" href="/brand/favicon.svg">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#071E45;--navy2:#0B2C61;--gold:#D79A21;--gold2:#F2C35B;--ink:#101828;--muted:#667085;--page:#F4F7FB;--line:#DFE6F0;--green:#16794B;--white:#fff;--radius:22px}
html{scroll-behavior:smooth}
body{font-family:"Segoe UI",Arial,sans-serif;color:var(--ink);background:var(--page);line-height:1.55;overflow-x:hidden}
a{text-decoration:none}
.container{width:min(1180px,calc(100% - 40px));margin:auto}
.top-shell{position:relative;color:white;background:
radial-gradient(circle at 81% 18%,rgba(242,195,91,.18),transparent 25%),
linear-gradient(125deg,#03132D 0%,var(--navy) 58%,#0A326F 100%);overflow:hidden}
.top-shell:before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:54px 54px;mask-image:linear-gradient(to bottom,black,transparent 85%)}
.nav{height:84px;display:flex;align-items:center;justify-content:space-between;gap:24px;position:relative;z-index:2;border-bottom:1px solid rgba(255,255,255,.1)}
.brand{display:flex;align-items:center;gap:12px;min-width:0}
.brand-logo{width:46px;height:46px;border-radius:13px;background:white;border:1px solid rgba(255,255,255,.35);display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.18)}
.brand-logo img{width:100%;height:100%;object-fit:contain;padding:4px}
.brand-name{font-size:15px;font-weight:800;color:white;white-space:nowrap;max-width:280px;overflow:hidden;text-overflow:ellipsis}
.brand-caption{font-size:9px;color:rgba(255,255,255,.58);text-transform:uppercase;letter-spacing:.15em;margin-top:2px}
.nav-links{display:flex;align-items:center;gap:10px}
.nav-link{padding:9px 12px;color:rgba(255,255,255,.75);font-size:12px;font-weight:700}
.nav-link:hover{color:white}
.nav-cta{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;padding:11px 16px;background:var(--gold);color:var(--navy);font-size:12px;font-weight:800;box-shadow:0 8px 20px rgba(215,154,33,.2)}
.hero{min-height:620px;padding:74px 0 96px;display:grid;grid-template-columns:1.03fr .97fr;gap:72px;align-items:center;position:relative;z-index:1}
.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:7px 11px;border:1px solid rgba(242,195,91,.36);background:rgba(215,154,33,.1);border-radius:30px;color:var(--gold2);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em}
.eyebrow i{width:7px;height:7px;border-radius:50%;background:#34D399;box-shadow:0 0 0 4px rgba(52,211,153,.12)}
.hero h1{font-size:clamp(40px,5vw,67px);line-height:1.02;letter-spacing:-.05em;margin:21px 0 22px;color:white;max-width:680px}
.hero h1 span{color:var(--gold2)}
.lead{font-size:17px;line-height:1.75;color:rgba(255,255,255,.7);max-width:590px}
.hero-actions{display:flex;gap:11px;flex-wrap:wrap;margin-top:30px}
.btn{min-height:50px;padding:0 20px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;gap:9px;font-size:13px;font-weight:800;transition:.18s ease}
.btn:hover{transform:translateY(-2px)}
.btn-primary{background:var(--gold);color:var(--navy);box-shadow:0 12px 28px rgba(215,154,33,.23)}
.btn-outline{border:1px solid rgba(255,255,255,.27);color:white;background:rgba(255,255,255,.06)}
.btn svg,.nav-cta svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.trust-row{display:flex;gap:24px;flex-wrap:wrap;margin-top:34px;color:rgba(255,255,255,.58);font-size:11px;font-weight:600}
.trust-item{display:flex;align-items:center;gap:7px}.trust-item b{width:20px;height:20px;border-radius:50%;display:grid;place-items:center;background:rgba(22,121,75,.22);color:#6EE7B7}
.portal-card{position:relative;background:white;border-radius:26px;padding:8px;box-shadow:0 35px 80px rgba(0,0,0,.3);transform:rotate(1.2deg)}
.portal-card:before{content:"";position:absolute;width:140px;height:140px;right:-42px;top:-42px;border:1px solid rgba(242,195,91,.25);border-radius:50%}
.portal-inner{border:1px solid var(--line);border-radius:20px;overflow:hidden;background:#FBFCFE}
.portal-head{padding:22px 22px 18px;background:var(--navy);color:white;position:relative;overflow:hidden}
.portal-head:after{content:"";position:absolute;width:160px;height:160px;border:30px solid rgba(215,154,33,.08);border-radius:50%;right:-70px;top:-90px}
.portal-status{display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;z-index:1}
.portal-status small{color:rgba(255,255,255,.6);font-size:9px;text-transform:uppercase;letter-spacing:.12em;font-weight:800}
.open-badge{display:inline-flex;align-items:center;gap:6px;border-radius:20px;padding:5px 9px;background:rgba(22,121,75,.25);color:#A7F3D0;font-size:9px;font-weight:800;text-transform:uppercase}
.open-badge.closed{background:rgba(239,68,68,.2);color:#FECACA}
.portal-head h2{font-size:23px;line-height:1.18;margin:16px 0 7px;position:relative;z-index:1}
.portal-head p{font-size:11px;color:rgba(255,255,255,.65);position:relative;z-index:1}
.portal-body{padding:20px}
.progress-label{display:flex;justify-content:space-between;font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:800;margin-bottom:9px}
.progress{height:7px;background:#E8EDF5;border-radius:10px;overflow:hidden}.progress span{display:block;width:34%;height:100%;background:linear-gradient(90deg,var(--gold),var(--gold2));border-radius:10px}
.step-list{display:grid;gap:10px;margin-top:18px}
.step{display:grid;grid-template-columns:34px 1fr auto;gap:11px;align-items:center;padding:11px;border:1px solid var(--line);border-radius:12px;background:white}
.step-no{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:#FEF7E7;color:#9B6807;font-size:11px;font-weight:900}
.step strong{display:block;color:var(--navy);font-size:12px}.step p{color:var(--muted);font-size:9px;margin-top:2px}.step em{font-style:normal;color:#98A2B3;font-size:16px}
.portal-note{margin-top:13px;padding:12px;border-radius:11px;background:#EFF6FF;color:#1D4ED8;font-size:10px;line-height:1.55}
.float-card{position:absolute;right:-24px;bottom:46px;background:white;border-radius:13px;padding:12px 14px;box-shadow:0 16px 38px rgba(0,0,0,.18);transform:rotate(-2deg);display:flex;align-items:center;gap:10px}
.float-icon{width:34px;height:34px;border-radius:9px;background:#ECFDF3;color:var(--green);display:grid;place-items:center;font-weight:900}.float-card strong{display:block;color:var(--navy);font-size:11px}.float-card span{font-size:9px;color:var(--muted)}
.metric-band{position:relative;z-index:3;margin-top:-42px}
.metrics{background:white;border:1px solid var(--line);border-radius:18px;box-shadow:0 18px 48px rgba(7,30,69,.09);display:grid;grid-template-columns:repeat(4,1fr);overflow:hidden}
.metric{padding:22px 24px;border-right:1px solid var(--line)}.metric:last-child{border:0}.metric strong{display:block;color:var(--navy);font-size:23px;letter-spacing:-.04em}.metric span{display:block;color:var(--muted);font-size:10px;margin-top:3px;text-transform:uppercase;letter-spacing:.08em;font-weight:700}
.section{padding:96px 0}.section-kicker{color:#9B6807;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.14em}.section-title{font-size:clamp(29px,3vw,43px);line-height:1.1;letter-spacing:-.04em;color:var(--navy);margin:10px 0 13px}.section-lead{color:var(--muted);font-size:15px;max-width:620px}
.process-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:38px}
.process-card{position:relative;padding:25px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 10px 28px rgba(7,30,69,.04)}
.process-card:before{content:"";position:absolute;left:0;top:28px;width:4px;height:40px;background:var(--gold);border-radius:0 5px 5px 0}
.process-no{font-size:10px;color:#9B6807;font-weight:900;letter-spacing:.1em}.process-card h3{color:var(--navy);font-size:17px;margin:13px 0 8px}.process-card p{font-size:12px;color:var(--muted);line-height:1.7}
.info-section{padding:0 0 96px}.info-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:24px}
.info-card{background:white;border:1px solid var(--line);border-radius:22px;padding:30px}
.info-card.dark{background:var(--navy);border-color:var(--navy);color:white;position:relative;overflow:hidden}.info-card.dark:after{content:"";position:absolute;width:220px;height:220px;border:44px solid rgba(215,154,33,.08);border-radius:50%;right:-100px;bottom:-110px}
.info-card h2{font-size:25px;line-height:1.15;color:var(--navy);margin-bottom:10px}.info-card.dark h2{color:white}.info-card>p{color:var(--muted);font-size:12px;line-height:1.75}.info-card.dark>p{color:rgba(255,255,255,.62);position:relative;z-index:1}
.requirements{display:grid;gap:9px;margin-top:20px}.requirement{display:flex;gap:10px;align-items:flex-start;font-size:12px;color:#344054}.check{width:20px;height:20px;border-radius:6px;background:#ECFDF3;color:var(--green);display:grid;place-items:center;flex:0 0 auto;font-size:11px;font-weight:900}
.classes{display:flex;flex-wrap:wrap;gap:8px;margin-top:22px;position:relative;z-index:1}.class-chip{border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.07);color:white;border-radius:9px;padding:8px 11px;font-size:10px;font-weight:700}
.deadline{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:22px;position:relative;z-index:1}.deadline-box{padding:13px;border-radius:11px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)}.deadline-box small{display:block;color:rgba(255,255,255,.5);font-size:8px;text-transform:uppercase;letter-spacing:.1em}.deadline-box strong{display:block;color:var(--gold2);font-size:12px;margin-top:4px}
.cta{padding:0 0 92px}.cta-box{background:linear-gradient(120deg,var(--navy),#0B3470);border-radius:24px;padding:44px;display:flex;align-items:center;justify-content:space-between;gap:30px;overflow:hidden;position:relative}.cta-box:after{content:"";position:absolute;width:260px;height:260px;border:1px solid rgba(242,195,91,.22);border-radius:50%;right:-60px;top:-140px}.cta-box h2{color:white;font-size:30px;letter-spacing:-.035em}.cta-box p{color:rgba(255,255,255,.6);font-size:13px;margin-top:7px}.cta-actions{display:flex;gap:10px;position:relative;z-index:1;flex-shrink:0}
.closed-btn{opacity:.55;cursor:not-allowed}
footer{background:#031126;color:rgba(255,255,255,.54);padding:32px 0}.footer-row{display:flex;align-items:center;justify-content:space-between;gap:24px}.footer-brand{font-size:12px}.footer-brand strong{color:white}.footer-links{display:flex;gap:18px;flex-wrap:wrap}.footer-links a{color:rgba(255,255,255,.58);font-size:10px}.powered{font-size:9px;text-transform:uppercase;letter-spacing:.1em}
@media(max-width:960px){.hero{grid-template-columns:1fr;gap:48px;padding-top:54px}.hero-copy{text-align:center}.lead{margin:auto}.hero-actions,.trust-row{justify-content:center}.portal-card{max-width:600px;margin:auto;transform:none}.info-grid{grid-template-columns:1fr}.process-grid{grid-template-columns:1fr}.metrics{grid-template-columns:1fr 1fr}.metric:nth-child(2){border-right:0}.metric:nth-child(-n+2){border-bottom:1px solid var(--line)}}
@media(max-width:700px){.container{width:min(100% - 28px,1180px)}.nav{height:72px}.nav-link{display:none}.brand-name{max-width:170px}.hero{padding:48px 0 80px}.hero h1{font-size:42px}.lead{font-size:14px}.float-card{display:none}.metrics{grid-template-columns:1fr 1fr}.metric{padding:17px}.metric strong{font-size:18px}.section{padding:72px 0}.info-section{padding-bottom:72px}.info-card{padding:22px}.cta{padding-bottom:70px}.cta-box{padding:28px;display:block}.cta-actions{margin-top:22px;flex-wrap:wrap}.footer-row{align-items:flex-start;flex-direction:column}.deadline{grid-template-columns:1fr}.portal-head,.portal-body{padding:17px}.step{grid-template-columns:31px 1fr}.step em{display:none}}
@media(max-width:430px){.brand-caption{display:none}.nav-cta{padding:10px 12px}.hero h1{font-size:36px}.hero-actions .btn{width:100%}.metrics{grid-template-columns:1fr}.metric{border-right:0!important;border-bottom:1px solid var(--line)!important}.metric:last-child{border-bottom:0!important}.cta-actions .btn{width:100%}}
</style>
</head>
<body>
@php
    $open = $settings->isCurrentlyOpen();
    $logoPath = $tenant->logo_path ? asset('storage/'.ltrim($tenant->logo_path,'/')) : asset('brand/educore-icon.svg');
    $requirements = collect(preg_split('/\r\n|\r|\n/', (string) $settings->requirements))->map(fn($line)=>trim($line))->filter();
@endphp

<div class="top-shell">
    <div class="container">
        <nav class="nav">
            <a href="{{ url('/apply') }}" class="brand">
                <span class="brand-logo"><img src="{{ $logoPath }}" alt="{{ $tenant->name }} logo"></span>
                <span><span class="brand-name">{{ $tenant->name }}</span><span class="brand-caption">Online Admissions Portal</span></span>
            </a>
            <div class="nav-links">
                <a href="#process" class="nav-link">How it works</a>
                <a href="#requirements" class="nav-link">Requirements</a>
                <a href="{{ url('/apply/status') }}" class="nav-link">Track application</a>
                @if($open)
                    <a href="{{ url('/apply/form') }}" class="nav-cta">Apply now <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
                @else
                    <span class="nav-cta closed-btn">Admissions closed</span>
                @endif
            </div>
        </nav>

        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow"><i></i>{{ $open ? 'Applications are now open' : 'Admissions portal notice' }}</span>
                <h1>Start a brighter <span>school journey.</span></h1>
                <p class="lead">{{ $settings->welcome_message ?: "A simple, secure way to apply to {$tenant->name}. Complete the form online, upload the required documents and follow every decision from one place." }}</p>
                <div class="hero-actions">
                    @if($open)
                    <a href="{{ url('/apply/form') }}" class="btn btn-primary">Begin application <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
                    @else
                    <span class="btn btn-primary closed-btn">Applications currently closed</span>
                    @endif
                    <a href="{{ url('/apply/status') }}" class="btn btn-outline"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>Check admission status</a>
                </div>
                <div class="trust-row">
                    <span class="trust-item"><b>✓</b>Secure application</span>
                    <span class="trust-item"><b>✓</b>Instant reference number</span>
                    <span class="trust-item"><b>✓</b>Online status tracking</span>
                </div>
            </div>

            <div class="portal-card">
                <div class="portal-inner">
                    <div class="portal-head">
                        <div class="portal-status"><small>{{ $settings->academic_year ?: date('Y').'/'.(date('Y')+1) }} Admission Cycle</small><span class="open-badge {{ $open ? '' : 'closed' }}">{{ $open ? '● Open' : '● Closed' }}</span></div>
                        <h2>Your application workspace</h2>
                        <p>One guided process from first detail to final decision.</p>
                    </div>
                    <div class="portal-body">
                        <div class="progress-label"><span>Application journey</span><span>3 guided steps</span></div>
                        <div class="progress"><span></span></div>
                        <div class="step-list">
                            <div class="step"><span class="step-no">01</span><span><strong>Applicant details</strong><p>Student, guardian and class information</p></span><em>›</em></div>
                            <div class="step"><span class="step-no">02</span><span><strong>Supporting documents</strong><p>Upload clear and verified records</p></span><em>›</em></div>
                            <div class="step"><span class="step-no">03</span><span><strong>Review and submit</strong><p>Receive your unique application number</p></span><em>›</em></div>
                        </div>
                        <div class="portal-note">Keep your application number and guardian phone number safe. You will use both to track progress securely.</div>
                    </div>
                </div>
                <div class="float-card"><span class="float-icon">✓</span><span><strong>Track every update</strong><span>No repeated school visits required</span></span></div>
            </div>
        </section>
    </div>
</div>

<div class="metric-band">
    <div class="container metrics">
        <div class="metric"><strong>{{ number_format($stats['total_applied']) }}</strong><span>Applications this year</span></div>
        <div class="metric"><strong>{{ number_format($stats['admitted']) }}</strong><span>Offers issued</span></div>
        <div class="metric"><strong>{{ $classLevels->count() }}</strong><span>Available class levels</span></div>
        <div class="metric"><strong>{{ $settings->application_fee > 0 ? '₦'.number_format($settings->application_fee) : 'Free' }}</strong><span>Application fee</span></div>
    </div>
</div>

<section class="section" id="process">
    <div class="container">
        <div class="section-kicker">A clearer admissions process</div>
        <h2 class="section-title">Apply confidently in three steps.</h2>
        <p class="section-lead">The portal guides families through the information the school needs and keeps the application accessible after submission.</p>
        <div class="process-grid">
            <article class="process-card"><span class="process-no">STEP 01</span><h3>Complete the form</h3><p>Enter accurate student and guardian details, choose a class level and provide previous-school information where applicable.</p></article>
            <article class="process-card"><span class="process-no">STEP 02</span><h3>Attach documents</h3><p>Upload the requested records in PDF, JPG or PNG format. Your entries remain linked to one secure application.</p></article>
            <article class="process-card"><span class="process-no">STEP 03</span><h3>Track the decision</h3><p>Use the application number and guardian phone number to view shortlisting, interview and admission updates online.</p></article>
        </div>
    </div>
</section>

<section class="info-section" id="requirements">
    <div class="container info-grid">
        <article class="info-card">
            <div class="section-kicker">Prepare before you apply</div>
            <h2>Application requirements</h2>
            <p>Have accurate records and clear digital copies ready. This helps the school review your application without unnecessary delay.</p>
            <div class="requirements">
                @if($requirements->isNotEmpty())
                    @foreach($requirements as $requirement)<div class="requirement"><span class="check">✓</span><span>{{ ltrim($requirement,'-• ') }}</span></div>@endforeach
                @else
                    <div class="requirement"><span class="check">✓</span><span>Student and guardian contact information</span></div>
                    @if($settings->require_passport)<div class="requirement"><span class="check">✓</span><span>Recent passport photograph</span></div>@endif
                    @if($settings->require_birth_cert)<div class="requirement"><span class="check">✓</span><span>Birth certificate or age declaration</span></div>@endif
                    @if($settings->require_report_card)<div class="requirement"><span class="check">✓</span><span>Most recent school report card</span></div>@endif
                    <div class="requirement"><span class="check">✓</span><span>A working phone number for secure status verification</span></div>
                @endif
            </div>
        </article>
        <article class="info-card dark">
            <div class="section-kicker" style="color:var(--gold2)">Current admission cycle</div>
            <h2>Classes accepting applications</h2>
            <p>Select the intended level in the form. The school will confirm placement after reviewing the application and any required assessment.</p>
            <div class="classes">
                @forelse($classLevels as $level)<span class="class-chip">{{ $level->name }}</span>@empty<span class="class-chip">Contact the school for placement</span>@endforelse
            </div>
            <div class="deadline">
                <div class="deadline-box"><small>Applications open</small><strong>{{ $settings->opens_on?->format('d M Y') ?? 'School calendar' }}</strong></div>
                <div class="deadline-box"><small>Closing date</small><strong>{{ $settings->closes_on?->format('d M Y') ?? 'To be announced' }}</strong></div>
            </div>
        </article>
    </div>
</section>

<section class="cta">
    <div class="container cta-box">
        <div><h2>{{ $open ? 'Ready to submit an application?' : 'Need an admissions update?' }}</h2><p>{{ $settings->footer_note ?: 'Start online or use the secure tracker to check an existing application.' }}</p></div>
        <div class="cta-actions">
            @if($open)<a href="{{ url('/apply/form') }}" class="btn btn-primary">Apply now</a>@endif
            <a href="{{ url('/apply/status') }}" class="btn btn-outline">Track application</a>
        </div>
    </div>
</section>

<footer>
    <div class="container footer-row">
        <div class="footer-brand"><strong>{{ $tenant->name }}</strong><br>{{ $tenant->address }} @if($tenant->phone) · {{ $tenant->phone }} @endif</div>
        <div class="footer-links"><a href="{{ url('/apply') }}">Admissions</a><a href="{{ url('/apply/status') }}">Check status</a><a href="{{ url('/login') }}">Staff login</a></div>
        <div class="powered">Securely powered by EduCore</div>
    </div>
</footer>
</body>
</html>
