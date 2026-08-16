<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیمات دیتابیس - نصب ERP CRM</title>
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
            max-width: 600px;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #94a3b8;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #334155;
            background-color: #0f172a;
            color: white;
            font-family: inherit;
            box-sizing: border-box;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .error-box {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
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
        .btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="installer-card">
        <div class="header">
            <h1>⚙️ تنظیمات دیتابیس و مدیر</h1>
            <p>اطلاعات لازم برای راه‌اندازی سیستم را وارد کنید</p>
        </div>

        @if($errors->any())
            <div class="error-box">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('install.process') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label>آدرس سایت (URL)</label>
                <input type="url" name="app_url" class="form-control" placeholder="https://demohw.ir" value="{{ old('app_url', request()->root()) }}" required>
            </div>

            <div class="grid">
                <div class="form-group">
                    <label>میزبان دیتابیس (Host)</label>
                    <input type="text" name="db_host" class="form-control" value="{{ old('db_host', '127.0.0.1') }}" required>
                </div>
                <div class="form-group">
                    <label>نام دیتابیس</label>
                    <input type="text" name="db_database" class="form-control" placeholder="db_name" value="{{ old('db_database') }}" required>
                </div>
                <div class="form-group">
                    <label>نام کاربری دیتابیس</label>
                    <input type="text" name="db_username" class="form-control" placeholder="db_user" value="{{ old('db_username') }}" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور دیتابیس</label>
                    <input type="password" name="db_password" class="form-control" value="{{ old('db_password') }}" required>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #334155; margin: 20px 0;">

            <div class="grid">
                <div class="form-group">
                    <label>ایمیل مدیر سیستم</label>
                    <input type="email" name="admin_email" class="form-control" placeholder="admin@example.com" value="{{ old('admin_email') }}" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور مدیر</label>
                    <input type="password" name="admin_password" class="form-control" placeholder="******" value="{{ old('admin_password') }}" required>
                </div>
            </div>

            <button type="submit" class="btn">نصب و راه‌اندازی نهایی</button>
        </form>
    </div>
</body>
</html>