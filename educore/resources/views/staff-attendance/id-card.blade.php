<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Staff ID Card — {{ $staff->name }}</title>
<style>
:root{--navy:#071e45;--gold:#d79a21;--ink:#10233d;--muted:#667085;--rail:52px}
*{box-sizing:border-box}
body{margin:0;padding:26px;background:#eef1f5;color:var(--ink);font-family:Arial,Helvetica,sans-serif}
.toolbar{max-width:820px;margin:0 auto 22px;padding:13px 16px;background:#fff;border:1px solid #d9dee7;border-radius:12px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.toolbar strong{display:block;font-size:14px}.toolbar span{font-size:11px;color:#667085}.actions{display:flex;gap:8px;flex-wrap:wrap}.btn{padding:9px 14px;border-radius:8px;border:1px solid #d0d5dd;background:#fff;color:var(--navy);text-decoration:none;font:700 12px Arial,sans-serif;cursor:pointer}.btn.primary{background:var(--navy);border-color:var(--navy);color:#fff}
.cards{display:flex;justify-content:center;gap:34px;flex-wrap:wrap}.unit{text-align:center}.label{margin-bottom:8px;color:#667085;font:800 10px Arial,sans-serif;letter-spacing:.16em;text-transform:uppercase}
.card{width:324px;height:516px;position:relative;overflow:hidden;background:#fff;border:1px solid #d6dbe4;border-radius:15px;box-shadow:0 18px 38px rgba(7,30,69,.18);text-align:left}
.side-strip{position:absolute;right:0;top:0;bottom:0;width:47px;background:var(--navy);z-index:3}.side-gold{position:absolute;right:47px;top:0;bottom:0;width:5px;background:var(--gold);z-index:4}.vertical-copy{position:absolute;right:14px;top:64px;bottom:52px;width:18px;color:#fff;font-size:8px;font-weight:800;letter-spacing:2.2px;text-transform:uppercase;writing-mode:vertical-rl;transform:rotate(180deg);text-align:center;z-index:6}
.slot{position:absolute;top:10px;left:50%;transform:translateX(-50%);width:56px;height:13px;border-radius:10px;background:#edf0f4;box-shadow:inset 0 1px 4px rgba(7,30,69,.2);z-index:10}
.front-body,.back-body{position:absolute;left:0;right:var(--rail);top:0;bottom:0}
.school-logo{position:absolute;top:25px;left:50%;transform:translateX(-50%);width:68px;height:68px;object-fit:contain}.school-fallback{position:absolute;top:29px;left:50%;transform:translateX(-50%);width:62px;height:62px;border:2px solid var(--gold);border-radius:50%;display:grid;place-items:center;color:var(--navy);font-size:22px;font-weight:900;background:#fff9e8}.school-block{position:absolute;top:96px;left:13px;right:13px;text-align:center}.school-name{color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:14px;font-weight:800;line-height:1;text-transform:uppercase;max-height:30px;overflow:hidden}.motto{margin-top:2px;color:#5f6673;font-family:Arial,Helvetica,sans-serif;font-size:8px;line-height:1;font-style:italic;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.identity-stack{position:absolute;top:130px;left:13px;right:13px;text-align:center}.portrait-box{position:relative;width:122px;height:154px;margin:0 auto 7px;background:#f5f6f8;border:2px solid var(--gold);border-radius:8px;padding:4px;overflow:hidden}.portrait{width:100%;height:100%;object-fit:cover;object-position:top center;border-radius:5px;background:#e6e8ec}.fallback{height:100%;display:grid;place-items:center;background:#e1e4e8;color:var(--navy);font:800 52px Georgia,serif}.name{margin:0 auto;color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:18px;line-height:1.02;font-weight:800;max-height:39px;overflow:hidden}.role{margin:3px auto 0;color:var(--gold);font-family:Arial,Helvetica,sans-serif;font-size:10px;line-height:1.15;font-weight:800;text-transform:uppercase;letter-spacing:.25px;max-height:24px;overflow:hidden}
.details{position:absolute;top:365px;left:21px;right:21px;border-collapse:collapse;width:calc(100% - 42px)}.details td{padding:4px 0;font:600 9px/1.15 Arial,Helvetica,sans-serif;vertical-align:top}.details td:first-child{width:68px;color:var(--navy);font-weight:800}.details td:nth-child(2){width:9px;color:#98a2b3;text-align:center}.details td:last-child{color:#253858}.front-footer-rule{position:absolute;left:18px;right:18px;bottom:28px;height:1px;background:#e5c273}
.back-stack{position:absolute;top:46px;left:18px;right:18px;text-align:center}.back-title{color:var(--navy);font-family:Georgia,"Times New Roman",serif;font-size:15px;line-height:1.05;font-weight:800;letter-spacing:.2px;text-transform:uppercase}.back-rule{width:108px;height:2px;margin:11px auto 10px;background:var(--gold)}.back-property{margin:0 auto;color:#667085;font:400 8px/1.5 Arial,Helvetica,sans-serif;max-width:210px}.back-property strong{display:block;margin-top:5px;color:var(--navy);font-weight:700}.qr{position:relative;width:160px;height:160px;margin:14px auto 0;padding:9px;background:#fff;border:2px solid var(--gold);border-radius:8px}.qr img{width:100%;height:100%;display:block}
.powered{position:absolute;left:24px;right:24px;bottom:22px;border-top:1px solid #e5c273;padding-top:8px;text-align:center}.powered-label{display:block;color:#7a8494;font:600 7px/1 Arial,Helvetica,sans-serif;letter-spacing:.15px}.powered-brand{display:flex;align-items:center;justify-content:center;gap:5px;margin-top:5px}.powered-brand img{width:22px;height:22px;object-fit:contain;display:block}.brand-text{font:900 14px/1 Arial,Helvetica,sans-serif;letter-spacing:-.35px}.brand-edu{color:var(--navy)}.brand-core{color:var(--gold)}.brand-small{display:block;margin-top:3px;color:#8b94a3;font:700 5.5px/1 Arial,Helvetica,sans-serif;letter-spacing:.4px;text-transform:uppercase}.corner-line{position:absolute;right:var(--rail);bottom:0;width:165px;height:5px;background:var(--gold)}
@media(max-width:720px){body{padding:14px 8px}.toolbar{align-items:flex-start;flex-direction:column}.actions,.actions>*{width:100%;text-align:center}.cards{gap:22px}.card{width:min(324px,calc(100vw - 20px));height:516px}}
@media print{body{background:#fff;padding:0}.toolbar,.label{display:none}.cards{gap:12mm}.card{width:54mm;height:86mm;transform:none;box-shadow:none;border-radius:2mm}@page{size:A4 landscape;margin:12mm}}
</style>
</head>
<body>
@php
    $tenant=$staff->tenant;
    $schoolName=$tenant?->name??'EduCore School';
    $motto=$tenant?->motto?:'Excellence through education';
    $department=$staff->department_name?:($staff->currentWorkHistory?->department_name?:'School Administration');
    $schoolLogo=$tenant?->logo_path?asset('storage/'.preg_replace('#^storage/#','',ltrim($tenant->logo_path,'/'))):null;
    $educoreIcon=asset('brand/educore-icon-id.svg');
@endphp
<div class="toolbar"><div><strong>{{ $staff->name }} — Option 13 Staff ID</strong><span>CR80 portrait • attendance QR • school + EduCore branding</span></div><div class="actions">@if(auth()->user()->isAdmin())<a class="btn primary" href="{{ route('staff.id-card.download',$staff) }}">Download PDF</a>@endif<button class="btn" onclick="window.print()">Print card</button><a class="btn" href="{{ route('staff.show',$staff) }}">Staff profile</a></div></div>
<main class="cards">
<section class="unit"><div class="label">Front</div><article class="card front">
    <div class="side-strip"></div><div class="side-gold"></div><div class="vertical-copy">Identity • Service • Excellence</div><div class="slot"></div>
    <div class="front-body">
        @if($schoolLogo)<img class="school-logo" src="{{ $schoolLogo }}" alt="{{ $schoolName }} logo">@else<div class="school-fallback">{{ strtoupper(substr($schoolName,0,2)) }}</div>@endif
        <div class="school-block"><div class="school-name">{{ $schoolName }}</div><div class="motto">{{ $motto }}</div></div>
        <div class="identity-stack">
            <div class="portrait-box">@if($staff->passport_photo)<img class="portrait" src="{{ Storage::url($staff->passport_photo) }}" alt="{{ $staff->name }}">@else<div class="fallback">{{ strtoupper(substr($staff->name,0,1)) }}</div>@endif</div>
            <div class="name">{{ $staff->name }}</div><div class="role">{{ $staff->roleLabel() }}</div>
        </div>
        <table class="details"><tr><td>Staff ID</td><td>:</td><td>{{ $staff->staff_id?:'STAFF-'.str_pad($staff->id,4,'0',STR_PAD_LEFT) }}</td></tr><tr><td>Department</td><td>:</td><td>{{ $department }}</td></tr><tr><td>Phone</td><td>:</td><td>{{ $staff->phone?:'—' }}</td></tr></table>
        <div class="front-footer-rule"></div>
    </div><div class="corner-line"></div>
</article></section>
<section class="unit"><div class="label">Back</div><article class="card back">
    <div class="side-strip"></div><div class="side-gold"></div><div class="vertical-copy">People • Process • Progress</div><div class="slot"></div>
    <div class="back-body">
        <div class="back-stack">
            <div class="back-title">About This Card</div><div class="back-rule"></div>
            <div class="back-property">This personal QR identifies the card owner for EduCore staff attendance.<strong>If found, please return this card to {{ $schoolName }}.</strong></div>
            <div class="qr">@if($qrBase64)<img src="{{ $qrBase64 }}" alt="Attendance QR">@endif</div>
        </div>
        <div class="powered"><span class="powered-label">Powered by</span><div class="powered-brand"><img src="{{ $educoreIcon }}" alt="EduCore"><span class="brand-text"><span class="brand-edu">Edu</span><span class="brand-core">Core</span></span></div><span class="brand-small">One Platform. Every School Operation.</span></div>
    </div><div class="corner-line"></div>
</article></section>
</main>
</body></html>
