<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset your EduCore password</title>
</head>
<body style="margin:0;padding:0;background:#EEF2F7;-webkit-text-size-adjust:100%;font-family:Arial,Helvetica,sans-serif;color:#334155;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">Secure EduCore password reset. This link expires in {{ $expires }} minutes.</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#EEF2F7;border-collapse:collapse;">
<tr><td align="center" style="padding:14px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:540px;border-collapse:separate;border-spacing:0;background:#FFFFFF;border:1px solid #DDE5EF;border-radius:12px;overflow:hidden;">

<tr>
<td bgcolor="#082653" style="background:#082653;padding:10px 14px 9px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td valign="middle" style="width:32px;padding:0 8px 0 0;">
<img src="cid:{{ $platformIconCid }}" alt="EduCore" width="28" height="28" style="display:block;width:28px;height:28px;max-width:28px;border:0;border-radius:7px;background:#FFFFFF;">
</td>
<td valign="middle" style="padding:0;">
<div style="font-size:18px;line-height:1.05;font-weight:800;letter-spacing:-.3px;color:#FFFFFF;"><span style="color:#FFFFFF;">Edu</span><span style="color:#F2C14E;">Core</span></div>
<div style="font-size:8.5px;line-height:1.3;color:#CFE0F4;margin-top:1px;">One Platform. Every School.</div>
</td>
</tr>
</table>
</td>
</tr>
<tr><td style="height:2px;background:#F2C14E;font-size:0;line-height:2px;">&nbsp;</td></tr>

<tr>
<td style="padding:20px 22px 18px;">
<div style="display:inline-block;padding:4px 8px;border-radius:999px;background:#FFF7DD;color:#8A6110;font-size:8px;line-height:1;font-weight:800;letter-spacing:.45px;text-transform:uppercase;">Account Security</div>

<h1 style="margin:11px 0 7px;font-size:22px;line-height:1.18;color:#0B1D3A;font-weight:800;letter-spacing:-.35px;">Reset your password</h1>

<p style="margin:0 0 14px;font-size:13px;line-height:1.55;color:#526276;">We received a request to reset the password for your EduCore account.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0;background:#F7F9FC;border:1px solid #E2E8F0;border-radius:8px;">
<tr><td style="padding:10px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td style="font-size:11px;line-height:1.4;color:#64748B;">Secure link validity</td>
<td align="right" style="font-size:12px;line-height:1.4;color:#0B1D3A;font-weight:800;">{{ $expires }} minutes</td>
</tr>
</table>
</td></tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:16px auto 14px;border-collapse:separate;">
<tr><td bgcolor="#0B3B78" style="background:#0B3B78;border-radius:7px;text-align:center;">
<a href="{{ $resetUrl }}" style="display:inline-block;padding:10px 20px;font-size:12.5px;line-height:1.2;font-weight:800;color:#FFFFFF;text-decoration:none;border-radius:7px;">Reset Password</a>
</td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0;background:#F8FAFC;border-left:3px solid #F2C14E;border-radius:6px;">
<tr><td style="padding:10px 12px;">
<div style="font-size:11.5px;line-height:1.4;font-weight:800;color:#0B1D3A;margin-bottom:2px;">Didn't request this?</div>
<div style="font-size:11px;line-height:1.45;color:#64748B;">Ignore this email. Your current password remains unchanged.</div>
</td></tr>
</table>

<p style="margin:13px 0 0;font-size:10px;line-height:1.45;color:#8391A5;">
If the button does not open, <a href="{{ $resetUrl }}" style="color:#0B4A87;text-decoration:underline;font-weight:700;">open the secure reset link</a>.
</p>

<p style="margin:15px 0 0;font-size:11.5px;line-height:1.5;color:#334155;">Regards,<br><strong style="color:#0B1D3A;">The EduCore Team</strong></p>
</td>
</tr>

<tr>
<td bgcolor="#082653" style="background:#082653;padding:11px 14px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
<tr>
<td valign="middle">
<div style="font-size:15px;line-height:1.05;font-weight:800;"><span style="color:#FFFFFF;">Edu</span><span style="color:#F2C14E;">Core</span></div>
<div style="margin-top:1px;font-size:7.5px;line-height:1.3;color:#CFE0F4;">One Platform. Every School.</div>
</td>
<td align="right" valign="middle" style="font-size:7.5px;line-height:1.35;color:#CFE0F4;">© {{ date('Y') }} EduCore<br><span style="color:#F2C14E;">educoreng.online</span></td>
</tr>
</table>
</td>
</tr>

</table>
</td></tr>
</table>
</body>
</html>
