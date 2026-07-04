<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Session Expired</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#F1F5F9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:white;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);padding:48px 40px;max-width:460px;width:100%;text-align:center}
.icon{font-size:60px;margin-bottom:20px}
h1{font-size:22px;font-weight:800;color:#0F172A;margin-bottom:10px}
p{font-size:14px;color:#475569;line-height:1.6;margin-bottom:24px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#071E45;color:white;border-radius:9px;text-decoration:none;font-size:14px;font-weight:700}
.btn-g{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;margin-right:8px}
</style>
</head>
<body>
<div class="box">
    <div class="icon">⏱</div>
    <h1>Session Expired</h1>
    <p>Your session timed out for security reasons. Please sign in again to continue.</p>
    <a href="{{ url()->previous() ?: url('/') }}" class="btn btn-g">← Go Back</a>
    <a href="{{ url('/') }}" class="btn" onclick="window.location.reload();return false;">Refresh Page</a>
</div>
</body>
</html>
