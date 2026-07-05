<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EduCore Staff App — Coming Soon</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
     font-family:Inter,system-ui,sans-serif;background:linear-gradient(145deg,#061733,#071E45 60%,#0A1628);padding:20px}
.box{max-width:440px;text-align:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.12);
     border-radius:18px;padding:44px 34px;color:#fff}
.icon{font-size:52px;margin-bottom:16px}
h1{font-size:24px;font-weight:800;margin:0 0 10px}
h1 span{color:#F2C35B}
p{color:rgba(255,255,255,.68);font-size:14.5px;line-height:1.65;margin:0 0 26px}
a{display:inline-flex;align-items:center;gap:6px;padding:11px 24px;background:#D79A21;color:#071E45;
  border-radius:10px;font-size:14px;font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="box">
    <div class="icon">📱</div>
    <h1>EduCore <span>Staff App</span></h1>
    <p>The Android app is being prepared for release. Check back shortly —
       staff will be able to mark class attendance, clock in with the school QR,
       and follow announcements right from their phone.</p>
    <a href="{{ url('/') }}">&larr; Back to EduCore</a>
</div>
</body>
</html>
