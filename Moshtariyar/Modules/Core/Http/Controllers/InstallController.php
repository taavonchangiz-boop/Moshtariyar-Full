<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstallController extends Controller
{
    public function index()
    {
        $requirements = [
            'php' => [
                'display' => 'نسخه PHP 8.2+',
                'status' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            'bcmath' => [
                'display' => 'افزونه BCMath',
                'status' => extension_loaded('bcmath'),
            ],
            'ctype' => [
                'display' => 'افزونه Ctype',
                'status' => extension_loaded('ctype'),
            ],
            'fileinfo' => [
                'display' => 'افزونه Fileinfo',
                'status' => extension_loaded('fileinfo'),
            ],
            'json' => [
                'display' => 'افزونه JSON',
                'status' => extension_loaded('json'),
            ],
            'mbstring' => [
                'display' => 'افزونه Mbstring',
                'status' => extension_loaded('mbstring'),
            ],
            'openssl' => [
                'display' => 'افزونه OpenSSL',
                'status' => extension_loaded('openssl'),
            ],
            'pdo' => [
                'display' => 'افزونه PDO',
                'status' => extension_loaded('pdo'),
            ],
            'tokenizer' => [
                'display' => 'افزونه Tokenizer',
                'status' => extension_loaded('tokenizer'),
            ],
            'xml' => [
                'display' => 'افزونه XML',
                'status' => extension_loaded('xml'),
            ],
        ];

        return view('install.index', compact('requirements'));
    }

    public function dbConfig()
    {
        return view('install.db_config');
    }

    public function install(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_url'    => 'required|string', 
            'db_host'    => 'required|string',
            'db_database'=> 'required|string',
            'db_username'=> 'required|string',
            'db_password'=> 'nullable|string',
            'admin_email'=> 'required|email',
            'admin_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return view('install.db_config', [
                'errors' => $validator->errors(),
                'old' => $request->all()
            ]);
        }

        $data = $request->all();
        $appUrl = $data['app_url'];
        if (!preg_match("~^(https?://)~i", $appUrl)) {
            $appUrl = 'https://' . $appUrl;
        }

        try {
            $key = base64_encode(random_bytes(32));

            $envContent = "APP_NAME=\"ERP CRM\"\n";
            $envContent .= "APP_ENV=production\n";
            $envContent .= "APP_KEY=base64:{$key}\n";
            $envContent .= "APP_DEBUG=false\n";
            $envContent .= "APP_URL=" . $appUrl . "\n";
            $envContent .= "\n";
            $envContent .= "DB_CONNECTION=mysql\n";
            $envContent .= "DB_HOST=" . $data['db_host'] . "\n";
            $envContent .= "DB_PORT=3306\n";
            $envContent .= "DB_DATABASE=" . $data['db_database'] . "\n";
            $envContent .= "DB_USERNAME=" . $data['db_username'] . "\n";
            $envContent .= "DB_PASSWORD=" . $data['db_password'] . "\n";
            $envContent .= "\n";
            $envContent .= "CACHE_DRIVER=file\n";
            $envContent .= "SESSION_DRIVER=file\n";
            $envContent .= "QUEUE_CONNECTION=sync\n";

            if (false === @file_put_contents(base_path('.env'), $envContent)) {
                throw new \Exception("سیستم اجازه نوشتن فایل .env در ریشه پروژه را ندارد.");
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Exception $e) {
                throw new \Exception("خطا در اتصال به دیتابیس یا ساخت جداول: " . $e->getMessage());
            }

            if (!File::exists(storage_path())) {
                File::makeDirectory(storage_path(), 0755, true);
            }
            File::put(storage_//path('installed.lock'), date('Y-m-d H:i:s'));

            return redirect('/')->with('status', 'سیستم با موفقیت نصب شد. خوش آمدید!');

        } catch (\Exception $e) {
            Log::error('Installation Error: ' . $e->getMessage());
            return view('install.db_config', [
                'errors' => ['error' => 'خطای سیستمی: ' . $e->getMessage()],
                'old' => $request->all()
            ]);
        }
    }

    /**
     * ایجاد داده‌های نمونه برای تست قابلیت‌های AI
     */
    public function seedTestData()
    {
        try {
            // ۱. ساخت چند مشتری با رفتارهای متفاوت
            $customers = [
                ['full_name' => 'علی محمدی', 'email' => 'ali@test.com', 'phone' => '09121111111', 'lifetime_value' => 5000000],
                ['full_name' => 'سارا رضایی', 'email' => 'sara@test.com', 'phone' => '09122222222', 'lifetime_value' => 1000000],
                ['full_name' => 'رضا احمدی', 'email' => 'reza@test.com', 'phone' => '09123333333', 'lifetime_value' => 50000],
                ['full_name' => 'مریم حسینی', 'email' => 'maryam@test.com', 'phone' => '09124444444', 'lifetime_value' => 12000000],
                ['full_name' => 'حسین کریمی', 'email' => 'hossein@test.com', 'phone' => '09125555555', 'lifetime_value' => 0],
            ];

            foreach ($customers as $c) {
                $id = DB::table('customers')->insertGetId($c);
                DB::table('loyalty_members')->insert([
                    'customer_id' => $id,
                    'points' => rand(100, 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ۲. ساخت یک گروه (Segment) برای تست
            $segmentId = DB::table('segments')->insertGetId([
                'name' => 'مشتریان ویژه VIP',
                'description' => 'گروه تست برای AI',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // اضافه کردن دو نفر به این گروه
            $customerIds = DB::table('customers')->pluck('id')->take(2);
            foreach ($customerIds as $cid) {
                DB::table('segment_member')->insert([
                    'segment_id' => $segmentId,
                    'customer_id' => $cid,
                    'created_at' => now(),
                ]);
            }

            return "✅ داده‌های تست با موفقیت ایجاد شدند! حالا می‌توانید AI را تست کنید.";
        } catch (\Exception $e) {
            return "خطا در ایجاد داده‌ها: " . $e->getMessage();
        }
    }
}