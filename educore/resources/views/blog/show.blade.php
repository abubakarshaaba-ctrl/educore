<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="{{ $meta['meta_description'] ?? '' }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://educoreng.online/blog/{{ $slug }}">
<title>{{ $meta['title'] ?? 'EduCore Blog' }} — EduCore</title>
<link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">

<meta property="og:type" content="article">
<meta property="og:title" content="{{ $meta['title'] ?? '' }}">
<meta property="og:description" content="{{ $meta['meta_description'] ?? '' }}">
<meta property="og:url" content="https://educoreng.online/blog/{{ $slug }}">
<meta property="og:image" content="https://educoreng.online/brand/og-image.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $meta['title'] ?? '' }}">
<meta name="twitter:description" content="{{ $meta['meta_description'] ?? '' }}">
<meta name="twitter:image" content="https://educoreng.online/brand/og-image.png">

<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:#0B1D3A;background:#F8FAFC;line-height:1.7}
.nav{background:#071E45;padding:16px 5vw;display:flex;align-items:center;justify-content:space-between}
.nav a{color:white;text-decoration:none;font-size:14px;font-weight:600}
.brand{display:flex;align-items:center;gap:10px;color:white;font-weight:800;font-size:17px}
.brand span{color:#D79A21}
.wrap{max-width:720px;margin:0 auto;padding:64px 24px;background:white}
.back{color:#1A56DB;text-decoration:none;font-size:13px;font-weight:600;display:inline-block;margin-bottom:24px}
h1{font-size:32px;font-weight:800;letter-spacing:-0.02em;margin-bottom:32px;color:#0B1D3A}
article h1{font-size:26px;margin-top:32px;margin-bottom:14px}
article h2{font-size:21px;font-weight:700;margin-top:32px;margin-bottom:14px;color:#0B1D3A}
article h3{font-size:17px;font-weight:700;margin-top:24px;margin-bottom:10px;color:#0B1D3A}
article p{margin-bottom:16px;color:#334155;font-size:15.5px}
article ul,article ol{margin:0 0 16px 24px;color:#334155;font-size:15.5px}
article li{margin-bottom:6px}
article a{color:#1A56DB}
.cta-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:24px;margin-top:40px;text-align:center}
.cta-box a{display:inline-block;margin-top:12px;background:#1A56DB;color:white;padding:11px 24px;border-radius:9px;text-decoration:none;font-weight:700;font-size:14px}
footer{text-align:center;padding:32px;color:#94A3B8;font-size:12px}
</style>
</head>
<body>
<nav class="nav">
    <a href="/" class="brand">Edu<span>Core</span></a>
    <a href="/get-started">Start Free Trial →</a>
</nav>
<div class="wrap">
    <a href="{{ route('blog.index') }}" class="back">← All Posts</a>
    <h1>{{ $meta['title'] ?? '' }}</h1>
    <article>{!! $html !!}</article>

    <div class="cta-box">
        <div style="font-weight:700;font-size:16px">Ready to run your school on EduCore?</div>
        <p style="color:#475569;font-size:13px;margin-top:6px">Free for schools up to 20 students — every feature included.</p>
        <a href="/get-started">Start Free Trial →</a>
    </div>
</div>
<footer>&copy; {{ date('Y') }} EduCore. All rights reserved.</footer>
</body>
</html>
