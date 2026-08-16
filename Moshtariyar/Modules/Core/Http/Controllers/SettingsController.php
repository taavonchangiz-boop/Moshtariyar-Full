<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Entities\Setting;
use Modules\Core\Support\ImageOptimizer;

/**
 * مرکز تنظیمات سیستم (نمای واحد + هر ۹ تب)
 *
 * قوانین:
 *  - همه فیلدهای ارسال‌شده در جدول settings ذخیره می‌شوند
 *  - چک‌باکس‌های خاموش هم صراحتاً روی '0' ست می‌شوند
 *  - لوگو با ImageOptimizer به WebP تبدیل می‌شود
 *  - کش تنظیمات پس از هر تغییر پاک می‌شود
 */
class SettingsController extends Controller
{
    /** فهرست تمام checkbox و radio ها — برای مقدار پیش‌فرض '0' اگر ارسال نشدند */
    protected array $booleanFields = [
        // finance
        'moadian_active',
        // notifications events
        'notif_order', 'notif_ticket', 'notif_payment', 'notif_campaign',
        'notif_order_customer', 'notif_ticket_customer', 'notif_low_stock',
        'notif_daily_report', 'notif_birthday', 'notif_abandoned_cart',
        'notif_new_review', 'notif_membership_expire',
        // recovery report
        'recovery_report_enabled', 'recovery_report_evening_enabled',
        // notifications enable channels
        'sms_enabled', 'email_enabled', 'telegram_enabled',
        'bale_enabled', 'rubika_enabled', 'eitaa_enabled', 'whatsapp_enabled',
        // branding (کامل)
        'invoice_show_logo', 'invoice_show_sig',
        'invoice_show_stamp', 'invoice_show_watermark',
        'invoice_show_barcode', 'invoice_show_qr',
        'invoice_show_notes', 'invoice_show_total_word',
        // security
        'security_two_fa', 'security_log_activity',
        'security_force_strong_password', 'security_lock_after_fail',
        'security_ip_whitelist_enabled',
        // 2FA channels
        'two_fa_sms', 'two_fa_email', 'two_fa_totp', 'two_fa_admin_only',
        // backup
        'backup_auto', 'backup_include_files',
        'backup_target_local', 'backup_target_hosting', 'backup_target_google', 'backup_target_ftp',
        // seo
        'seo_sitemap_auto', 'seo_open_graph_enabled', 'seo_schema_enabled', 'seo_share_enabled',
        // woocommerce
        'woocommerce_active',
        // gateways
        'gw_zarinpal_active', 'gw_zibal_active', 'gw_idpay_active', 'gw_nextpay_active',
        'gw_payping_active', 'gw_payir_active', 'gw_jibimo_active', 'gw_vandar_active',
        'gw_aqayepardakht_active', 'gw_mellat_active', 'gw_parsian_active', 'gw_saman_active',
        'gw_saderat_active', 'gw_melli_active', 'gw_pasargad_active', 'gw_novin_active',
        'gw_asanpardakht_active', 'gw_iranpay_active',
        'gw_zarinpal_sandbox', 'gw_idpay_sandbox',
    ];

    /** ================== نمای اصلی مرکز تنظیمات ================== */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'business');
        return view('app.settings', compact('tab'));
    }

    /** ================== ذخیره تنظیمات (هر تب) ================== */
    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method', '_tab']);
        $tab  = $request->input('_tab', 'business');

        // چک‌باکس‌های موجود در این تب که در POST نیامدند = خاموش → '0'
        foreach ($this->booleanFields as $field) {
            if (! array_key_exists($field, $data)) {
                if (! empty($data)) {
                    $data[$field] = '0';
                }
            }
        }

        // آپلود لوگو فاکتور (تب branding)
        if ($request->hasFile('invoice_logo_file')) {
            $file = $request->file('invoice_logo_file');
            $validator = Validator::make(
                ['logo' => $file],
                ['logo' => 'image|mimes:jpg,jpeg,png,gif,bmp,webp|max:4096']
            );
            if ($validator->passes()) {
                $absolute = public_path('uploads/branding');
                $saved = ImageOptimizer::saveAsWebp(
                    $file, $absolute, 'invoice-logo', 600, 90
                );
                if ($saved) {
                    $data['invoice_logo_url'] = '/uploads/branding/' . basename($saved);
                }
            }
            unset($data['invoice_logo_file']);
        }

        // آپلود لوگوی سایت (تب طراحی)
        if ($request->hasFile('site_logo_file')) {
            $file = $request->file('site_logo_file');
            $validator = Validator::make(
                ['logo' => $file],
                ['logo' => 'image|mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:4096']
            );
            if ($validator->passes()) {
                $ext = strtolower($file->getClientOriginalExtension());
                if ($ext === 'svg') {
                    $absolute = public_path('uploads/branding');
                    if (!is_dir($absolute)) @mkdir($absolute, 0755, true);
                    $filename = 'site-logo-' . time() . '.svg';
                    $file->move($absolute, $filename);
                    $data['site_logo_url'] = '/uploads/branding/' . $filename;
                } else {
                    $absolute = public_path('uploads/branding');
                    $saved = ImageOptimizer::saveAsWebp(
                        $file, $absolute, 'site-logo-' . time(), 800, 90
                    );
                    if ($saved) {
                        $data['site_logo_url'] = '/uploads/branding/' . basename($saved);
                    }
                }
            }
            unset($data['site_logo_file']);
        }

        // آپلود لوگوی باشگاه
        if ($request->hasFile('club_logo_file')) {
            $file = $request->file('club_logo_file');
            $validator = \Illuminate\Support\Facades\Validator::make(
                ['logo' => $file],
                ['logo' => 'image|mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:4096']
            );
            if ($validator->passes()) {
                $ext = strtolower($file->getClientOriginalExtension());
                $absolute = public_path('uploads/branding');
                if (!is_dir($absolute)) @mkdir($absolute, 0755, true);
                if ($ext === 'svg') {
                    $filename = 'club-logo-' . time() . '.svg';
                    $file->move($absolute, $filename);
                    $data['club_logo_url'] = '/uploads/branding/' . $filename;
                } else {
                    $saved = \Modules\Core\Support\ImageOptimizer::saveAsWebp(
                        $file, $absolute, 'club-logo-' . time(), 800, 90
                    );
                    if ($saved) {
                        $data['club_logo_url'] = '/uploads/branding/' . basename($saved);
                    }
                }
            }
            unset($data['club_logo_file']);
        }

        // آپلود فاوآیکن
        if ($request->hasFile('site_favicon_file')) {
            $file = $request->file('site_favicon_file');
            $validator = Validator::make(
                ['favicon' => $file],
                ['favicon' => 'image|mimes:jpg,jpeg,png,gif,bmp,webp,ico,svg|max:2048']
            );
            if ($validator->passes()) {
                $ext = strtolower($file->getClientOriginalExtension());
                $absolute = public_path('uploads/branding');
                if (!is_dir($absolute)) @mkdir($absolute, 0755, true);
                if (in_array($ext, ['ico','svg'])) {
                    $filename = 'favicon-' . time() . '.' . $ext;
                    $file->move($absolute, $filename);
                    $data['site_favicon_url'] = '/uploads/branding/' . $filename;
                } else {
                    $saved = ImageOptimizer::saveAsWebp(
                        $file, $absolute, 'favicon-' . time(), 256, 90
                    );
                    if ($saved) {
                        $data['site_favicon_url'] = '/uploads/branding/' . basename($saved);
                    }
                }
            }
            unset($data['site_favicon_file']);
        }

        // ذخیره در جدول settings (مستقیم با DB برای سازگاری کامل با هر نسخه Setting model)
        foreach ($data as $key => $value) {
            if (is_null($value)) $value = '';
            if (is_array($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            $this->putSetting($key, (string) $value);
        }

        // پاک کردن کش تنظیمات
        $this->clearSettingsCache();

        return redirect()->to('/app/settings?tab=' . urlencode($tab))
                         ->with('success', 'تنظیمات ذخیره شد.');
    }

    /** ================== حذف لوگو ================== */
    public function removeLogo(Request $request)
    {
        $current = $this->getSetting('invoice_logo_url', '');
        if (!empty($current)) {
            ImageOptimizer::deleteIfExists($current);
            $this->putSetting('invoice_logo_url', '');
        }
        $this->clearSettingsCache();
        return redirect()->to('/app/settings?tab=branding')->with('success', 'لوگو حذف شد.');
    }

    /** ================== حذف لوگوی سایت ================== */
    public function removeSiteLogo(Request $request)
    {
        $current = $this->getSetting('site_logo_url', '');
        if (!empty($current)) {
            ImageOptimizer::deleteIfExists($current);
            $abs = public_path(ltrim($current, '/'));
            if (file_exists($abs)) @unlink($abs);
            $this->putSetting('site_logo_url', '');
        }
        $this->clearSettingsCache();
        return redirect()->to('/app/settings?tab=design')->with('success', 'لوگوی سایت حذف شد.');
    }

    /** ================== حذف لوگوی باشگاه ================== */
    public function removeClubLogo(Request $request)
    {
        $current = $this->getSetting('club_logo_url', '');
        if (!empty($current)) {
            \Modules\Core\Support\ImageOptimizer::deleteIfExists($current);
            $abs = public_path(ltrim($current, '/'));
            if (file_exists($abs)) @unlink($abs);
            $this->putSetting('club_logo_url', '');
        }
        $this->clearSettingsCache();
        return redirect()->to('/app/settings?tab=design')->with('success', 'لوگوی باشگاه حذف شد.');
    }

    /** ================== حذف فاوآیکن ================== */
    public function removeFavicon(Request $request)
    {
        $current = $this->getSetting('site_favicon_url', '');
        if (!empty($current)) {
            ImageOptimizer::deleteIfExists($current);
            $abs = public_path(ltrim($current, '/'));
            if (file_exists($abs)) @unlink($abs);
            $this->putSetting('site_favicon_url', '');
        }
        $this->clearSettingsCache();
        return redirect()->to('/app/settings?tab=design')->with('success', 'فاوآیکن حذف شد.');
    }

    /** ================== افزودن / ویرایش کاربر ================== */
    public function saveUser(Request $request, $id = null)
    {
        if (! Schema::hasTable('users')) {
            return back()->with('error', 'جدول کاربران در دیتابیس وجود ندارد.');
        }

        $isEdit = $id !== null;
        $rules = [
            'name'     => 'required|string|max:150',
            'email'    => 'nullable|email|max:190',
            'phone'    => 'nullable|string|max:20',
            'role'     => 'nullable|string|max:50',
            'password' => $isEdit ? 'nullable|string|min:6|max:100' : 'required|string|min:6|max:100',
        ];
        $validated = $request->validate($rules);

        $row = [
            'name'   => $validated['name'],
            'email'  => $validated['email'] ?? null,
            'phone'  => $validated['phone'] ?? null,
            'role'   => $validated['role']  ?? 'user',
            'active' => $request->has('active') ? 1 : 0,
            'updated_at' => now(),
        ];

        if (! Schema::hasColumn('users', 'phone')) unset($row['phone']);
        if (! Schema::hasColumn('users', 'role'))  unset($row['role']);
        if (! Schema::hasColumn('users', 'active')) {
            if (Schema::hasColumn('users', 'is_active')) {
                $row['is_active'] = $row['active']; unset($row['active']);
            } else {
                unset($row['active']);
            }
        }

        if ($isEdit) {
            if (! empty($validated['password'])) {
                $row['password'] = Hash::make($validated['password']);
            }
            DB::table('users')->where('id', $id)->update($row);
        } else {
            $row['password']   = Hash::make($validated['password']);
            $row['created_at'] = now();
            DB::table('users')->insert($row);
        }

        return redirect()->to('/app/settings?tab=users')->with('success', $isEdit ? 'کاربر ویرایش شد.' : 'کاربر اضافه شد.');
    }

    /** ================== حذف کاربر ================== */
    public function deleteUser($id)
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('id', $id)->delete();
        }
        return redirect()->to('/app/settings?tab=users')->with('success', 'کاربر حذف شد.');
    }

    /* ==================== توابع کمکی ==================== */

    protected function putSetting(string $key, string $value): void
    {
        if (method_exists(Setting::class, 'put')) {
            try { Setting::put($key, $value); return; } catch (\Throwable $e) {}
        }
        if (method_exists(Setting::class, 'set')) {
            try { Setting::set($key, $value); return; } catch (\Throwable $e) {}
        }
        if (Schema::hasTable('settings')) {
            $columns = Schema::getColumnListing('settings');
            $keyCol = in_array('key', $columns) ? 'key' : (in_array('name', $columns) ? 'name' : 'key');

            $row = ['value' => $value];
            if (in_array('updated_at', $columns)) $row['updated_at'] = now();

            $exists = DB::table('settings')->where($keyCol, $key)->exists();
            if ($exists) {
                DB::table('settings')->where($keyCol, $key)->update($row);
            } else {
                $row[$keyCol] = $key;
                if (in_array('created_at', $columns)) $row['created_at'] = now();
                DB::table('settings')->insert($row);
            }
        }
    }

    protected function getSetting(string $key, $default = null)
    {
        if (method_exists(Setting::class, 'get')) {
            try { return Setting::get($key, $default); } catch (\Throwable $e) {}
        }
        if (Schema::hasTable('settings')) {
            $columns = Schema::getColumnListing('settings');
            $keyCol = in_array('key', $columns) ? 'key' : (in_array('name', $columns) ? 'name' : 'key');
            $val = DB::table('settings')->where($keyCol, $key)->value('value');
            return $val ?? $default;
        }
        return $default;
    }

    protected function clearSettingsCache(): void
    {
        try {
            Cache::forget('crm_cachesettings.all');
            Cache::forget('settings.all');
            Cache::forget('settings');
            if (method_exists(Setting::class, 'clearCache')) Setting::clearCache();
            if (method_exists(Setting::class, 'flush')) Setting::flush();
        } catch (\Throwable $e) {}
    }
}