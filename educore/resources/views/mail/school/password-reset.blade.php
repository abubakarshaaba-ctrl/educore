<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset your {{ $mailBrand['name'] ?? 'school' }} password</title>
</head>
@php
    $school = trim((string) ($mailBrand['name'] ?? 'Your School'));
    $initial = mb_strtoupper(mb_substr($school, 0, 1));
    $motto = trim((string) ($mailBrand['motto'] ?? ''));
    $address = trim((string) ($mailBrand['address'] ?? ''));
    $phone = trim((string) ($mailBrand['phone'] ?? ''));
    $email = trim((string) ($mailBrand['email'] ?? ''));
@endphp
<body style="margin:0;padding:0;background:#EEF2F7;-webkit-text-size-adjust:100%;font-family:Arial,Helvetica,sans-serif;color:#334155;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
Secure password reset for {{ $school }}. This link expires in {{ $expires }} minutes.
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#EEF2F7;border-collapse:collapse;">
<tr>
<td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;border-collapse:separate;border-spacing:0;background:#FFFFFF;border:1px solid #DDE5EF;border-radius:18px;overflow:hidden;box-shadow:0 10px 28px rgba(14,90,71,.08);">

<tr>
<td bgcolor="#0E5A47" style="background:#0E5A47;padding:0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td style="padding:24px 28px 22px;">
<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr>
<td valign="middle" style="padding-right:14px;">
<table role="presentation" width="54" height="54" cellpadding="0" cellspacing="0" style="width:54px;height:54px;background:#FFFFFF;border-radius:12px;border-collapse:separate;">
<tr><td align="center" valign="middle" style="font-family:Arial,Helvetica,sans-serif;font-size:27px;line-height:54px;font-weight:900;color:#0E5A47;text-align:center;">{{ $initial }}</td></tr>
</table>
</td>
<td valign="middle">
<div style="font-size:20px;line-height:1.2;font-weight:800;letter-spacing:-.2px;color:#FFFFFF;text-transform:uppercase;">{{ $school }}</div>
<div style="font-size:12px;line-height:1.5;color:#D9F3EA;margin-top:5px;">{{ $motto !== '' ? $motto : 'Official school communication' }}</div>
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
<td style="padding:34px 34px 30px;">
<div style="display:inline-block;padding:6px 10px;border-radius:999px;background:#EAF7F2;color:#0E5A47;font-size:11px;line-height:1;font-weight:800;letter-spacing:.5px;text-transform:uppercase;">Account Security</div>

<h1 style="margin:16px 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:28px;line-height:1.2;color:#0B1D3A;font-weight:800;letter-spacing:-.5px;">Reset your password</h1>

<p style="margin:0 0 22px;font-size:15px;line-height:1.65;color:#526276;">
A password reset was requested for your {{ $school }} account. Use the secure button below to create a new password.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0;background:#F7F9FC;border:1px solid #E2E8F0;border-radius:12px;">
<tr>
<td style="padding:16px 18px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td style="font-size:13px;line-height:1.5;color:#64748B;">Reset link validity</td>
<td align="right" style="font-size:14px;line-height:1.5;color:#0B1D3A;font-weight:800;">{{ $expires }} minutes</td>
</tr>
</table>
</td>
</tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:26px auto 24px;border-collapse:separate;">
<tr>
<td bgcolor="#0E725A" style="background:#0E725A;border-radius:10px;text-align:center;">
<a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 30px;font-size:15px;line-height:1.2;font-weight:800;color:#FFFFFF;text-decoration:none;border-radius:10px;">Reset Password</a>
</td>
</tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0;background:#F8FAFC;border-left:4px solid #F2C14E;border-radius:8px;">
<tr>
<td style="padding:14px 16px;">
<div style="font-size:13px;line-height:1.45;font-weight:800;color:#0B1D3A;margin-bottom:4px;">Didn't request this?</div>
<div style="font-size:13px;line-height:1.55;color:#64748B;">No action is required. Your current password remains unchanged unless the reset link is used.</div>
</td>
</tr>
</table>

<p style="margin:24px 0 0;font-size:12px;line-height:1.55;color:#8391A5;">
If the button does not open, use this secure link:<br>
<a href="{{ $resetUrl }}" style="color:#0E5A47;text-decoration:underline;word-break:break-all;">{{ $resetUrl }}</a>
</p>
</td>
</tr>

<tr><td style="padding:0 34px;"><div style="height:1px;background:#E8EDF3;font-size:0;line-height:1px;">&nbsp;</div></td></tr>

<tr>
<td align="center" style="padding:22px 28px 26px;text-align:center;">
<div style="font-size:14px;line-height:1.4;font-weight:800;color:#0E5A47;">{{ $school }}</div>
@if($address !== '')
<div style="margin-top:5px;font-size:11px;line-height:1.5;color:#8795A7;">{{ $address }}</div>
@endif
@if($phone !== '' || $email !== '')
<div style="margin-top:3px;font-size:11px;line-height:1.5;color:#8795A7;">@if($phone !== ''){{ $phone }}@endif @if($phone !== '' && $email !== '')&nbsp;·&nbsp;@endif @if($email !== ''){{ $email }}@endif</div>
@endif
<div style="margin-top:10px;font-size:11px;line-height:1.5;color:#9AA7B6;">Powered securely by <span style="font-weight:800;color:#0B3B78;">Edu</span><span style="font-weight:800;color:#D79A21;">Core</span></div>
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>
