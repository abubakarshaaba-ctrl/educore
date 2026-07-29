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
    <table class="ref-row"><tr><td><strong>REF:</strong> ADM/{{ $admission->application_number }}</td><td><strong>DATE:</strong> {{ now()->format('d F Y') }}</td></tr></table>
    <div class="document-title"><span>Offer of Admission</span></div>
    <div class="recipient"><strong>{{ $admission->guardian_name }}</strong><br>Parent / Guardian of {{ $admission->first_name }} {{ $admission->last_name }}<br>{{ $admission->guardian_address ?: $admission->address }}</div>
    <div class="salutation">Dear {{ $admission->guardian_name }},</div>
    <div class="body-text">{{ $intro }}</div>
    <div class="body-text">{{ $body }}</div>
    <table class="offer-details">
        <tr><td>Applicant</td><td><strong>{{ $admission->first_name }} {{ $admission->other_names }} {{ $admission->last_name }}</strong></td></tr>
        <tr><td>Application Number</td><td>{{ $admission->application_number }}</td></tr>
        <tr><td>Class Offered</td><td>{{ $admission->applyingForClassLevel?->name ?? 'As determined by the school' }}</td></tr>
        <tr><td>Academic Year</td><td>{{ $admission->academic_year ?? date('Y').'/'.(date('Y')+1) }}</td></tr>
        <tr><td>Offer Status</td><td>Provisional admission — subject to completion of enrolment requirements</td></tr>
    </table>
    <div class="body-text">{{ $closing }}</div>
    <div class="acceptance-note"><strong>Important:</strong> Present this letter and the applicant’s original supporting documents when completing enrolment. The school reserves the right to verify all submitted information.</div>
    <table class="sign-table"><tr>
        <td><div class="sign-line">{{ $signatory1 }}</div></td>
        <td class="seal-cell"><div class="seal">Official<br>School Seal</div></td>
        <td><div class="sign-line">{{ $signatory2 }}</div></td>
    </tr></table>
    <div class="letter-footer">Official correspondence of {{ $tenant->name }} · Application {{ $admission->application_number }} · Generated securely by EduCore School ERP</div>
</div></div>
</body>
</html>
