<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Staff ID Card — {{ $staff->name }}</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<style>
:root{--navy:#071E45;--navy-2:#0B326F;--gold:#D79A21;--gold-soft:#F6D88E;--ink:#101828;--muted:#667085;--line:#DCE3EC}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:#EEF2F7;color:var(--ink);font-family:"Plus Jakarta Sans",Inter,system-ui,sans-serif;min-height:100vh;padding:26px}
.toolbar{max-width:780px;margin:0 auto 22px;padding:13px 16px;background:#fff;border:1px solid var(--line);border-radius:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 10px 30px rgba(7,30,69,.08)}
.toolbar-copy strong{display:block;color:var(--navy);font-size:14px}.toolbar-copy span{font-size:11px;color:var(--muted)}
.toolbar-actions{display:flex;gap:8px;flex-wrap:wrap}.btn{border:0;border-radius:9px;padding:9px 14px;font:700 12px/1 inherit;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px}.btn-primary{background:var(--gold);color:var(--navy)}.btn-light{background:#F8FAFC;color:var(--navy);border:1px solid var(--line)}
.cards{display:flex;justify-content:center;align-items:flex-start;gap:34px;flex-wrap:wrap}
.card-unit{display:flex;flex-direction:column;align-items:center;gap:8px}.side-label{font-size:10px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#98A2B3}
.staff-card{width:324px;height:516px;border-radius:23px;overflow:hidden;position:relative;background:#fff;box-shadow:0 20px 52px rgba(7,30,69,.22);border:1px solid rgba(7,30,69,.10)}
.slot{position:absolute;z-index:9;left:50%;top:15px;transform:translateX(-50%);width:78px;height:19px;background:#F8FAFC;border-radius:12px;box-shadow:inset 0 2px 6px rgba(7,30,69,.18)}
.front-head{height:174px;padding:48px 26px 0;text-align:center;color:white;position:relative;background:linear-gradient(145deg,var(--navy),#04142F)}
.front-head::before{content:"";position:absolute;inset:0;background-image:linear-gradient(30deg,transparent 48%,rgba(255,255,255,.035) 49%,rgba(255,255,255,.035) 51%,transparent 52%),linear-gradient(150deg,transparent 48%,rgba(255,255,255,.03) 49%,rgba(255,255,255,.03) 51%,transparent 52%);background-size:58px 58px}
.school-title{position:relative;font-family:Georgia,serif;font-size:25px;font-weight:800;line-height:1.05;letter-spacing:.04em;text-transform:uppercase}.school-motto{position:relative;margin-top:5px;color:var(--gold-soft);font-size:9px;font-weight:700;letter-spacing:.06em}
.front-head::after{content:"";position:absolute;left:-12px;right:-12px;bottom:-23px;height:52px;background:#fff;border-top:7px solid var(--gold);transform:skewY(-6deg)}
.portrait-shell{position:absolute;z-index:4;top:133px;left:50%;transform:translateX(-50%);width:150px;height:150px;border-radius:50%;padding:7px;background:white;border:3px solid var(--navy);box-shadow:0 8px 18px rgba(7,30,69,.18)}
.portrait{width:100%;height:100%;border-radius:50%;object-fit:cover;object-position:top;background:#E9EDF3}
.portrait-fallback{width:100%;height:100%;border-radius:50%;display:grid;place-items:center;background:linear-gradient(150deg,#D5DAE2,#AEB5BF);color:white;font-size:52px;font-weight:900}
.front-body{padding:116px 25px 65px;text-align:center;height:342px;position:relative}
.staff-name{color:var(--navy);font-size:22px;line-height:1.08;font-weight:900;text-transform:uppercase;letter-spacing:.015em}.staff-role{color:var(--gold);margin-top:5px;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.12em}
.name-rule{width:156px;height:1px;background:var(--navy);margin:9px auto 13px;position:relative}.name-rule::after{content:"";position:absolute;width:9px;height:9px;border-radius:50%;background:var(--gold);border:3px solid #fff;left:50%;top:50%;transform:translate(-50%,-50%)}
.identity-list{text-align:left;display:grid;gap:9px}.identity-row{display:grid;grid-template-columns:33px 1fr;gap:10px;align-items:center}.identity-icon{width:31px;height:31px;border-radius:9px;background:var(--navy);display:grid;place-items:center;color:var(--gold);font-size:15px}.identity-label{font-size:8px;font-weight:900;color:var(--navy);letter-spacing:.08em;text-transform:uppercase}.identity-value{margin-top:1px;font-size:11px;color:var(--ink);font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.front-foot{position:absolute;left:0;right:0;bottom:0;height:51px;display:grid;place-items:center;color:white;background:linear-gradient(90deg,var(--navy),var(--navy-2));border-top:5px solid var(--gold);font-size:15px;font-weight:900;letter-spacing:.34em;padding-left:.34em}
.back{background:linear-gradient(145deg,#fff,#F4F6F9)}
.back::before{content:"";position:absolute;inset:0;background:linear-gradient(135deg,transparent 58%,rgba(7,30,69,.025) 58%)}
.back-brand{position:relative;padding:48px 24px 0;text-align:center;z-index:2}.back-logo{width:54px;height:54px;object-fit:contain}.back-school{font-family:Georgia,serif;color:var(--navy);font-size:22px;font-weight:800;text-transform:uppercase;line-height:1.05}.gold-rule{width:150px;height:2px;background:var(--gold);margin:8px auto 12px;position:relative}.gold-rule::after{content:"";position:absolute;width:8px;height:8px;border-radius:50%;background:var(--gold);left:50%;top:50%;transform:translate(-50%,-50%);border:3px solid white}
.qr-box{position:relative;z-index:3;width:154px;height:154px;margin:0 auto;padding:10px;background:white;border:3px solid var(--navy);border-radius:14px;box-shadow:0 6px 18px rgba(7,30,69,.12)}.qr-box img{width:100%;height:100%;display:block}.qr-fallback{width:100%;height:100%;display:grid;place-items:center;color:var(--muted);font-size:10px;text-align:center}
.scan-copy{position:relative;z-index:3;text-align:center;margin-top:8px;color:var(--navy);font-size:11px;font-weight:700}.scan-copy strong{display:block;color:var(--gold);font-size:13px;letter-spacing:.05em;text-transform:uppercase}
.return-panel{position:absolute;z-index:2;left:0;right:0;bottom:0;height:158px;padding:28px 28px 15px;color:white;text-align:center;background:linear-gradient(135deg,var(--navy),#04142F);clip-path:polygon(0 13%,50% 0,100% 13%,100% 100%,0 100%);border-top:0}
.return-panel::before{content:"";position:absolute;left:0;right:0;top:5px;height:5px;background:var(--gold);clip-path:polygon(0 60%,50% 0,100% 60%,100% 100%,0 100%)}
.return-label{font-size:9px;color:#D5DFEC}.return-school{font-size:12px;font-weight:900;margin:2px 0}.return-address{font-size:9px;color:#C6D1E0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.contacts{display:grid;gap:3px;margin-top:8px;text-align:left}.contact{font-size:8.5px;color:#F4F7FB;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.contact b{color:var(--gold);display:inline-block;width:18px;text-align:center}.property{border-top:1px solid rgba(255,255,255,.32);margin-top:8px;padding-top:6px;font-size:7px;color:#C6D1E0;line-height:1.35}
@media(max-width:720px){body{padding:14px 10px}.toolbar{align-items:flex-start;flex-direction:column}.toolbar-actions,.toolbar-actions>*{width:100%}.cards{gap:22px}.staff-card{width:min(324px,calc(100vw - 24px));height:auto;aspect-ratio:324/516}.front-head{height:33.72%}.front-body{height:66.28%}}
@media print{body{background:#fff;padding:0}.toolbar,.side-label{display:none!important}.cards{gap:12mm;align-items:flex-start}.staff-card{box-shadow:none;width:54mm;height:86mm;border-radius:4mm}.card-unit{break-inside:avoid}@page{size:A4 landscape;margin:12mm}}
</style>
</head>
<body>
@php
    $tenant = $staff->tenant;
    $schoolName = $tenant?->name ?? 'EduCore School';
    $motto = $tenant?->motto ?: 'Excellence through education';
    $department = $staff->department_name ?: 'School Administration';
    $joinDate = optional($staff->employment_started_at ?? $staff->created_at)->format('d M Y');
    $logo = $tenant?->logo_path
        ? asset('storage/' . preg_replace('#^storage/#', '', ltrim($tenant->logo_path, '/')))
        : '/brand/educore-icon.svg';
    $website = parse_url(config('app.url'), PHP_URL_HOST) ?: 'educoreng.online';
@endphp
<div class="toolbar">
    <div class="toolbar-copy"><strong>{{ $staff->name }} — staff identity card</strong><span>Print the front and back at CR80 portrait size.</span></div>
    <div class="toolbar-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print card</button>
        <a href="{{ route('staff-attendance.my') }}" class="btn btn-light">Back to attendance</a>
    </div>
</div>
<main class="cards">
    <section class="card-unit">
        <div class="side-label">Front</div>
        <article class="staff-card front">
            <div class="slot"></div>
            <header class="front-head">
                <div class="school-title">{{ $schoolName }}</div>
                <div class="school-motto">{{ $motto }}</div>
            </header>
            <div class="portrait-shell">
                @if($staff->passport_photo)
                    <img class="portrait" src="{{ Storage::url($staff->passport_photo) }}" alt="{{ $staff->name }}">
                @else
                    <div class="portrait-fallback">{{ strtoupper(substr($staff->name,0,1)) }}</div>
                @endif
            </div>
            <div class="front-body">
                <div class="staff-name">{{ $staff->name }}</div>
                <div class="staff-role">{{ $staff->roleLabel() }}</div>
                <div class="name-rule"></div>
                <div class="identity-list">
                    <div class="identity-row"><div class="identity-icon">▣</div><div><div class="identity-label">Staff ID</div><div class="identity-value">{{ $staff->staff_id ?: 'STAFF-' . str_pad($staff->id, 4, '0', STR_PAD_LEFT) }}</div></div></div>
                    <div class="identity-row"><div class="identity-icon">◆</div><div><div class="identity-label">Department</div><div class="identity-value">{{ $department }}</div></div></div>
                    <div class="identity-row"><div class="identity-icon">▦</div><div><div class="identity-label">Date of joining</div><div class="identity-value">{{ $joinDate }}</div></div></div>
                </div>
            </div>
            <footer class="front-foot">STAFF</footer>
        </article>
    </section>
    <section class="card-unit">
        <div class="side-label">Back</div>
        <article class="staff-card back">
            <div class="slot"></div>
            <header class="back-brand">
                <img class="back-logo" src="{{ $logo }}" alt="">
                <div class="back-school">{{ $schoolName }}</div>
                <div class="gold-rule"></div>
            </header>
            <div class="qr-box">
                @if($qrBase64)
                    <img src="{{ $qrBase64 }}" alt="Staff attendance QR code">
                @else
                    <div id="qr-back" class="qr-fallback">Attendance QR code</div>
                @endif
            </div>
            <div class="scan-copy">Scan this QR code for<strong>Staff attendance</strong></div>
            <div class="return-panel">
                <div class="return-label">If found, please return to:</div>
                <div class="return-school">{{ $schoolName }}</div>
                <div class="return-address">{{ $tenant?->address ?: 'School administration office' }}</div>
                <div class="contacts">
                    <div class="contact"><b>☎</b>{{ $tenant?->phone ?: 'Contact the school office' }}</div>
                    <div class="contact"><b>✉</b>{{ $tenant?->email ?: $staff->email }}</div>
                    <div class="contact"><b>●</b>{{ $website }}</div>
                </div>
                <div class="property">This card is the property of {{ $schoolName }}. It must be returned on request or at the end of employment.</div>
            </div>
        </article>
    </section>
</main>
@if(!$qrBase64)
<script>
(function(){
    const target=document.getElementById('qr-back');
    const url={{ json_encode($url ?? '') }};
    if(target&&url){target.innerHTML='<img src="https://chart.googleapis.com/chart?cht=qr&chs=220x220&choe=UTF-8&chl='+encodeURIComponent(url)+'" alt="Attendance QR">';}
})();
</script>
@endif
</body>
</html>
