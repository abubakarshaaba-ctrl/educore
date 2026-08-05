<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff identity cards</title>
<style>
@page{size:A4 landscape;margin:12mm}
*{box-sizing:border-box}
body{margin:0;font-family:DejaVu Serif,Georgia,serif;color:#14233a;background:#fff}
.sheet{height:186mm;position:relative;text-align:center;page-break-after:always;padding-top:42mm}
.sheet:last-child{page-break-after:auto}
.card{display:inline-block;vertical-align:top;width:54mm;height:86mm;margin:0 7mm;position:relative;overflow:hidden;background:#fbf7ee;border:0.35mm solid #c9a35b;border-radius:2.4mm;text-align:left}
.inner-frame{position:absolute;inset:2mm;border:.18mm solid #d2b273;z-index:20;pointer-events:none}
.front-top{position:absolute;left:0;right:0;top:0;height:20mm;background:#10233d;color:#fff;text-align:center;padding:7.3mm 7mm 0;overflow:hidden}
.wing{position:absolute;top:0;width:19mm;height:22mm;background:#711c2b;z-index:2}
.wing.left{left:-9mm;transform:skewX(38deg)}.wing.right{right:-9mm;transform:skewX(-38deg)}
.gold-diagonal{position:absolute;top:10mm;width:25mm;height:.45mm;background:#d2aa56;z-index:3}.gold-diagonal.left{left:-2mm;transform:rotate(47deg)}.gold-diagonal.right{right:-2mm;transform:rotate(-47deg)}
.school{position:relative;z-index:5;font-size:4.2mm;line-height:.95;text-align:center;text-transform:uppercase;letter-spacing:.55mm;font-weight:bold;max-height:9mm;overflow:hidden}
.motto{position:relative;z-index:5;margin-top:1mm;color:#d9b76d;font-size:1.55mm;text-align:center;font-style:italic;white-space:nowrap;overflow:hidden}
.portrait-box{position:absolute;z-index:8;top:18mm;left:50%;margin-left:-13.5mm;width:27mm;height:33mm;background:#f3eee3;border:.35mm solid #b98e3d;padding:1mm}
.portrait-box img{width:100%;height:100%;object-fit:cover;object-position:top}.photo-fallback{height:100%;background:#d9d2c5;text-align:center;padding-top:10mm;font-size:10mm;color:#10233d;font-weight:bold}
.front-name{position:absolute;top:52.8mm;left:3.2mm;right:3.2mm;text-align:center;text-transform:uppercase;font-size:3.7mm;line-height:1;font-weight:bold;letter-spacing:.2mm;white-space:nowrap;overflow:hidden}
.front-role{position:absolute;top:57.4mm;left:4mm;right:4mm;text-align:center;text-transform:uppercase;font-size:1.8mm;color:#7a2633;letter-spacing:.55mm;font-weight:bold;white-space:nowrap;overflow:hidden}
.details{position:absolute;top:62.2mm;left:7mm;right:7mm;border-collapse:collapse;font-family:DejaVu Sans,sans-serif}
.details td{border-bottom:.16mm solid #cdb37f;padding:1.05mm 0;font-size:1.7mm}.details td:first-child{width:16mm;color:#762332;font-size:1.45mm;font-weight:bold;letter-spacing:.18mm;text-transform:uppercase}.details td:last-child{border-left:.16mm solid #cdb37f;padding-left:2mm;color:#17233a}
.front-foot{position:absolute;left:0;right:0;bottom:0;height:7.5mm;background:#761d2c;border-top:.5mm solid #caa04e;color:#e8ca82;text-align:center;padding-top:2.1mm;font-size:2.7mm;letter-spacing:1.7mm;font-weight:bold}
.back{background:#fcf8ef;text-align:center}.back .inner-frame{inset:2mm;border:.18mm solid #c9a35b}.back-frame-two{position:absolute;inset:3mm;border:.12mm solid #d8c394}
.back-school{position:absolute;top:8mm;left:5mm;right:5mm;text-transform:uppercase;font-size:4.3mm;line-height:.95;letter-spacing:.5mm;font-weight:bold;text-align:center;max-height:10mm;overflow:hidden}
.ornament{position:absolute;top:19.3mm;left:18mm;right:18mm;height:.3mm;background:#c59b4d}.ornament:after{content:'◆';position:absolute;left:50%;top:-1.25mm;margin-left:-1.2mm;color:#c59b4d;background:#fcf8ef;font-size:2mm;padding:0 .5mm}
.qr{position:absolute;top:23mm;left:50%;margin-left:-13mm;width:26mm;height:26mm;border:.35mm solid #172a47;background:#fff;padding:1.4mm}.qr img{width:100%;height:100%}
.scan{position:absolute;top:50.5mm;left:5mm;right:5mm;text-align:center;font-size:1.75mm;font-style:italic;color:#762332}
.return{position:absolute;top:55mm;left:6mm;right:6mm;text-align:center;font-size:1.55mm;line-height:1.35}.return strong{display:block;font-size:1.85mm}.contacts{margin-top:1.1mm}
.property{position:absolute;top:67mm;left:7mm;right:7mm;text-align:center;font-size:1.45mm;line-height:1.25;text-transform:uppercase;color:#762332;font-weight:bold}
.signature{position:absolute;left:12mm;right:12mm;bottom:5.5mm;height:9mm;text-align:center;border-bottom:.2mm solid #9e7a3b}.signature img{height:6mm;max-width:27mm}.signature-label{position:absolute;left:0;right:0;bottom:3.2mm;text-align:center;font-size:1.45mm;font-style:italic}
</style>
</head>
<body>
@foreach($cards as $card)
@php
    $staff = $card['staff']; $tenant = $card['tenant'];
    $schoolName = $tenant?->name ?? 'EduCore School';
    $motto = $tenant?->motto ?: 'Excellence through education';
@endphp
<section class="sheet">
    <article class="card front">
        <div class="front-top"><div class="school">{{ $schoolName }}</div><div class="motto">{{ $motto }}</div></div>
        <div class="wing left"></div><div class="wing right"></div><div class="gold-diagonal left"></div><div class="gold-diagonal right"></div>
        <div class="portrait-box">@if($card['photo'])<img src="{{ $card['photo'] }}" alt="">@else<div class="photo-fallback">{{ strtoupper(substr($staff->name,0,1)) }}</div>@endif</div>
        <div class="front-name">{{ $staff->name }}</div><div class="front-role">{{ $staff->roleLabel() }}</div>
        <table class="details"><tr><td>Staff ID</td><td>{{ $staff->staff_id ?: 'STAFF-' . str_pad($staff->id,4,'0',STR_PAD_LEFT) }}</td></tr><tr><td>Department</td><td>{{ $card['department'] }}</td></tr><tr><td>Joined</td><td>{{ $card['joined'] }}</td></tr></table>
        <div class="front-foot">STAFF</div><div class="inner-frame"></div>
    </article>
    <article class="card back">
        <div class="back-school">{{ $schoolName }}</div><div class="ornament"></div>
        <div class="qr">@if($card['qr'])<img src="{{ $card['qr'] }}" alt="">@endif</div><div class="scan">Scan for staff attendance</div>
        <div class="return">If found, please return to:<strong>{{ $schoolName }}</strong>{{ $tenant?->address ?: 'School administration office' }}<div class="contacts">{{ $tenant?->phone }}<br>{{ $tenant?->email }}</div></div>
        <div class="property">This card is the property of {{ $schoolName }}.<br>Please return upon request.</div>
        @if($card['signature'])<div class="signature"><img src="{{ $card['signature'] }}" alt=""></div>@endif<div class="signature-label">Authorized Signature</div>
        <div class="back-frame-two"></div><div class="inner-frame"></div>
    </article>
</section>
@endforeach
</body>
</html>
