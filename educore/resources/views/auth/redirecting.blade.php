<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signing in… – EduCore</title>
    <meta http-equiv="refresh" content="0;url={{ $url }}">
    <style>
        body { margin: 0; display: flex; align-items: center; justify-content: center;
               min-height: 100vh; font-family: system-ui, sans-serif; background: #f8fafc; }
        .wrap { text-align: center; color: #64748b; }
        .spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0;
                   border-top-color: #1e40af; border-radius: 50%;
                   animation: spin .7s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="spinner"></div>
        <p>Signing you in&hellip;</p>
    </div>
    <script>
        // Immediate JS redirect — meta-refresh is the fallback
        window.location.replace({{ Js::from($url) }});
    </script>
</body>
</html>
