<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Check Status — {{ $tenant->name }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#F1F5F9;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px}
.card{background:white;border-radius:20px;padding:36px 28px;max-width:460px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,0.08);border:1px solid #E2E8F0}
.top{text-align:center;margin-bottom:28px}
.top .icon{font-size:40px;margin-bottom:12px}
.top h1{font-size:22px;font-weight:800;color:#1E293B;margin-bottom:6px}
.top p{font-size:14px;color:#64748B}
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#475569}
input{padding:12px 14px;font-size:14px;border:1.5px solid #E2E8F0;border-radius:10px;background:#F8FAFC;outline:none;width:100%;transition:border 200ms;font-family:inherit}
input:focus{border-color:#2563EB;background:white}
.btn{width:100%;padding:13px;font-size:15px;font-weight:700;background:#2563EB;color:white;border:none;border-radius:10px;cursor:pointer;font-family:inherit;transition:all 200ms}
.btn:hover{background:#1D4ED8}
.error{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:13px;color:#B91C1C;margin-bottom:16px}
.back{display:block;text-align:center;margin-top:16px;font-size:13px;color:#2563EB;text-decoration:none}
</style>
</head>
<body>
<div class="card">
    <div class="top">
        <div class="icon">&#128269;</div>
        <h1>Check Application Status</h1>
        <p>Enter your application number and guardian phone number to see your current status.</p>
    </div>

    @if($errors->any())
    <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('portal.status', $tenant->slug) }}">
    @csrf
    <div class="fg">
        <label>Application Number</label>
        <input type="text" name="application_number" placeholder="e.g. APP-SCHOOL-2025-XXXXXX" required style="text-transform:uppercase">
    </div>
    <div class="fg">
        <label>Guardian Phone Number</label>
        <input type="tel" name="guardian_phone" placeholder="e.g. 08012345678" required>
    </div>
    <button type="submit" class="btn">Check Status</button>
    </form>
    <a href="{{ route('portal.landing', $tenant->slug) }}" class="back">&#8592; Back to Portal</a>
</div>
</body>
</html>
