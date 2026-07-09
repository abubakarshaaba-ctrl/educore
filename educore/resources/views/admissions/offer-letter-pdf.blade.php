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
.ref{text-align:right;font-size:10px;color:#94A3B8;margin-bottom:16px}
.body-text{font-size:13.5px;text-align:justify;margin-bottom:16px}
.body-text b{color:#071E45}
.details{margin:20px 0;padding:14px;background:#F8FAFC;border-left:3px solid #D79A21;font-size:12.5px}
.details div{margin-bottom:4px}
.sign-row{display:flex;justify-content:space-between;margin-top:70px}
.sign-box{width:220px;text-align:center;border-top:1px solid #1E293B;padding-top:6px;font-size:11px}
.date-line{margin-top:40px;font-size:12px}
</style>
</head>
<body>
<div class="ref">Application No: {{ $admission->application_number }} &middot; Issued {{ now()->format('d M Y') }}</div>
<div class="header">
    <div class="school-name">{{ $tenant->name }}</div>
    <div class="school-meta">{{ $tenant->address }} @if($tenant->phone) &middot; {{ $tenant->phone }} @endif</div>
</div>

<div class="title">Admission Offer Letter</div>

<div class="body-text">
    Dear {{ $admission->guardian_name }},
</div>

<div class="body-text">
    We are pleased to inform you that <b>{{ $admission->first_name }} {{ $admission->last_name }}</b>
    has been offered admission to {{ $tenant->name }}
    @if($admission->applyingForClassLevel) into <b>{{ $admission->applyingForClassLevel->name }}</b> @endif
    for the {{ $admission->academic_year ?? (date('Y') . '/' . (date('Y') + 1)) }} academic year.
</div>

<div class="details">
    <div><strong>Applicant:</strong> {{ $admission->first_name }} {{ $admission->last_name }}</div>
    <div><strong>Application Number:</strong> {{ $admission->application_number }}</div>
    @if($admission->applyingForClassLevel)<div><strong>Class:</strong> {{ $admission->applyingForClassLevel->name }}</div>@endif
    <div><strong>Guardian:</strong> {{ $admission->guardian_name }} ({{ $admission->guardian_phone }})</div>
</div>

<div class="body-text">
    Please contact the school office to complete the enrollment process and confirm your ward's place.
    We look forward to welcoming {{ $admission->first_name }} to our school community.
</div>

<div class="date-line">Date issued: {{ now()->format('d F Y') }}</div>

<div class="sign-row">
    <div class="sign-box">Admissions Officer</div>
    <div class="sign-box">Principal / Head of School</div>
</div>
</body>
</html>
