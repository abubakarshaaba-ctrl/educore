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
<td style="padding:0;">
<table align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width:570px;max-width:100%;margin:22px auto 0;border-collapse:collapse;">
<tr>
<td align="center" style="padding:20px 24px 8px;text-align:center;font-family:Arial,Helvetica,sans-serif;">
    @if($isSchool)
        <div style="font-size:14px;line-height:1.4;font-weight:800;color:#0E5A47;text-align:center;">{{ $name }}</div>
        @if($address !== '')
            <div style="font-size:12px;line-height:1.5;color:#64748B;margin-top:5px;text-align:center;">{{ $address }}</div>
        @endif
        @if($phone !== '' || $email !== '')
            <div style="font-size:12px;line-height:1.5;color:#64748B;margin-top:4px;text-align:center;">
                @if($phone !== ''){{ $phone }}@endif
                @if($phone !== '' && $email !== '') &nbsp;·&nbsp; @endif
                @if($email !== ''){{ $email }}@endif
            </div>
        @endif
        <div style="font-size:12px;line-height:1.5;color:#64748B;margin-top:10px;text-align:center;">
            Powered securely by <span style="font-weight:800;color:#0B3B78;">Edu</span><span style="font-weight:800;color:#D79A21;">Core</span>
        </div>
        <div style="font-size:11px;line-height:1.5;color:#94A3B8;margin-top:6px;text-align:center;">&copy; {{ date('Y') }} {{ $name }}. All rights reserved.</div>
    @else
        <div style="font-size:18px;line-height:1.3;font-weight:850;text-align:center;">
            <span style="color:#0B3B78;font-weight:850;">Edu</span><span style="color:#D79A21;font-weight:850;">Core</span>
        </div>
        <div style="font-size:12px;line-height:1.5;color:#64748B;margin-top:6px;text-align:center;">Secure school communication powered by EduCore.</div>
        @if($supportEmail !== '')
            <div style="font-size:12px;line-height:1.5;color:#64748B;margin-top:4px;text-align:center;">{{ $supportEmail }}</div>
        @endif
        <div style="font-size:11px;line-height:1.5;color:#94A3B8;margin-top:6px;text-align:center;">&copy; {{ date('Y') }} EduCore. All rights reserved.</div>
    @endif
</td>
</tr>
</table>
</td>
</tr>
