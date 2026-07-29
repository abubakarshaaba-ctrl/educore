<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
@include('pdf.partials.premium-offer-style')
</head>
<body>
@php $logoFile = $tenant->logo_path ? storage_path('app/public/'.$tenant->logo_path) : null; @endphp
<div class="paper"><div class="paper-inner">
    <table class="letter-head"><tr>
        <td class="letter-logo-cell">
            @if($logoFile && file_exists($logoFile))<img class="letter-logo" src="{{ $logoFile }}">@else<div class="letter-logo-fallback">{{ strtoupper(substr($tenant->name,0,1)) }}</div>@endif
        </td>
        <td>
            <div class="letter-school">{{ $tenant->name }}</div>
            @if($tenant->motto)<div class="letter-motto">“{{ $tenant->motto }}”</div>@endif
            <div class="letter-meta">{{ $tenant->address }}@if($tenant->phone) · {{ $tenant->phone }}@endif @if($tenant->email) · {{ $tenant->email }}@endif</div>
        </td>
    </tr></table>
    <table class="ref-row"><tr><td><strong>REF:</strong> HR/OFFER/{{ str_pad($applicant->id,5,'0',STR_PAD_LEFT) }}</td><td><strong>DATE:</strong> {{ now()->format('d F Y') }}</td></tr></table>
    <div class="document-title"><span>Offer of Employment</span></div>
    <div class="recipient"><strong>{{ $applicant->name }}</strong><br>{{ $applicant->email }}@if($applicant->phone)<br>{{ $applicant->phone }}@endif</div>
    <div class="salutation">Dear {{ $applicant->name }},</div>
    <div class="body-text">{{ $intro }}</div>
    <div class="body-text">{{ $body }}</div>
    <table class="offer-details">
        <tr><td>Candidate</td><td><strong>{{ $applicant->name }}</strong></td></tr>
        <tr><td>Position</td><td>{{ $applicant->jobPosting?->title }}</td></tr>
        @if($applicant->jobPosting?->department)<tr><td>Department</td><td>{{ $applicant->jobPosting->department }}</td></tr>@endif
        <tr><td>Employment Status</td><td>Offer issued — subject to acceptance and pre-employment verification</td></tr>
        <tr><td>Reporting Location</td><td>{{ $tenant->address ?: $tenant->name }}</td></tr>
    </table>
    <div class="body-text">{{ $closing }}</div>
    <div class="acceptance-note"><strong>Acceptance:</strong> Please sign and return a copy of this letter within the period stated in the offer. Your appointment remains subject to verification of credentials and the school’s employment policies.</div>
    <table class="sign-table"><tr>
        <td><div class="sign-line">{{ $signatory1 }}</div></td>
        <td class="seal-cell"><div class="seal">Official<br>School Seal</div></td>
        <td><div class="sign-line">{{ $signatory2 }}</div></td>
    </tr></table>
    <div class="letter-footer">Confidential employment correspondence of {{ $tenant->name }} · Generated securely by EduCore School ERP</div>
</div></div>
</body>
</html>
