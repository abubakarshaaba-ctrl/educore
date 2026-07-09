<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { margin: 60px 70px; }
body{font-family:'DejaVu Sans',sans-serif;color:#1E293B;line-height:1.8;font-size:13px}
.title{text-align:center;font-size:17px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:6px 0 26px;color:#071E45}
.ref{text-align:right;font-size:10px;color:#94A3B8;margin-bottom:10px}
.body-text{font-size:13.5px;text-align:justify;margin-bottom:16px;white-space:pre-line}
.body-text b{color:#071E45}
.details{margin:20px 0;padding:14px;background:#F8FAFC;border-left:3px solid #D79A21;font-size:12.5px}
.details div{margin-bottom:4px}
.sign-row{display:flex;justify-content:space-between;margin-top:70px}
.sign-box{width:220px;text-align:center;border-top:1px solid #1E293B;padding-top:6px;font-size:11px}
.date-line{margin-top:40px;font-size:12px}
</style>
</head>
<body>
@include('pdf.partials.letterhead', ['tenant' => $tenant])

<div class="ref">Ref: JOB-{{ $applicant->id }} &middot; Issued {{ now()->format('d M Y') }}</div>
<div class="title">Job Offer Letter</div>

<div class="body-text">{{ $intro }}</div>

<div class="body-text">{{ $body }}</div>

<div class="details">
    <div><strong>Candidate:</strong> {{ $applicant->name }}</div>
    <div><strong>Position:</strong> {{ $applicant->jobPosting->title }}</div>
    @if($applicant->jobPosting->department)<div><strong>Department:</strong> {{ $applicant->jobPosting->department }}</div>@endif
    @if($applicant->phone)<div><strong>Contact:</strong> {{ $applicant->phone }}</div>@endif
</div>

<div class="body-text">{{ $closing }}</div>

<div class="date-line">Date issued: {{ now()->format('d F Y') }}</div>

<div class="sign-row">
    <div class="sign-box">{{ $signatory1 }}</div>
    <div class="sign-box">{{ $signatory2 }}</div>
</div>
</body>
</html>
