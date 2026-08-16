<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم ERP CRM</title>
    <style>
        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .installer-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
            color: #f8fafc;
        }
        .header p {
            color: #94a3b8;
            font-size: 14px;
        }
        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #334155;
            font-size: 14px;
        }
        .requirement-item:last-child {
            border-bottom: none;
        }
        .status {
            font-weight: bold;
        }
        .status-ok { color: #10b981; }
        .status-fail { color: #ef4444; }
        .btn {
            display: block;
            width: 100%;
            text-align: center;
            background-color: #3b82f6;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 25px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .btn:disabled {
            background-color: #475569;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .btn:hover:not(:disabled) {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="installer-card">
        <div class="header">
            <h1>🚀 نصب سیستم ERP CRM</h1>
            <p>بررسی پیش‌نیازهای سرور برای اجرای بهینه</p>
        </div>

        <div class="requirements-list">
            @foreach($requirements as $key => $req)
                <div class="requirement-item">
                    <span>{{ $req['display'] }}</span>
                    <span class="status {{ $req['status'] ? 'status-ok' : 'status-fail' }}">
                        {{ $req['status'] ? '✓' : '✕' }}
                    </span>
                </div>
            @endforeach
        </div>

        @php
            $allOk = true;
            foreach($requirements as $req) if(!$req['status']) $allOk = false;
        @endphp

        <a href="{{ route('install.db') }}" class="btn {{ !$allOk ? 'disabled' : '' }}" {{ !$allOk ? 'onclick="alert(\'لطفاً ابتدا پیش‌نیازهای سرور را با پشتیبان هاست خود بررسی کنید.\')"' : '' }}>
            مرحله بعد: تنظیمات دیتابیس
        </a>
    </div>
</body>
</html>