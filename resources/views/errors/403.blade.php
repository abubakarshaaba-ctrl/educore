<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Access Denied</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#F1F5F9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:white;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);padding:48px 40px;max-width:460px;width:100%;text-align:center}
.icon{font-size:60px;margin-bottom:20px}
h1{font-size:22px;font-weight:800;color:#0F172A;margin-bottom:10px}
p{font-size:14px;color:#475569;line-height:1.6;margin-bottom:24px}
.role-pill{display:inline-block;background:#EFF6FF;color:#2563EB;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-bottom:20px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#2563EB;color:white;border-radius:9px;text-decoration:none;font-size:14px;font-weight:700}
.btn-g{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;margin-right:8px}
</style>
</head>
<body>
<div class="box">
    <div class="icon">🔒</div>
    @if(auth()->check())
    <div class="role-pill">{{ auth()->user()->roleLabel() }}</div>
    @endif
    <h1>Access Denied</h1>
    <p>{{ $exception->getMessage() ?: 'You do not have permission to view this page.' }}</p>
    <a href="{{ url()->previous() }}" class="btn btn-g">← Go Back</a>
    @if(auth()->check())
    <a href="{{ route(auth()->user()->isStudent() ? 'student.portal.dashboard' : (auth()->user()->isParent() ? 'parent.dashboard' : 'dashboard')) }}" class="btn">🏠 Home</a>
    @endif
</div>
</body>
</html>
