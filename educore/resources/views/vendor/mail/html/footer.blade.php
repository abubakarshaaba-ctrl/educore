@props(['brand' => []])

@php
    $context = $brand['context'] ?? 'platform';
    $isSchool = $context === 'school';
    $name = trim((string) ($brand['name'] ?? ($isSchool ? 'Your School' : 'EduCore')));
    $address = trim((string) ($brand['address'] ?? ''));
    $phone = trim((string) ($brand['phone'] ?? ''));
    $email = trim((string) ($brand['email'] ?? ''));
    $supportEmail = trim((string) ($brand['support_email'] ?? 'support@educoreng.online'));
@endphp

<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell footer-cell" align="center">
    @if($isSchool)
        <div class="footer-school-name">{{ $name }}</div>
        @if($address !== '')
            <p class="footer-contact">{{ $address }}</p>
        @endif
        @if($phone !== '' || $email !== '')
            <p class="footer-contact">
                @if($phone !== ''){{ $phone }}@endif
                @if($phone !== '' && $email !== '') &nbsp;·&nbsp; @endif
                @if($email !== ''){{ $email }}@endif
            </p>
        @endif
        <div class="footer-powered">Powered securely by <span class="brand-edu">Edu</span><span class="brand-core">Core</span></div>
        <p class="footer-small">&copy; {{ date('Y') }} {{ $name }}. All rights reserved.</p>
    @else
        <div class="footer-platform-mark"><span class="brand-edu">Edu</span><span class="brand-core">Core</span></div>
        <p>Secure school communication powered by EduCore.</p>
        @if($supportEmail !== '')
            <p class="footer-contact">{{ $supportEmail }}</p>
        @endif
        <p class="footer-small">&copy; {{ date('Y') }} EduCore. All rights reserved.</p>
    @endif
</td>
</tr>
</table>
</td>
</tr>
