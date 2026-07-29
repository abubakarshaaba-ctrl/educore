<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page{margin:8mm}
*{box-sizing:border-box}
body{margin:0;font-family:"DejaVu Serif",serif;color:#281337;background:#fff;font-size:12px}
.frame{height:276mm;border:7px solid #341042;padding:5px;position:relative}
.frame2{height:100%;border:2px solid #C28B2C;padding:5px}
.frame3{height:100%;border:1px solid #341042;padding:22px 27px 18px;position:relative;overflow:hidden}
.corner{position:absolute;width:52px;height:52px;border-color:#C28B2C}
.c1{left:8px;top:8px;border-left:3px solid;border-top:3px solid}.c2{right:8px;top:8px;border-right:3px solid;border-top:3px solid}.c3{left:8px;bottom:8px;border-left:3px solid;border-bottom:3px solid}.c4{right:8px;bottom:8px;border-right:3px solid;border-bottom:3px solid}
.watermark{position:absolute;left:0;right:0;top:105mm;text-align:center;font:bold 116px "DejaVu Sans",sans-serif;color:#341042;opacity:.025}
.header{width:100%;border-collapse:collapse}
.header td{vertical-align:middle}
.logo-cell{width:110px;text-align:center}.photo-cell{width:105px;text-align:center}
.logo{width:88px;height:88px;object-fit:contain}
.logo-fallback{width:82px;height:82px;border:3px double #341042;border-radius:50%;text-align:center;line-height:78px;font:bold 31px "DejaVu Sans",sans-serif;color:#341042}
.school{text-align:center;padding:0 12px}
.school-name{font:bold 24px "DejaVu Serif",serif;color:#341042;text-transform:uppercase;line-height:1.1;letter-spacing:.8px}
.motto{font:italic 10px "DejaVu Serif",serif;color:#8B5A14;margin-top:6px}
.address{font:9px "DejaVu Sans",sans-serif;color:#5D4B66;margin-top:5px;line-height:1.45}
.photo{width:84px;height:98px;object-fit:cover;border:2px solid #C28B2C;padding:3px}
.photo-placeholder{width:84px;height:98px;border:2px solid #C28B2C;color:#8C8291;font:9px "DejaVu Sans",sans-serif;padding-top:36px;text-align:center}
.ornament{text-align:center;color:#C28B2C;font-size:17px;letter-spacing:7px;margin:11px 0 7px}
.title-wrap{text-align:center;margin:4px auto 18px}
.title{display:inline-block;background:#341042;color:white;border:3px double #D4A54C;padding:9px 38px 10px;font:bold 22px "DejaVu Serif",serif;text-transform:uppercase;letter-spacing:2px}
.year{text-align:center;font:bold 12px "DejaVu Serif",serif;margin:0 0 16px}.year span{display:inline-block;border-bottom:1px solid #341042;min-width:130px;padding:0 8px 3px}
.certifies{text-align:center;font:italic 19px "DejaVu Serif",serif;margin-bottom:12px}
.student-name{text-align:center;font:bold 20px "DejaVu Serif",serif;text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid #341042;padding:0 12px 7px;margin:0 55px 13px}
.statement{text-align:center;font-size:12px;line-height:1.8;margin:0 26px}
.details{width:88%;margin:19px auto 0;border-collapse:collapse}
.details td{padding:7px 10px;border-bottom:1px dotted #BDAFC3;font-size:11px}
.details td:first-child{width:35%;font-weight:bold;color:#341042}
.remarks{width:88%;margin:14px auto 0;padding:10px 13px;border-left:4px solid #C28B2C;background:#FCF8F0;font-size:10.5px;line-height:1.6}
.serial{text-align:center;margin-top:11px;color:#7D7082;font:8.5px "DejaVu Sans",sans-serif;letter-spacing:.08em;text-transform:uppercase}
.signatures{width:100%;border-collapse:collapse;margin-top:34px}
.signatures td{width:33.333%;text-align:center;vertical-align:bottom;padding:0 15px;font-size:9px}
.sign-line{border-top:1px solid #341042;padding-top:6px;font-weight:bold}
.seal{width:78px;height:78px;margin:0 auto;border:3px double #341042;border-radius:50%;text-align:center;padding-top:22px;color:#341042;font:bold 8px "DejaVu Sans",sans-serif;text-transform:uppercase;transform:rotate(-5deg)}
.verify{text-align:center;margin-top:13px;color:#8B5A14;font:8px "DejaVu Sans",sans-serif}
</style>
</head>
<body>
@php
    $title = match($type) {
        'leaving_certificate' => 'School Leaving Certificate',
        'testimonial' => 'Testimonial',
        default => 'Transfer Certificate',
    };
    $logoFile = $tenant->logo_path ? storage_path('app/public/'.$tenant->logo_path) : null;
    $photoFile = $student->passport_photo_path ? storage_path('app/public/'.$student->passport_photo_path) : null;
@endphp
<div class="frame"><div class="frame2"><div class="frame3">
    <span class="corner c1"></span><span class="corner c2"></span><span class="corner c3"></span><span class="corner c4"></span>
    <div class="watermark">{{ strtoupper(substr($tenant->name,0,1)) }}</div>
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($logoFile && file_exists($logoFile))<img class="logo" src="{{ $logoFile }}">@else<div class="logo-fallback">{{ strtoupper(substr($tenant->name,0,1)) }}</div>@endif
            </td>
            <td class="school">
                <div class="school-name">{{ $tenant->name }}</div>
                @if($tenant->motto)<div class="motto">“{{ $tenant->motto }}”</div>@endif
                <div class="address">{{ $tenant->address }}@if($tenant->phone)<br>{{ $tenant->phone }}@endif @if($tenant->email) · {{ $tenant->email }}@endif</div>
            </td>
            <td class="photo-cell">
                @if($photoFile && file_exists($photoFile))<img class="photo" src="{{ $photoFile }}">@else<div class="photo-placeholder">PASSPORT<br>PHOTOGRAPH</div>@endif
            </td>
        </tr>
    </table>
    <div class="ornament">◆ ── ◆ ── ◆</div>
    <div class="title-wrap"><span class="title">{{ $title }}</span></div>
    <div class="year">YEAR <span>{{ $issuedAt->format('Y') }}</span></div>
    <div class="certifies">This is to certify that</div>
    <div class="student-name">{{ $student->full_name }}</div>
    <div class="statement">
        @if($type === 'leaving_certificate')
            having been duly enrolled at <strong>{{ $tenant->name }}</strong>, completed the required course of study and leaves the school in good standing.
        @elseif($type === 'testimonial')
            was a bona fide student of <strong>{{ $tenant->name }}</strong> and demonstrated commendable character, discipline and commitment to learning during the period of enrolment.
        @else
            was a bona fide student of <strong>{{ $tenant->name }}</strong> and is hereby granted this official certificate for transfer to another recognised institution.
        @endif
    </div>
    <table class="details">
        <tr><td>Admission Number</td><td>{{ $student->admission_number }}</td></tr>
        <tr><td>Last Class Attended</td><td>{{ $student->currentClassArm?->classLevel?->name }} {{ $student->currentClassArm?->name }}</td></tr>
        <tr><td>Period of Attendance</td><td>{{ $student->admission_date?->format('d F Y') ?? 'School record' }} — {{ $student->graduation_date?->format('d F Y') ?? $issuedAt->format('d F Y') }}</td></tr>
        <tr><td>Conduct</td><td>Good and satisfactory</td></tr>
    </table>
    @if($remarks)<div class="remarks"><strong>Official remarks:</strong> {{ $remarks }}</div>@endif
    <div class="serial">Serial No. {{ $serial }} · Issued {{ $issuedAt->format('d F Y') }}</div>
    <table class="signatures">
        <tr>
            <td><div class="sign-line">Registrar / Class Teacher</div></td>
            <td><div class="seal">Official<br>School Seal</div></td>
            <td><div class="sign-line">Principal / Head of School</div></td>
        </tr>
    </table>
    <div class="verify">This certificate is valid only with the authorised signatures and official school seal.</div>
</div></div></div>
</body>
</html>
