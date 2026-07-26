<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { margin: 60px 70px; }
body{font-family:'DejaVu Sans',sans-serif;color:#1E293B;line-height:1.8;font-size:13px}
.header{text-align:center;border-bottom:3px solid #071E45;padding-bottom:16px;margin-bottom:30px}
.school-name{font-size:22px;font-weight:700;color:#071E45;text-transform:uppercase;letter-spacing:1px}
.school-meta{font-size:11px;color:#64748B;margin-top:4px}
.title{text-align:center;font-size:17px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:26px 0;color:#071E45}
.serial{text-align:right;font-size:10px;color:#94A3B8;margin-bottom:16px}
.body-text{font-size:13.5px;text-align:justify;margin-bottom:24px}
.body-text b{color:#071E45}
.remarks{margin-top:20px;padding:14px;background:#F8FAFC;border-left:3px solid #D79A21;font-size:12.5px}
.sign-row{display:flex;justify-content:space-between;margin-top:70px}
.sign-box{width:220px;text-align:center;border-top:1px solid #1E293B;padding-top:6px;font-size:11px}
.date-line{margin-top:40px;font-size:12px}
</style>
</head>
<body>
<div class="serial">Serial No: {{ $serial }} &middot; Issued {{ $issuedAt->format('d M Y') }}</div>
<div class="header">
    <div class="school-name">{{ $tenant->name }}</div>
    <div class="school-meta">{{ $tenant->address }} @if($tenant->phone) &middot; {{ $tenant->phone }} @endif</div>
</div>

<div class="title">
    @if($type === 'leaving_certificate') Leaving Certificate
    @elseif($type === 'testimonial') Testimonial
    @else Transfer Certificate
    @endif
</div>

<div class="body-text">
    @if($type === 'leaving_certificate')
        This is to certify that <b>{{ $student->full_name }}</b> (Admission Number: <b>{{ $student->admission_number }}</b>)
        was a student of {{ $tenant->name }}
        @if($student->admission_date) from <b>{{ \Carbon\Carbon::parse($student->admission_date)->format('d M Y') }}</b> @endif
        and has left the school in good standing as of <b>{{ now()->format('d M Y') }}</b>.
    @elseif($type === 'testimonial')
        This is to certify that <b>{{ $student->full_name }}</b> (Admission Number: <b>{{ $student->admission_number }}</b>)
        was a dedicated and well-behaved student of {{ $tenant->name }}. During their time with us,
        {{ $student->gender === 'female' ? 'she' : 'he' }} demonstrated commendable conduct and academic diligence.
        We wish {{ $student->gender === 'female' ? 'her' : 'him' }} continued success in all future endeavours.
    @else
        This is to certify that <b>{{ $student->full_name }}</b> (Admission Number: <b>{{ $student->admission_number }}</b>)
        is being transferred from {{ $tenant->name }} and this document serves as the official transfer certificate
        for onward admission to another institution.
    @endif
</div>

@if($remarks)
<div class="remarks"><strong>Remarks:</strong> {{ $remarks }}</div>
@endif

<div class="date-line">Date issued: {{ $issuedAt->format('d F Y') }}</div>

<div class="sign-row">
    <div class="sign-box">Class Teacher / Registrar</div>
    <div class="sign-box">Principal / Head of School</div>
</div>
</body>
</html>
