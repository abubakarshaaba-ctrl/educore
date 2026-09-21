<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $broadcastTitle ?? 'EduCore Platform Broadcast' }}</title>
</head>
<body style="margin:0;padding:0;background:#EEF2F7;-webkit-text-size-adjust:100%;font-family:Arial,Helvetica,sans-serif;color:#334155;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
    {{ $broadcastTitle ?? 'A new EduCore platform broadcast is available.' }}
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#EEF2F7;border-collapse:collapse;">
<tr>
<td align="center" style="padding:14px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;border-collapse:separate;border-spacing:0;background:#FFFFFF;border:1px solid #DDE5EF;border-radius:12px;overflow:hidden;">

<tr>
<td bgcolor="#082653" style="background:#082653;padding:10px 14px 9px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td valign="middle" style="width:34px;padding:0 9px 0 0;">
<img src="cid:{{ $platformIconCid }}" alt="EduCore" width="30" height="30" style="display:block;width:30px;height:30px;max-width:30px;border:0;border-radius:7px;background:#FFFFFF;">
</td>
<td valign="middle" style="padding:0;">
<div style="font-size:18px;line-height:1.05;font-weight:800;letter-spacing:-.3px;color:#FFFFFF;">
<span style="color:#FFFFFF;">Edu</span><span style="color:#F2C14E;">Core</span>
</div>
<div style="font-size:9px;line-height:1.3;color:#CFE0F4;margin-top:2px;">One Platform. Every School.</div>
</td>
</tr>
</table>
</td>
</tr>

<tr>
<td style="height:3px;background:#F2C14E;font-size:0;line-height:3px;">&nbsp;</td>
</tr>

<tr>
<td style="padding:20px 22px 19px;">
<div style="display:inline-block;padding:5px 9px;border-radius:999px;background:#FFF7DD;color:#8A6110;font-size:8.5px;line-height:1;font-weight:800;letter-spacing:.5px;text-transform:uppercase;">
Platform Broadcast
</div>

<h1 style="margin:11px 0 10px;font-size:20px;line-height:1.25;color:#0B1D3A;font-weight:800;letter-spacing:-.25px;">
{{ $broadcastTitle }}
</h1>

<p style="margin:0 0 12px;font-size:12.5px;line-height:1.55;color:#0B1D3A;font-weight:700;">
@if(!empty($recipientName))
Hello {{ $recipientName }},
@else
Hello,
@endif
</p>

<p style="margin:0 0 15px;font-size:12.5px;line-height:1.55;color:#526276;">
A new EduCore platform broadcast has been sent{{ !empty($schoolName) ? ' to '.$schoolName : ' to your school' }}.
</p>

@if(!empty($messageBody))
@php
    $paragraphs = preg_split('/(?:\r?\n){2,}/', trim($messageBody)) ?: [];
@endphp
@foreach($paragraphs as $paragraph)
    @if(trim($paragraph) !== '')
    <p style="margin:0 0 12px;font-size:13px;line-height:1.65;color:#46566A;">{!! nl2br(e(trim($paragraph))) !!}</p>
    @endif
@endforeach
@endif

@if(!empty($expiresAt))
<p style="margin:14px 0 0;padding:9px 11px;border-radius:8px;background:#F8FAFC;border:1px solid #E2E8F0;font-size:10.5px;line-height:1.45;color:#64748B;">
This notice remains available until {{ $expiresAt }}.
</p>
@endif

@if(!empty($actionUrl))
<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:17px auto 14px;border-collapse:separate;">
<tr>
<td bgcolor="#0B3B78" style="background:#0B3B78;border-radius:7px;text-align:center;">
<a href="{{ $actionUrl }}" style="display:inline-block;padding:10px 20px;font-size:12.5px;line-height:1.2;font-weight:800;color:#FFFFFF;text-decoration:none;border-radius:7px;">
View Platform Notices
</a>
</td>
</tr>
</table>
@endif

<p style="margin:15px 0 0;font-size:11.5px;line-height:1.55;color:#334155;">
Regards,<br>
<strong>The EduCore Team</strong>
</p>

@if(!empty($actionUrl))
<p style="margin:13px 0 0;font-size:9.5px;line-height:1.45;color:#8391A5;">
If the button does not open, use the secure Platform Notices page from your EduCore school account.
</p>
@endif
</td>
</tr>

<tr>
<td bgcolor="#082653" style="background:#082653;padding:10px 14px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td valign="middle">
<div style="font-size:14px;line-height:1.05;font-weight:800;">
<span style="color:#FFFFFF;">Edu</span><span style="color:#F2C14E;">Core</span>
</div>
<div style="margin-top:2px;font-size:7.5px;line-height:1.3;color:#CFE0F4;">One Platform. Every School.</div>
</td>
<td align="right" valign="middle" style="font-size:7.5px;line-height:1.35;color:#CFE0F4;">
© {{ date('Y') }} EduCore<br>
<span style="color:#F2C14E;">educoreng.online</span>
</td>
</tr>
</table>
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>
