<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Submitted — {{ $tenant->name }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#F1F5F9;color:#1E293B;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px}
.card{background:white;border-radius:20px;padding:40px 32px;text-align:center;max-width:520px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,0.08);border:1px solid #E2E8F0}
.check{width:80px;height:80px;border-radius:50%;background:#ECFDF5;border:3px solid #6EE7B7;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 20px}
h1{font-size:24px;font-weight:800;color:#059669;margin-bottom:8px}
p{font-size:15px;color:#64748B;margin-bottom:20px;line-height:1.6}
.app-number{background:#F0FDF4;border:2px dashed #86EFAC;border-radius:12px;padding:16px 20px;margin:20px 0}
.app-number .label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#059669;margin-bottom:6px}
.app-number .num{font-size:22px;font-weight:800;font-family:monospace;color:#065F46;letter-spacing:.05em}
.info{background:#EFF6FF;border-radius:10px;padding:14px 16px;font-size:13px;color:#1E40AF;margin-bottom:20px;text-align:left;line-height:1.6}
.info strong{display:block;margin-bottom:4px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;font-size:14px;font-weight:700;border-radius:10px;text-decoration:none;transition:all 200ms;margin:4px;font-family:inherit;border:none;cursor:pointer}
.btn-primary{background:#D79A21;color:white}
.btn-outline{background:white;color:#2563EB;border:2px solid #2563EB}
.sms-note{font-size:12px;color:#94A3B8;margin-top:16px}
</style>
</head>
<body>
<div class="card">
    <div class="check">&#10003;</div>
    <h1>Application Submitted!</h1>
    <p>Thank you for applying to <strong>{{ $tenant->name }}</strong>. Your application has been received and is under review.</p>

    <div class="app-number">
        <div class="label">Your Application Number</div>
        <div class="num">{{ $admission->application_number }}</div>
    </div>

    <div class="info">
        <strong>&#128241; What happens next?</strong>
        Our admissions team will review your application.
        @if($admission->guardian_phone) An SMS confirmation has been sent to <strong>{{ $admission->guardian_phone }}</strong>.@endif
        You can check your application status at any time using your application number.
    </div>

    <a href="{{ route('portal.status.form', $tenant->slug) }}" class="btn btn-primary">Check Status Later</a>
    <a href="{{ route('portal.landing', $tenant->slug) }}" class="btn btn-outline">Back to Portal</a>

    <div class="sms-note">
        Screenshot or write down your application number: <strong>{{ $admission->application_number }}</strong>
    </div>
</div>
</body>
</html>
