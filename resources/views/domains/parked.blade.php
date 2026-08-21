<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $host }} — Coming Soon</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0b1220 0%, #12305e 60%, #1d4ed8 100%);
            color: #fff;
            padding: 24px;
        }
        .card { max-width: 560px; text-align: center; }
        .globe { font-size: 56px; margin-bottom: 20px; }
        h1 { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; word-break: break-all; }
        h1 span { color: #7dd3fc; }
        p { margin-top: 14px; font-size: 16px; line-height: 1.65; color: #cbd5e1; }
        .badge {
            display: inline-block; margin-top: 28px; padding: 10px 22px;
            border: 1px solid rgba(255,255,255,.25); border-radius: 999px;
            font-size: 14px; color: #e2e8f0; text-decoration: none;
            transition: background .2s;
        }
        .badge:hover { background: rgba(255,255,255,.1); }
        .foot { margin-top: 36px; font-size: 13px; color: #94a3b8; }
        .foot a { color: #7dd3fc; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="globe">🌐</div>
        <h1><span>{{ $host }}</span></h1>
        <p>This domain has been registered and is ready to launch.<br>The website is coming soon — stay tuned!</p>
        <a class="badge" href="https://jamunasoft.com" rel="noopener">Domains &amp; Hosting by Jamuna Soft</a>
        <div class="foot">Want a website on this domain? <a href="https://jamunasoft.com/contact">Talk to us</a></div>
    </div>
</body>
</html>
