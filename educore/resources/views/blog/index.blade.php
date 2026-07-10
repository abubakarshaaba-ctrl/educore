<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Guides on school management, admissions, fees, and running a Nigerian K-12 school more efficiently — from the EduCore team.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://educoreng.online/blog">
<title>Blog — EduCore</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:#0B1D3A;background:#F8FAFC;line-height:1.6}
.nav{background:#071E45;padding:16px 5vw;display:flex;align-items:center;justify-content:space-between}
.nav a{color:white;text-decoration:none;font-size:14px;font-weight:600}
.brand{display:flex;align-items:center;gap:10px;color:white;font-weight:800;font-size:17px}
.brand span{color:#D79A21}
.wrap{max-width:800px;margin:0 auto;padding:64px 24px}
h1{font-size:36px;font-weight:800;letter-spacing:-0.02em;margin-bottom:12px}
.sub{color:#64748B;font-size:15px;margin-bottom:48px}
.post{background:white;border:1px solid #E2E8F0;border-radius:14px;padding:28px;margin-bottom:18px;text-decoration:none;display:block;transition:box-shadow 150ms}
.post:hover{box-shadow:0 8px 24px rgba(11,29,58,0.08)}
.post h2{font-size:19px;font-weight:700;color:#0B1D3A;margin-bottom:8px}
.post p{color:#475569;font-size:14px}
.post .cta{color:#1A56DB;font-size:13px;font-weight:700;margin-top:12px;display:inline-block}
footer{text-align:center;padding:32px;color:#94A3B8;font-size:12px}
</style>
</head>
<body>
<nav class="nav">
    <a href="/" class="brand">Edu<span>Core</span></a>
    <a href="/get-started">Start Free Trial →</a>
</nav>
<div class="wrap">
    <h1>EduCore Blog</h1>
    <p class="sub">Practical guides on running a school in Nigeria — admissions, fees, payroll, and more.</p>

    @forelse($posts as $post)
    <a href="{{ route('blog.show', $post['slug']) }}" class="post">
        <h2>{{ $post['title'] }}</h2>
        <p>{{ $post['description'] }}</p>
        <span class="cta">Read more →</span>
    </a>
    @empty
    <p style="color:#94A3B8">No posts published yet.</p>
    @endforelse
</div>
<footer>&copy; {{ date('Y') }} EduCore. All rights reserved.</footer>
</body>
</html>
