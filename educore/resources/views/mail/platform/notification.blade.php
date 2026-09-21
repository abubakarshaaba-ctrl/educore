<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $subject ?? 'EduCore Notification' }}</title>
</head>
<body style="margin:0;padding:0;background:#EEF2F7;-webkit-text-size-adjust:100%;font-family:Arial,Helvetica,sans-serif;color:#334155;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
{{ $subject ?? 'A new notification from EduCore.' }}
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#EEF2F7;border-collapse:collapse;">
<tr>
<td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;border-collapse:separate;border-spacing:0;background:#FFFFFF;border:1px solid #DDE5EF;border-radius:18px;overflow:hidden;box-shadow:0 10px 28px rgba(8,38,83,.08);">

<tr>
<td bgcolor="#082653" style="background:#082653;padding:0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td style="padding:24px 28px 22px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td valign="middle">
<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr>
<td valign="middle" style="padding-right:14px;">
<img src="cid:{{ $platformIconCid }}" alt="EduCore" width="56" height="56" style="display:block;width:56px;height:56px;border-radius:12px;background:#FFFFFF;">
</td>
<td valign="middle">
<div style="font-size:28px;line-height:1.05;font-weight:800;letter-spacing:-.5px;color:#FFFFFF;"><span style="color:#FFFFFF;">Edu</span><span style="color:#F2C14E;">Core</span></div>
<div style="font-size:12px;line-height:1.5;color:#CFE0F4;margin-top:5px;">One Platform. Every School.</div>
</td>
</tr>
</table>
</td>
<td align="right" valign="middle" style="font-size:10px;line-height:1.6;letter-spacing:2px;color:#FFFFFF;text-transform:uppercase;">
<span style="color:#CFE0F4;">Learn</span>&nbsp;&nbsp;<span style="color:#CFE0F4;">Manage</span>&nbsp;&nbsp;<strong style="color:#FFFFFF;">Grow Together</strong>
</td>
</tr>
</table>
</td>
</tr>
<tr><td style="height:4px;background:#F2C14E;font-size:0;line-height:4px;">&nbsp;</td></tr>
</table>
</td>
</tr>

<tr>
<td style="padding:34px 36px 30px;">
<div style="display:inline-block;padding:6px 10px;border-radius:999px;background:#FFF7DD;color:#8A6110;font-size:11px;line-height:1;font-weight:800;letter-spacing:.5px;text-transform:uppercase;">
EduCore Notification
</div>

<h1 style="margin:16px 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:28px;line-height:1.2;color:#0B1D3A;font-weight:800;letter-spacing:-.5px;">
{{ $subject ?? ($greeting ?? 'EduCore Notification') }}
</h1>

@if(!empty($greeting))
<p style="margin:0 0 18px;font-size:15px;line-height:1.65;color:#0B1D3A;font-weight:700;">{{ $greeting }}</p>
@endif

@foreach(($introLines ?? []) as $line)
<p style="margin:0 0 15px;font-size:15px;line-height:1.65;color:#526276;">{!! $line !!}</p>
@endforeach

@isset($actionText)
<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:26px auto 24px;border-collapse:separate;">
<tr>
<td bgcolor="#0B3B78" style="background:#0B3B78;border-radius:10px;text-align:center;">
<a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 30px;font-size:15px;line-height:1.2;font-weight:800;color:#FFFFFF;text-decoration:none;border-radius:10px;">{{ $actionText }} &nbsp;→</a>
</td>
</tr>
</table>
@endisset

@foreach(($outroLines ?? []) as $line)
<p style="margin:0 0 15px;font-size:15px;line-height:1.65;color:#526276;">{!! $line !!}</p>
@endforeach

@if(!empty($salutation))
<p style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#334155;">{!! nl2br(e($salutation)) !!}</p>
@endif

@isset($actionText)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;margin-top:24px;border-collapse:separate;border-spacing:0;background:#F8FAFC;border-left:4px solid #F2C14E;border-radius:8px;">
<tr>
<td style="padding:14px 16px;">
<div style="font-size:13px;line-height:1.45;font-weight:800;color:#0B1D3A;margin-bottom:4px;">Having trouble with the button?</div>
<div style="font-size:12px;line-height:1.55;color:#64748B;">Copy and paste this link into your browser:</div>
<div style="margin-top:6px;font-size:11px;line-height:1.5;"><a href="{{ $actionUrl }}" style="color:#0B4A87;text-decoration:underline;word-break:break-all;">{{ $actionUrl }}</a></div>
</td>
</tr>
</table>
@endisset
</td>
</tr>

<tr><td style="padding:0 36px;"><div style="height:1px;background:#E8EDF3;font-size:0;line-height:1px;">&nbsp;</div></td></tr>

<tr>
<td style="padding:20px 28px 18px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td align="center" style="width:33%;font-size:12px;line-height:1.4;color:#0B1D3A;"><strong>Learn</strong><br><span style="color:#64748B;">Smarter</span></td>
<td align="center" style="width:33%;border-left:1px solid #E2E8F0;border-right:1px solid #E2E8F0;font-size:12px;line-height:1.4;color:#0B1D3A;"><strong>Manage</strong><br><span style="color:#64748B;">Better</span></td>
<td align="center" style="width:33%;font-size:12px;line-height:1.4;color:#0B1D3A;"><strong>Grow</strong><br><span style="color:#64748B;">Together</span></td>
</tr>
</table>
</td>
</tr>

<tr>
<td bgcolor="#082653" style="background:#082653;padding:22px 28px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td valign="middle">
<div style="font-size:22px;line-height:1.1;font-weight:800;"><span style="color:#FFFFFF;">Edu</span><span style="color:#F2C14E;">Core</span></div>
<div style="margin-top:4px;font-size:10px;line-height:1.5;color:#CFE0F4;">One Platform. Every School.</div>
</td>
<td align="right" valign="middle" style="font-size:10px;line-height:1.5;color:#CFE0F4;">
© {{ date('Y') }} EduCore<br>
<span style="color:#F2C14E;">www.educoreng.online</span>
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
