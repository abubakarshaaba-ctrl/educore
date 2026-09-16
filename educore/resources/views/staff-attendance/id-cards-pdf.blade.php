<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff identity cards</title>
<style>
@page{size:A4 landscape;margin:12mm}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:DejaVu Sans,Arial,sans-serif;color:#10233d;background:#fff}
.sheet{height:185mm;position:relative;text-align:center;padding-top:41mm;overflow:hidden;page-break-inside:avoid}
.card{display:inline-block;vertical-align:top;width:54mm;height:86mm;margin:0 7mm;position:relative;overflow:hidden;background:#fff;border:.22mm solid #d7dce5;border-radius:2.4mm;text-align:left;page-break-inside:avoid}
.side-strip{position:absolute;left:0;top:0;bottom:0;width:8.1mm;background:#071e45}.side-gold{position:absolute;left:8.1mm;top:0;bottom:0;width:.9mm;background:#d79a21}.vertical-copy{position:absolute;z-index:5;left:-27mm;top:39mm;width:62mm;text-align:center;color:#fff;font-size:1.35mm;font-weight:bold;letter-spacing:.7mm;text-transform:uppercase;transform:rotate(-90deg)}.slot{position:absolute;top:1.8mm;left:50%;margin-left:-5mm;width:10mm;height:2.3mm;border-radius:2mm;background:#edf0f4}.body{position:absolute;left:9mm;right:0;top:0;bottom:0}.school-logo{position:absolute;top:5mm;left:50%;margin-left:-6mm;width:12mm;height:12mm;object-fit:contain}.school-fallback{position:absolute;top:5.5mm;left:50%;margin-left:-5.5mm;width:11mm;height:11mm;border:.35mm solid #d79a21;border-radius:50%;text-align:center;padding-top:3.5mm;color:#071e45;font-size:3mm;font-weight:bold}.school-name{position:absolute;top:18.2mm;left:2.8mm;right:2.8mm;text-align:center;color:#071e45;font-family:DejaVu Serif,Georgia,serif;font-size:2.4mm;font-weight:bold;line-height:1.08;text-transform:uppercase;max-height:5.8mm;overflow:hidden}.motto{position:absolute;top:24.2mm;left:3mm;right:3mm;text-align:center;color:#666f7d;font-family:DejaVu Serif,Georgia,serif;font-size:1.2mm;font-style:italic;white-space:nowrap;overflow:hidden}.portrait-box{position:absolute;top:28.3mm;left:50%;margin-left:-10mm;width:20mm;height:22mm;background:#f5f6f8;border:.4mm solid #d79a21;border-radius:1.4mm;padding:.7mm;overflow:hidden}.portrait-box img{width:100%;height:100%;object-fit:cover;object-position:top}.photo-fallback{height:100%;background:#e1e4e8;text-align:center;padding-top:6mm;color:#071e45;font-family:DejaVu Serif,serif;font-size:9mm;font-weight:bold}.front-name{position:absolute;top:52mm;left:2.5mm;right:2.5mm;text-align:center;color:#071e45;font-family:DejaVu Serif,Georgia,serif;font-size:3.05mm;line-height:1.05;font-weight:bold;max-height:6.1mm;overflow:hidden}.front-role{position:absolute;top:58.6mm;left:2.8mm;right:2.8mm;text-align:center;color:#d79a21;font-size:1.45mm;line-height:1.15;font-weight:bold;text-transform:uppercase;max-height:3.7mm;overflow:hidden}.details{position:absolute;top:63.3mm;left:3.8mm;right:3.8mm;width:calc(100% - 7.6mm);border-collapse:collapse}.details td{padding:.5mm 0;font-size:1.35mm;line-height:1.12;vertical-align:top}.details td:first-child{width:10.6mm;color:#071e45;font-weight:bold}.details td:nth-child(2){width:2mm;color:#98a2b3;text-align:center}.details td:last-child{color:#253858;font-weight:bold}.front-footer-rule{position:absolute;left:3.8mm;right:3.8mm;bottom:6.8mm;height:.18mm;background:#e5c273}.corner-line{position:absolute;right:0;bottom:0;width:28mm;height:.7mm;background:#d79a21}.back-title{position:absolute;top:8.6mm;left:4mm;right:4mm;text-align:center;color:#071e45;font-family:DejaVu Serif,Georgia,serif;font-size:2.8mm;line-height:1;font-weight:bold;letter-spacing:.12mm;text-transform:uppercase}.back-rule{position:absolute;top:13.6mm;left:11mm;right:11mm;height:.3mm;background:#d79a21}.qr{position:absolute;top:17.2mm;left:50%;margin-left:-13mm;width:26mm;height:26mm;padding:1.5mm;background:#fff;border:.4mm solid #d79a21;border-radius:1.3mm}.qr img{width:100%;height:100%}.scan{position:absolute;top:45.1mm;left:3mm;right:3mm;text-align:center;color:#071e45;font-family:DejaVu Serif,Georgia,serif;font-size:1.9mm;line-height:1.1;font-weight:bold}.back-message{position:absolute;top:50.1mm;left:5mm;right:5mm;text-align:center;color:#3f4a5c;font-family:DejaVu Serif,Georgia,serif;font-size:1.7mm;line-height:1.4;font-style:italic}.back-property{position:absolute;top:58.7mm;left:4.6mm;right:4.6mm;text-align:center;color:#667085;font-size:1.05mm;line-height:1.45}.back-property strong{color:#071e45}.powered{position:absolute;left:3.8mm;right:3.8mm;bottom:3.5mm;height:9.2mm;border-top:.18mm solid #e5c273;text-align:center;padding-top:1.35mm;white-space:nowrap}.powered-label{display:inline-block;vertical-align:middle;color:#667085;font-size:1.05mm;margin-right:.8mm}.powered-icon{display:inline-block;vertical-align:middle;width:5.6mm;height:5.6mm;margin-right:.8mm}.brand-wrap{display:inline-block;vertical-align:middle;text-align:left}.brand-name{font-size:3mm;line-height:1;font-weight:bold;letter-spacing:-.12mm}.brand-edu{color:#071e45}.brand-core{color:#d79a21}.brand-small{display:block;margin-top:.35mm;color:#7a8494;font-size:.74mm;font-weight:bold;letter-spacing:.08mm;text-transform:uppercase}
</style>
</head>
<body>
@foreach($cards as $card)
@php
    $staff=$card['staff'];
    $tenant=$card['tenant'];
    $schoolName=$tenant?->name??'EduCore School';
    $motto=$tenant?->motto?:'Excellence through education';
    $logoData=null;
    if($tenant?->logo_path){
        $logoRelative=preg_replace('#^storage/#','',ltrim($tenant->logo_path,'/'));
        $logoFile=storage_path('app/public/'.$logoRelative);
        if(is_file($logoFile)){
            $logoMime=@mime_content_type($logoFile)?:'image/png';
            $logoData='data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoFile));
        }
    }
    $educoreFile=public_path('brand/educore-icon-id.svg');
    $educoreIcon=is_file($educoreFile)?'data:image/svg+xml;base64,'.base64_encode(file_get_contents($educoreFile)):null;
@endphp
<section class="sheet" @if(!$loop->last) style="page-break-after:always" @endif>
<article class="card front">
    <div class="side-strip"></div><div class="side-gold"></div><div class="vertical-copy">EduCore • Empower • Excel</div><div class="slot"></div>
    <div class="body">
        @if($logoData)<img class="school-logo" src="{{ $logoData }}" alt="">@else<div class="school-fallback">{{ strtoupper(substr($schoolName,0,2)) }}</div>@endif
        <div class="school-name">{{ $schoolName }}</div><div class="motto">{{ $motto }}</div>
        <div class="portrait-box">@if($card['photo'])<img src="{{ $card['photo'] }}" alt="">@else<div class="photo-fallback">{{ strtoupper(substr($staff->name,0,1)) }}</div>@endif</div>
        <div class="front-name">{{ $staff->name }}</div><div class="front-role">{{ $staff->roleLabel() }}</div>
        <table class="details"><tr><td>Staff ID</td><td>:</td><td>{{ $staff->staff_id?:'STAFF-'.str_pad($staff->id,4,'0',STR_PAD_LEFT) }}</td></tr><tr><td>Department</td><td>:</td><td>{{ $card['department'] }}</td></tr><tr><td>Phone</td><td>:</td><td>{{ $staff->phone?:'—' }}</td></tr></table>
        <div class="front-footer-rule"></div>
    </div><div class="corner-line"></div>
</article>
<article class="card back">
    <div class="side-strip"></div><div class="side-gold"></div><div class="vertical-copy">People • Process • Progress</div><div class="slot"></div>
    <div class="body">
        <div class="back-title">Staff Attendance</div><div class="back-rule"></div>
        <div class="qr">@if($card['qr'])<img src="{{ $card['qr'] }}" alt="">@endif</div><div class="scan">Scan for Attendance</div>
        <div class="back-message">“Discipline Today.<br>A Greater Tomorrow.”</div>
        <div class="back-property">This personal QR identifies the card owner for EduCore staff attendance.<br><strong>If found, please return this card to {{ $schoolName }}.</strong></div>
        <div class="powered"><span class="powered-label">Powered by</span>@if($educoreIcon)<img class="powered-icon" src="{{ $educoreIcon }}" alt="">@endif<span class="brand-wrap"><span class="brand-name"><span class="brand-edu">Edu</span><span class="brand-core">Core</span></span><span class="brand-small">One Platform. Every School Operation.</span></span></div>
    </div><div class="corner-line"></div>
</article>
</section>
@endforeach
</body>
</html>
