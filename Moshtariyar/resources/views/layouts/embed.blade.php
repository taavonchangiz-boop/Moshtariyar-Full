<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'دستیار هوشمند')</title>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <style>
        :root{
            --bg:#f3f7fb; --panel:#ffffff; --panel2:#f8fafc; --line:#e5edf7;
            --txt:#0f172a; --mut:#64748b; --acc:#2563eb; --acc2:#0ea5e9;
            --ok:#10b981; --warn:#f59e0b; --bad:#ef4444;
        }
        body.dark{--bg:#0f172a;--panel:#1e293b;--panel2:#172033;--line:#334155;--txt:#e2e8f0;--mut:#aeb9c9;--acc:#38bdf8;--acc2:#0ea5e9;}
        body.light{--bg:#f3f7fb;--panel:#ffffff;--panel2:#f8fafc;--line:#e5edf7;--txt:#0f172a;--mut:#64748b;--acc:#2563eb;--acc2:#0ea5e9;}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--txt);font-size:14px;line-height:1.7;
             font-family:var(--font-fa,Tahoma,sans-serif);overflow:hidden}
        
        .embed-container{padding:15px;height:100vh;display:flex;flex-direction:column;gap:15px}
        
        /* استایل‌های ضروری برای اجزای چت */
        .card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:18px;margin-bottom:16px}
        .user-msg,.bot-msg{padding:10px 12px;border-radius:16px;white-space:pre-wrap;font-size:.9rem;display:flex;flex-direction:column;gap:8px}
        .user-msg{background:linear-gradient(135deg,var(--acc2),var(--acc));color:#001018;align-self:flex-start;max-width:82%}
        .bot-msg{background:var(--panel2);border:1px solid var(--line);align-self:flex-end;max-width:86%}
        .chat-form{display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;margin-top:12px}
        .chat-text{background:var(--panel2);border:1px solid var(--line);color:var(--txt);border-radius:10px;padding:10px 12px;width:100%;font-family:inherit;font-size:14px}
        .btn{background:var(--acc2);color:#001018;border:none;border-radius:10px;padding:10px 18px;cursor:pointer;font-family:inherit;font-weight:700;font-size:14px}
        .suggestion-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
        .suggestion-chip{background:var(--acc);color:#001018;border:0;border-radius:12px;padding:6px 10px;font-size:.75rem;cursor:pointer;font-weight:bold}
        .rich-card{background:rgba(255,255,255,0.05);border:1px solid var(--line);border-radius:12px;padding:12px;margin:8px 0}
        .stat-grid{display:grid;grid-template-columns:repeat(2, 1fr);gap:10px;margin:10px 0}
        .stat-item{background:var(--panel2);padding:8px;border-radius:10px;border:1px solid var(--line);text-align:center}
        .stat-val{display:block;font-weight:800;color:var(--acc);font-size:1rem}
        .stat-lbl{font-size:.7rem;color:var(--mut)}
    </style>
</head>
<body>
    <div class="embed-container">
        @yield('content')
    </div>
</body>
</html>