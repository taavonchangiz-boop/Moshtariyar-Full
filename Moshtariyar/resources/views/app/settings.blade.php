@extends('layouts.app')
@section('title','مرکز تنظیمات سیستم')
@section('heading','مرکز تنظیمات سیستم')
@section('subtitle','پیکربندی کامل کسب‌وکار، کاربران، اتصال‌ها، مالیات، پیام‌ها، ظاهر و امنیت')

@section('content')
<link rel="stylesheet" href="{{ asset('css/settings-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/finance-board.css') }}">

@php
    use Modules\Core\Entities\Setting;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    /* ---------- تابع‌های کمکی ---------- */
    if (!function_exists('to_fa_num')) {
        function to_fa_num($num) {
            $en = ['0','1','2','3','4','5','6','7','8','9'];
            $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return str_replace($en, $fa, (string) $num);
        }
    }
    if (!function_exists('safe_jdate')) {
        function safe_jdate($v) {
            if (empty($v)) return '—';
            try {
                $dt = $v instanceof \DateTimeInterface ? $v : \Carbon\Carbon::parse($v);
                return \Modules\Core\Support\Jalali::date($dt);
            } catch (\Throwable $e) {
                return '—';
            }
        }
    }

    /* ---------- پروفایل کسب‌وکار ---------- */
    $bizName     = Setting::get('business_name',      '');
    $bizBrand    = Setting::get('business_brand',     '');
    $bizPhone    = Setting::get('business_phone',     '');
    $bizMobile   = Setting::get('business_mobile',    '');
    $bizFax      = Setting::get('business_fax',       '');
    $bizEmail    = Setting::get('business_email',     '');
    $bizWebsite  = Setting::get('business_website',   'ayarpro.ir');
    $bizAddress  = Setting::get('business_address',   '');
    $bizProvince = Setting::get('business_province',  '');
    $bizCity     = Setting::get('business_city',      '');
    $bizPostal   = Setting::get('business_postal_code','');
    $bizBranch   = Setting::get('business_branch',    'دفتر مرکزی');
    $bizAbout    = Setting::get('business_about',     '');

    /* ---------- مالی و مالیات ---------- */
    $bizEconomic = Setting::get('business_economic_code',   '');
    $bizNational = Setting::get('business_national_id',     '');
    $bizRegNo    = Setting::get('business_registration_no', '');
    $bizVatNo    = Setting::get('business_vat_no',          '');
    $defaultVat  = Setting::get('default_vat_percent',      '9');
    $currency    = Setting::get('currency',                 'toman');
    $moadianKey  = Setting::get('moadian_api_key',          '');
    $moadianOn   = Setting::get('moadian_active',           '0');

    /* ---------- ظاهر و برند فاکتور (کامل) ---------- */
    $invTheme      = Setting::get('invoice_theme',        'classic');
    $invPageSize   = Setting::get('invoice_page_size',    'A4');
    $invPageOrient = Setting::get('invoice_page_orient',  'portrait');
    $invPrimary    = Setting::get('invoice_primary',      '#0f172a');
    $invAccent     = Setting::get('invoice_accent',       '#10b981');
    $invFontFamily = Setting::get('invoice_font_family',  'inherit');
    $invFontSize   = Setting::get('invoice_font_size',    'medium');
    $invMargin     = Setting::get('invoice_margin',       '12');
    $invBorderStyle= Setting::get('invoice_border_style', 'solid');
    $invBorderWidth= Setting::get('invoice_border_width', '2');
    $invHeaderBg   = Setting::get('invoice_header_bg',    'primary');
    $invTableStyle = Setting::get('invoice_table_style',  'bordered');
    $invShowLogo   = Setting::get('invoice_show_logo',    '1');
    $invLogoPos    = Setting::get('invoice_logo_position','right');
    $invShowSig    = Setting::get('invoice_show_sig',     '1');
    $invShowStamp  = Setting::get('invoice_show_stamp',   '0');
    $invStampText  = Setting::get('invoice_stamp_text',   'پرداخت شد');
    $invStampColor = Setting::get('invoice_stamp_color',  '#ef4444');
    $invShowWatermark = Setting::get('invoice_show_watermark', '0');
    $invWatermarkText = Setting::get('invoice_watermark_text', $bizName);
    $invShowBarcode= Setting::get('invoice_show_barcode', '0');
    $invShowQR     = Setting::get('invoice_show_qr',      '1');
    $invQrContent  = Setting::get('invoice_qr_content',   'auto');
    $invQrCustom   = Setting::get('invoice_qr_custom',    '');
    $invLogoText   = Setting::get('invoice_logo_text',    mb_substr($bizName, 0, 12));
    $invLogoUrl    = Setting::get('invoice_logo_url',     '');
    $invSeller     = Setting::get('invoice_seller_sig',   $bizName);
    $invTerms      = Setting::get('invoice_terms_text',   'مبلغ ثبت شده در این سند بر پایه توافق طرفین است و پس از پرداخت قطعی می‌گردد.');
    $invFooter     = Setting::get('invoice_footer_text',  'با تشکر از خرید شما');
    $invShowNotes  = Setting::get('invoice_show_notes',   '1');
    $invShowTotalWord = Setting::get('invoice_show_total_word', '1');

    /* ---------- شماره‌گذاری پیش‌فاکتور ---------- */
    $proPrefix   = Setting::get('proforma_serial_prefix',    'پف');
    $proSep      = Setting::get('proforma_serial_separator', '-');
    $proPad      = Setting::get('proforma_serial_padding',   5);
    $proDate     = Setting::get('proforma_serial_date',      'jalali');

    /* ---------- سرویس پیامک ---------- */
    $smsProv     = Setting::get('sms_provider',    'kavenegar');
    $smsSender   = Setting::get('sms_sender',      '');
    $smsEnabled  = Setting::get('sms_enabled',     '1');
    $smsCred = [
        'kavenegar'    => ['api_key' => Setting::get('sms_kavenegar_api_key', '')],
        'smsir'        => ['api_key' => Setting::get('sms_smsir_api_key', ''), 'line_number' => Setting::get('sms_smsir_line_number', '')],
        'melipayamak'  => ['username' => Setting::get('sms_melipayamak_username', ''), 'password' => Setting::get('sms_melipayamak_password', '')],
        'farapayamak'  => ['username' => Setting::get('sms_farapayamak_username', ''), 'password' => Setting::get('sms_farapayamak_password', '')],
        'ippanel'      => ['username' => Setting::get('sms_ippanel_username', ''), 'password' => Setting::get('sms_ippanel_password', ''), 'pattern_code' => Setting::get('sms_ippanel_pattern', '')],
        'mediana'      => ['api_key' => Setting::get('sms_mediana_api_key', '')],
        'farazsms'     => ['api_key' => Setting::get('sms_farazsms_api_key', '')],
        'payamak_yas'  => ['username' => Setting::get('sms_payamakyas_username', ''), 'password' => Setting::get('sms_payamakyas_password', '')],
        'asanak'       => ['username' => Setting::get('sms_asanak_username', ''), 'password' => Setting::get('sms_asanak_password', '')],
        'sabanovin'    => ['username' => Setting::get('sms_sabanovin_username', ''), 'password' => Setting::get('sms_sabanovin_password', '')],
    ];

    /* ---------- ایمیل ---------- */
    $mailEnabled = Setting::get('email_enabled',   '1');
    $mailFrom    = Setting::get('mail_from_address','');
    $mailName    = Setting::get('mail_from_name',  '');
    $mailHost    = Setting::get('mail_host',       '');
    $mailPort    = Setting::get('mail_port',       '587');
    $mailUser    = Setting::get('mail_username',   '');
    $mailPass    = Setting::get('mail_password',   '');
    $mailEnc     = Setting::get('mail_encryption', 'tls');

    /* ---------- کانال‌های پیام‌رسان ---------- */
    $tgEnabled   = Setting::get('telegram_enabled', '0');
    $tgToken     = Setting::get('telegram_bot_token','');
    $tgChatId    = Setting::get('telegram_chat_id','');

    $baleEnabled = Setting::get('bale_enabled',     '0');
    $baleToken   = Setting::get('bale_bot_token',   '');
    $baleChatId  = Setting::get('bale_chat_id',     '');

    $rubikaEnabled = Setting::get('rubika_enabled', '0');
    $rubikaToken   = Setting::get('rubika_bot_token','');
    $rubikaChatId  = Setting::get('rubika_chat_id', '');

    $eitaaEnabled  = Setting::get('eitaa_enabled',  '0');
    $eitaaToken    = Setting::get('eitaa_bot_token','');
    $eitaaChatId   = Setting::get('eitaa_chat_id',  '');

    $waEnabled     = Setting::get('whatsapp_enabled','0');
    $waProvider    = Setting::get('whatsapp_provider','wp_api');
    $waToken       = Setting::get('whatsapp_token',  '');
    $waInstance    = Setting::get('whatsapp_instance','');
    $waSender      = Setting::get('whatsapp_sender', '');

    /* ---------- رویدادهای اعلان ---------- */
    $notifOrder          = Setting::get('notif_order',          '1');
    $notifOrderCustomer  = Setting::get('notif_order_customer', '1');
    $notifTicket         = Setting::get('notif_ticket',         '1');
    $notifTicketCustomer = Setting::get('notif_ticket_customer','1');
    $notifPay            = Setting::get('notif_payment',        '1');
    $notifCamp           = Setting::get('notif_campaign',       '0');
    $notifLowStock       = Setting::get('notif_low_stock',      '1');
    $notifDailyReport    = Setting::get('notif_daily_report',   '0');
    $notifBirthday       = Setting::get('notif_birthday',       '0');
    $notifAbandonedCart  = Setting::get('notif_abandoned_cart', '0');
    $notifNewReview      = Setting::get('notif_new_review',     '1');
    $notifMemExpire      = Setting::get('notif_membership_expire','1');

    /* ---------- گزارش بازگشت و حفظ خودکار ---------- */
    $recoveryEnabled         = Setting::get('recovery_report_enabled', '1');
    $recoveryTime            = Setting::get('recovery_report_time', '09:00');
    $recoveryEveningEnabled  = Setting::get('recovery_report_evening_enabled', '0');
    $recoveryEveningTime     = Setting::get('recovery_report_evening_time', '21:00');
    $recoveryDaysJson        = Setting::get('recovery_report_days', '["6"]');
    $recoveryDaysArray       = json_decode($recoveryDaysJson, true);
    if (!is_array($recoveryDaysArray)) $recoveryDaysArray = [6];
    $recoveryEmail           = Setting::get('recovery_report_email', '');

    /* ---------- قالب‌های OTP پیامک ---------- */
    $smsirVerifyTemplate     = Setting::get('smsir_verify_template_id', Setting::get('sms_smsir_verify_template_id', ''));
    $kavenegarOtpTemplate    = Setting::get('kavenegar_otp_template', Setting::get('sms_kavenegar_otp_template', ''));
    $melipayamakBodyId       = Setting::get('melipayamak_otp_body_id', Setting::get('sms_melipayamak_otp_body_id', ''));
    $genericOtpTemplate      = Setting::get('otp_template_id', Setting::get('portal_otp_template_id', ''));

    /* ---------- ووکامرس (تشخیص خودکار فوق حرفه‌ای) ---------- */
    $wcUrl = Setting::get('woocommerce_url', '')
           ?: Setting::get('wc_url', '')
           ?: Setting::get('wc_site_url', '')
           ?: Setting::get('woocommerce_base_url', '')
           ?: Setting::get('wc_shop_url', '')
           ?: Setting::get('wp_url', '');
    $wcKey = Setting::get('woocommerce_key', '')
           ?: Setting::get('woocommerce_consumer_key', '')
           ?: Setting::get('wc_consumer_key', '')
           ?: Setting::get('wc_key', '')
           ?: Setting::get('wc_ck', '');
    $wcSecret = Setting::get('woocommerce_secret', '')
              ?: Setting::get('woocommerce_consumer_secret', '')
              ?: Setting::get('wc_consumer_secret', '')
              ?: Setting::get('wc_secret', '')
              ?: Setting::get('wc_cs', '');
    $wcVersion = Setting::get('woocommerce_version', 'wc/v3');
    $wcActiveRaw = Setting::get('woocommerce_active', null);
    foreach (['wc_active','wc_enabled','woocommerce_enabled','wc_connection_active','wc_status'] as $k) {
        if ($wcActiveRaw === null) $wcActiveRaw = Setting::get($k, null);
    }
    if ($wcActiveRaw === null) {
        $wcActiveRaw = (!empty($wcUrl) && !empty($wcKey) && !empty($wcSecret)) ? '1' : '0';
    }
    $wcActive = ((string) $wcActiveRaw === '1' || $wcActiveRaw === true || $wcActiveRaw === 1 || $wcActiveRaw === 'active' || $wcActiveRaw === 'enabled') ? '1' : '0';
    foreach (['wc_connections','woocommerce_connections','wc_settings','woocommerce_settings','wc_stores','woocommerce_stores'] as $tableName) {
        if ($wcActive === '1') break;
        try {
            if (Schema::hasTable($tableName)) {
                $q = DB::table($tableName);
                if (Schema::hasColumn($tableName, 'is_active')) $q->where('is_active', 1);
                elseif (Schema::hasColumn($tableName, 'active')) $q->where('active', 1);
                elseif (Schema::hasColumn($tableName, 'status')) $q->whereIn('status', ['active','enabled','1']);
                if ($q->exists()) $wcActive = '1';
            }
        } catch (\Throwable $e) {}
    }
    if ($wcActive !== '1') {
        foreach (['wc_sync_logs','woocommerce_sync_logs','sync_logs','wc_logs'] as $tableName) {
            if ($wcActive === '1') break;
            try {
                if (Schema::hasTable($tableName)) {
                    $q = DB::table($tableName);
                    if (Schema::hasColumn($tableName, 'created_at')) {
                        $q->where('created_at', '>=', now()->subDays(30));
                    }
                    if ($q->exists()) $wcActive = '1';
                }
            } catch (\Throwable $e) {}
        }
    }
    if ($wcActive !== '1' && !empty($wcUrl) && !empty($wcKey) && !empty($wcSecret)) {
        $wcActive = '1';
    }

    /* ---------- درگاه‌های پرداخت ---------- */
    $payDefault  = Setting::get('payment_default_gateway', 'zarinpal');
    $gateways = [
        'zarinpal'  => ['label'=>'زرین‌پال','kind'=>'واسط','color'=>'#ffc107','fields'=>['merchant_id'=>Setting::get('gw_zarinpal_merchant',''),'sandbox'=>Setting::get('gw_zarinpal_sandbox','0')],'active'=>Setting::get('gw_zarinpal_active','0')],
        'zibal'     => ['label'=>'زیبال','kind'=>'واسط','color'=>'#4e57ff','fields'=>['merchant_id'=>Setting::get('gw_zibal_merchant','')],'active'=>Setting::get('gw_zibal_active','0')],
        'idpay'     => ['label'=>'آیدی‌پی','kind'=>'واسط','color'=>'#00a99d','fields'=>['api_key'=>Setting::get('gw_idpay_key',''),'sandbox'=>Setting::get('gw_idpay_sandbox','0')],'active'=>Setting::get('gw_idpay_active','0')],
        'nextpay'   => ['label'=>'نکست‌پی','kind'=>'واسط','color'=>'#0093dd','fields'=>['api_key'=>Setting::get('gw_nextpay_key','')],'active'=>Setting::get('gw_nextpay_active','0')],
        'payping'   => ['label'=>'پی‌پینگ','kind'=>'واسط','color'=>'#00b6ff','fields'=>['token'=>Setting::get('gw_payping_token','')],'active'=>Setting::get('gw_payping_active','0')],
        'payir'     => ['label'=>'پی‌آی‌آر','kind'=>'واسط','color'=>'#ff2d55','fields'=>['api'=>Setting::get('gw_payir_api','')],'active'=>Setting::get('gw_payir_active','0')],
        'jibimo'    => ['label'=>'جیبی‌مو','kind'=>'واسط','color'=>'#8a3ffc','fields'=>['token'=>Setting::get('gw_jibimo_token','')],'active'=>Setting::get('gw_jibimo_active','0')],
        'vandar'    => ['label'=>'وندار','kind'=>'واسط','color'=>'#f39c12','fields'=>['api_key'=>Setting::get('gw_vandar_key','')],'active'=>Setting::get('gw_vandar_active','0')],
        'aqayepardakht'=>['label'=>'آقای پرداخت','kind'=>'واسط','color'=>'#00c853','fields'=>['pin'=>Setting::get('gw_aqayepardakht_pin','')],'active'=>Setting::get('gw_aqayepardakht_active','0')],
        'mellat'    => ['label'=>'بانک ملت','kind'=>'بانکی مستقیم','color'=>'#e53935','fields'=>['terminal_id'=>Setting::get('gw_mellat_terminal',''),'username'=>Setting::get('gw_mellat_username',''),'password'=>Setting::get('gw_mellat_password','')],'active'=>Setting::get('gw_mellat_active','0')],
        'parsian'   => ['label'=>'بانک پارسیان','kind'=>'بانکی مستقیم','color'=>'#c62828','fields'=>['pin'=>Setting::get('gw_parsian_pin','')],'active'=>Setting::get('gw_parsian_active','0')],
        'saman'     => ['label'=>'بانک سامان','kind'=>'بانکی مستقیم','color'=>'#1565c0','fields'=>['merchant_id'=>Setting::get('gw_saman_merchant',''),'password'=>Setting::get('gw_saman_password','')],'active'=>Setting::get('gw_saman_active','0')],
        'saderat'   => ['label'=>'بانک صادرات','kind'=>'بانکی مستقیم','color'=>'#00695c','fields'=>['terminal_id'=>Setting::get('gw_saderat_terminal','')],'active'=>Setting::get('gw_saderat_active','0')],
        'melli'     => ['label'=>'بانک ملی (سداد)','kind'=>'بانکی مستقیم','color'=>'#2e7d32','fields'=>['terminal_id'=>Setting::get('gw_melli_terminal',''),'merchant_id'=>Setting::get('gw_melli_merchant',''),'terminal_key'=>Setting::get('gw_melli_key','')],'active'=>Setting::get('gw_melli_active','0')],
        'pasargad'  => ['label'=>'بانک پاسارگاد','kind'=>'بانکی مستقیم','color'=>'#ffa000','fields'=>['terminal_id'=>Setting::get('gw_pasargad_terminal',''),'merchant_id'=>Setting::get('gw_pasargad_merchant',''),'private_key'=>Setting::get('gw_pasargad_private_key','')],'active'=>Setting::get('gw_pasargad_active','0')],
        'eghtesadnovin'=>['label'=>'بانک اقتصاد نوین','kind'=>'بانکی مستقیم','color'=>'#6a1b9a','fields'=>['username'=>Setting::get('gw_novin_username',''),'password'=>Setting::get('gw_novin_password','')],'active'=>Setting::get('gw_novin_active','0')],
        'asanpardakht'=>['label'=>'آسان پرداخت','kind'=>'بانکی مستقیم','color'=>'#3949ab','fields'=>['username'=>Setting::get('gw_asanpardakht_username',''),'password'=>Setting::get('gw_asanpardakht_password',''),'merchant_id'=>Setting::get('gw_asanpardakht_merchant','')],'active'=>Setting::get('gw_asanpardakht_active','0')],
        'iranpay'   => ['label'=>'ایران‌پی','kind'=>'بانکی مستقیم','color'=>'#00838f','fields'=>['api_key'=>Setting::get('gw_iranpay_key','')],'active'=>Setting::get('gw_iranpay_active','0')],
    ];

    /* ---------- امنیت ---------- */
    $twoFA        = Setting::get('security_two_fa',       '0');
    $twoFaSms     = Setting::get('two_fa_sms',            '1');
    $twoFaEmail   = Setting::get('two_fa_email',          '0');
    $twoFaTotp    = Setting::get('two_fa_totp',           '0');
    $twoFaAdminOnly = Setting::get('two_fa_admin_only',   '1');
    $twoFaCodeLen = Setting::get('two_fa_code_length',    '6');
    $twoFaExpire  = Setting::get('two_fa_expire_min',     '5');
    $sessionMin   = Setting::get('security_session_min',  '120');
    $logActivity  = Setting::get('security_log_activity', '1');
    $strongPass   = Setting::get('security_force_strong_password','1');
    $lockAfter    = Setting::get('security_lock_after_fail','1');
    $lockThreshold= Setting::get('security_lock_threshold','5');
    $lockMinutes  = Setting::get('security_lock_minutes', '15');
    $ipWhitelistOn= Setting::get('security_ip_whitelist_enabled','0');
    $ipWhitelist  = Setting::get('security_ip_whitelist','');

    /* ---------- بک‌آپ ---------- */
    $backupAuto        = Setting::get('backup_auto',           '0');
    $backupFreq        = Setting::get('backup_frequency',      'daily');
    $backupTime        = Setting::get('backup_time',           '03:00');
    $backupIncludeFile = Setting::get('backup_include_files',  '0');
    $backupRetain      = Setting::get('backup_retention_days', '30');
    $backupTargetLocal = Setting::get('backup_target_local',   '1');
    $backupTargetHost  = Setting::get('backup_target_hosting', '0');
    $backupTargetGoogle= Setting::get('backup_target_google',  '0');
    $backupTargetFtp   = Setting::get('backup_target_ftp',     '0');
    $backupHostPath    = Setting::get('backup_hosting_path',   '/backups');
    $backupGoogleClientId  = Setting::get('backup_google_client_id',    '');
    $backupGoogleClientSec = Setting::get('backup_google_client_secret','');
    $backupGoogleFolderId  = Setting::get('backup_google_folder_id',    '');
    $backupFtpHost     = Setting::get('backup_ftp_host',      '');
    $backupFtpPort     = Setting::get('backup_ftp_port',      '21');
    $backupFtpUser     = Setting::get('backup_ftp_username',  '');
    $backupFtpPass     = Setting::get('backup_ftp_password',  '');
    $backupFtpPath     = Setting::get('backup_ftp_path',      '/');

    /* ---------- سئو ---------- */
    $seoTitle       = Setting::get('seo_title',              'مشتری‌یار — سامانه مدیریت مشتریان');
    $seoTitleSep    = Setting::get('seo_title_separator',    '-');
    $seoDesc        = Setting::get('seo_description',        'سامانه هوشمند مدیریت ارتباط با مشتری، سفارش، پیش‌فاکتور و باشگاه مشتریان.');
    $seoKeywords    = Setting::get('seo_keywords',           'سی آر ام، مدیریت مشتری، پیش‌فاکتور، باشگاه مشتریان');
    $seoRobots      = Setting::get('seo_robots',             'index,follow');
    $seoCanonical   = Setting::get('seo_canonical_url',      '');
    $seoShareImage  = Setting::get('seo_share_image',        '');
    $seoAuthor      = Setting::get('seo_default_author',     '');
    $seoLang        = Setting::get('seo_lang',               'fa-IR');
    $seoAnalytics   = Setting::get('seo_analytics_id',       '');
    $seoGoogleVerif = Setting::get('seo_google_verification','');
    $seoBingVerif   = Setting::get('seo_bing_verification',  '');
    $seoYandexVerif = Setting::get('seo_yandex_verification','');
    $seoSitemapAuto = Setting::get('seo_sitemap_auto',       '1');
    $seoShareEnabled= Setting::get('seo_share_enabled',      '1');
    $seoSchemaOn    = Setting::get('seo_schema_enabled',     '1');
    $seoOrgType     = Setting::get('seo_org_type',           'business');
    $seoOrgLogo     = Setting::get('seo_org_logo',           '');

    $socialInstagram = Setting::get('social_instagram', '');
    $socialTelegram  = Setting::get('social_telegram',  '');
    $socialWhatsapp  = Setting::get('social_whatsapp',  '');
    $socialLinkedin  = Setting::get('social_linkedin',  '');
    $socialRubika    = Setting::get('social_rubika',    '');
    $socialBale      = Setting::get('social_bale',      '');
    $socialEitaa     = Setting::get('social_eitaa',     '');
    $socialAparat    = Setting::get('social_aparat',    '');

    /* ---------- طراحی سایت و برند شخصی ---------- */
    $siteBrandName    = Setting::get('site_brand_name',     $bizName ?: 'مشتری‌یار');
    $siteBrandTagline = Setting::get('site_brand_tagline',  'مدیریت هوشمند مشتریان');
    $siteLogoUrl      = Setting::get('site_logo_url',       '');
    $siteFaviconUrl   = Setting::get('site_favicon_url',    '');
    $siteDefaultTheme = Setting::get('site_default_theme',  'light');
    $sitePrimaryColor = Setting::get('site_primary_color',  '#0ea5e9');
    $siteIcon         = Setting::get('site_icon',           '✦');
    $siteOwnerName    = Setting::get('site_owner_name',     'هومن‌وب');
    $siteOwnerUrl     = Setting::get('site_owner_url',      'https://hoomanweb.ir');

    $clubBrandName    = Setting::get('club_brand_name',     $siteBrandName ?: 'باشگاه مشتریان');
    $clubLogoUrl      = Setting::get('club_logo_url',       $siteLogoUrl);
    $clubDefaultTheme = Setting::get('club_default_theme',  'light');
    $clubPrimaryColor = Setting::get('club_primary_color',  $sitePrimaryColor);

    /* ---------- کاربران سیستم ---------- */
    $usersList = collect();
    try {
        if (Schema::hasTable('users')) {
            $usersList = DB::table('users')->orderByDesc('id')->limit(200)->get();
        }
    } catch (\Throwable $e) { $usersList = collect(); }

    /* ---------- محاسبه امتیازها ---------- */
    $profileFields = [$bizName, $bizPhone, $bizAddress, $bizEmail, $bizProvince, $bizCity, $bizPostal];
    $profileDone   = count(array_filter($profileFields, fn($v) => !empty($v)));
    $profileScore  = (int) round(($profileDone / count($profileFields)) * 100);

    $activeGateways = 0;
    foreach ($gateways as $g) { if ($g['active'] == '1') $activeGateways++; }

    $smsHasCreds = false;
    foreach ($smsCred as $c) {
        foreach ($c as $val) { if (!empty($val)) { $smsHasCreds = true; break 2; } }
    }

    $connections = [
        'ووکامرس'  => $wcActive == '1',
        'مودیان'   => $moadianOn == '1',
        'پیامک'    => $smsEnabled == '1' && $smsHasCreds,
        'ایمیل'    => $mailEnabled == '1' && !empty($mailHost) && !empty($mailFrom),
        'تلگرام'   => $tgEnabled == '1' && !empty($tgToken),
        'بله'      => $baleEnabled == '1' && !empty($baleToken),
        'روبیکا'   => $rubikaEnabled == '1' && !empty($rubikaToken),
        'ایتا'     => $eitaaEnabled == '1' && !empty($eitaaToken),
        'واتساپ'   => $waEnabled == '1' && !empty($waToken),
        'درگاه'    => $activeGateways > 0,
    ];
    $connectionsActive = count(array_filter($connections));
    $connectionsTotal  = count($connections);

    $securityScore = 0;
    if ($twoFA == '1')          $securityScore += 25;
    if ($logActivity == '1')    $securityScore += 15;
    if ($backupAuto == '1')     $securityScore += 20;
    if ($strongPass == '1')     $securityScore += 15;
    if ($lockAfter == '1')      $securityScore += 15;
    if ((int)$sessionMin <= 240) $securityScore += 10;
    $securityScore = min(100, $securityScore);

    $active = request('tab', 'business');
@endphp

<div class="settings-page" id="settingsPage">

    {{-- پیام موفقیت --}}
    @if(session('success'))
        <div class="settings-flash is-ok">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="settings-flash is-bad">⚠ {{ session('error') }}</div>
    @endif

    {{-- ==================== هدر ==================== --}}
    <section class="settings-hero">
        <div>
            <span class="settings-eyebrow">مرکز تنظیمات سیستم</span>
            <h2>پیکربندی کامل کسب‌وکار در یک صفحه</h2>
            <p>پروفایل، کاربران، اتصال‌ها، درگاه‌های پرداخت، پیامک و پیام‌رسان‌ها، ظاهر و برند، امنیت و بک‌آپ، سئو حرفه‌ای — همه در یک مرکز یکپارچه.</p>
            <div class="settings-actions">
                <button class="btn" type="button" onclick="saveActivePanel()">💾 ذخیره تغییرات فعال</button>
                <a class="btn btn-ghost" href="{{ url('/app') }}">← بازگشت به پیشخوان</a>
            </div>
        </div>
        <div class="settings-score">
            <div class="settings-score-item" style="--metric-color:#10b981;">
                <span>تکمیل پروفایل</span>
                <b>{{ to_fa_num($profileScore) }}٪</b>
                <small>{{ to_fa_num($profileDone) }} از {{ to_fa_num(count($profileFields)) }} فیلد پر شده</small>
            </div>
            <div class="settings-score-item" style="--metric-color:#3b82f6;">
                <span>اتصال‌های فعال</span>
                <b>{{ to_fa_num($connectionsActive) }} از {{ to_fa_num($connectionsTotal) }}</b>
                <small>ووکامرس، مودیان، پیامک، ایمیل، ۴ پیام‌رسان، درگاه</small>
            </div>
            <div class="settings-score-item" style="--metric-color:#8b5cf6;">
                <span>امتیاز امنیت</span>
                <b>{{ to_fa_num($securityScore) }}٪</b>
                <small>۲FA، لاگ، بک‌آپ، پسورد قوی، قفل</small>
            </div>
            <div class="settings-score-item" style="--metric-color:#f59e0b;">
                <span>نرخ مالیات فعال</span>
                <b>{{ to_fa_num($defaultVat) }}٪</b>
                <small>پیش‌فرض روی همه اسناد</small>
            </div>
        </div>
    </section>

    {{-- ==================== منو + محتوا ==================== --}}
    <div class="settings-layout">
        <nav class="settings-tabs" id="settingsTabs">
            <button type="button" class="settings-tab-btn" data-tab="business"      style="--tab-color:#10b981;"><span class="icon">🏢</span><span class="label">پروفایل کسب‌وکار</span></button>
            <button type="button" class="settings-tab-btn" data-tab="users"         style="--tab-color:#3b82f6;"><span class="icon">👤</span><span class="label">کاربران و نقش‌ها</span></button>
            <button type="button" class="settings-tab-btn" data-tab="connections"   style="--tab-color:#0ea5e9;"><span class="icon">🔌</span><span class="label">اتصال‌ها</span></button>
            <button type="button" class="settings-tab-btn" data-tab="finance"       style="--tab-color:#f59e0b;"><span class="icon">💳</span><span class="label">مالی و مالیات</span></button>
            <button type="button" class="settings-tab-btn" data-tab="notifications" style="--tab-color:#8b5cf6;"><span class="icon">📧</span><span class="label">اعلان‌ها</span></button>
            <button type="button" class="settings-tab-btn" data-tab="gateways"      style="--tab-color:#22c55e;"><span class="icon">💰</span><span class="label">درگاه پرداخت</span></button>
            <button type="button" class="settings-tab-btn" data-tab="branding"      style="--tab-color:#ec4899;"><span class="icon">🎨</span><span class="label">ظاهر و برند</span></button>
            <button type="button" class="settings-tab-btn" data-tab="design"        style="--tab-color:#f59e0b;"><span class="icon">🌟</span><span class="label">طراحی سایت</span></button>
            <button type="button" class="settings-tab-btn" data-tab="security"      style="--tab-color:#ef4444;"><span class="icon">🔒</span><span class="label">امنیت و بک‌آپ</span></button>
            <button type="button" class="settings-tab-btn" data-tab="seo"           style="--tab-color:#14b8a6;"><span class="icon">🌐</span><span class="label">سئو و متا</span></button>
        </nav>

        <div class="settings-content">

            {{-- ========== تب ۱: پروفایل ========== --}}
            <section class="settings-panel" data-panel="business">
                <header class="settings-panel-header">
                    <div><span class="chip">اطلاعات پایه</span><h3>پروفایل کسب‌وکار</h3>
                        <p>این اطلاعات در فاکتور رسمی، پیش‌فاکتور، ایمیل خودکار و صفحه‌های سایت نمایش داده می‌شوند.</p></div>
                </header>
                <form method="post" action="{{ url('/app/settings') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="business">
                    <div class="settings-block is-accent" style="--block-color:#10b981;">
                        <div class="settings-block-header"><h4>هویت کسب‌وکار</h4><small>نام رسمی، برند تجاری و توضیح مختصر</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field"><label>نام رسمی کسب‌وکار</label><input name="business_name" value="{{ $bizName }}" placeholder="مثلاً شرکت مهر ایرانیان"></div>
                            <div class="settings-field"><label>برند تجاری</label><input name="business_brand" value="{{ $bizBrand }}" placeholder="نام کوتاه یا برند نمایشی"></div>
                            <div class="settings-field"><label>شعبه فعال</label><input name="business_branch" value="{{ $bizBranch }}" placeholder="دفتر مرکزی"></div>
                            <div class="settings-field col-span-3"><label>معرفی کوتاه</label><textarea name="business_about" placeholder="یک پاراگراف کوتاه برای معرفی کسب‌وکار (در ایمیل و فاکتور استفاده می‌شود)">{{ $bizAbout }}</textarea></div>
                        </div>
                    </div>
                    <div class="settings-block is-accent" style="--block-color:#0ea5e9;">
                        <div class="settings-block-header"><h4>راه‌های ارتباط</h4><small>شماره‌ها، ایمیل و آدرس وب</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field ltr"><label>تلفن ثابت</label><input name="business_phone" value="{{ $bizPhone }}" placeholder="021-XXXXXXXX"></div>
                            <div class="settings-field ltr"><label>موبایل پشتیبان</label><input name="business_mobile" value="{{ $bizMobile }}" placeholder="09XX-XXXXXXX"></div>
                            <div class="settings-field ltr"><label>فکس</label><input name="business_fax" value="{{ $bizFax }}" placeholder="اختیاری"></div>
                            <div class="settings-field ltr"><label>ایمیل کسب‌وکار</label><input name="business_email" type="email" value="{{ $bizEmail }}" placeholder="info@example.com"></div>
                            <div class="settings-field ltr col-span-2"><label>وب‌سایت</label><input name="business_website" value="{{ $bizWebsite }}" placeholder="ayarpro.ir"></div>
                        </div>
                    </div>
                    <div class="settings-block is-accent" style="--block-color:#8b5cf6;">
                        <div class="settings-block-header"><h4>نشانی و موقعیت</h4><small>اطلاعات مکانی برای فاکتور و ارسال</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field"><label>استان</label><input name="business_province" value="{{ $bizProvince }}" placeholder="مثلاً تهران"></div>
                            <div class="settings-field"><label>شهر</label><input name="business_city" value="{{ $bizCity }}" placeholder="مثلاً تهران"></div>
                            <div class="settings-field ltr"><label>کد پستی</label><input name="business_postal_code" value="{{ $bizPostal }}" placeholder="۱۰ رقم"></div>
                            <div class="settings-field col-span-3"><label>نشانی کامل</label><textarea name="business_address" placeholder="خیابان، کوچه، پلاک، طبقه، واحد">{{ $bizAddress }}</textarea></div>
                        </div>
                    </div>
                    <div class="settings-form-footer">
                        <button type="button" class="btn btn-ghost" onclick="location.reload()">بازخوانی</button>
                        <button type="submit" class="btn">💾 ذخیره تغییرات</button>
                    </div>
                </form>
            </section>

            {{-- ========== تب ۲: کاربران ========== --}}
            <section class="settings-panel" data-panel="users">
                <header class="settings-panel-header">
                    <div><span class="chip">تیم و دسترسی</span><h3>کاربران و نقش‌ها</h3>
                        <p>مدیریت کاربران سیستم، تعریف نقش‌ها و کنترل دسترسی‌ها.</p></div>
                    <button type="button" class="btn" onclick="openUserModal()">+ افزودن کاربر</button>
                </header>
                <div class="settings-table-wrap">
                    <table class="settings-table">
                        <thead><tr><th>#</th><th>کاربر</th><th>ایمیل / موبایل</th><th>نقش</th><th>وضعیت</th><th>ثبت‌نام</th><th style="text-align:left;">عملیات</th></tr></thead>
                        <tbody>
                            @forelse($usersList as $u)
                                @php
                                    $uName   = $u->name ?? ($u->full_name ?? ($u->username ?? 'کاربر'));
                                    $uEmail  = $u->email ?? '';
                                    $uPhone  = $u->phone ?? ($u->mobile ?? '');
                                    $uRole   = $u->role ?? ($u->user_type ?? 'user');
                                    $roleMap = ['admin'=>'مدیر سیستم','manager'=>'مدیر فروش','support'=>'پشتیبان','reporter'=>'گزارش‌گیر','user'=>'کارمند'];
                                    $uRoleLabel = $roleMap[$uRole] ?? 'کارمند';
                                    $uActive = property_exists($u,'active') ? $u->active : (property_exists($u,'is_active') ? $u->is_active : 1);
                                    $uCreated = $u->created_at ?? null;
                                @endphp
                                <tr>
                                    <td>{{ to_fa_num($u->id) }}</td>
                                    <td><span class="avatar">{{ mb_substr($uName,0,1) }}</span><b>{{ $uName }}</b></td>
                                    <td style="direction:ltr; text-align:right;">{{ $uEmail ?: ($uPhone ?: '—') }}</td>
                                    <td><span class="settings-badge {{ $uRole==='admin'?'is-ok':'' }}">{{ $uRoleLabel }}</span></td>
                                    <td><span class="settings-badge {{ $uActive?'is-ok':'is-muted' }}">{{ $uActive?'فعال':'غیرفعال' }}</span></td>
                                    <td>{{ safe_jdate($uCreated) }}</td>
                                    <td style="text-align:left;">
                                        <button type="button" class="settings-badge" onclick="openUserModal({{ (int)$u->id }}, {{ json_encode(['name'=>$uName,'email'=>$uEmail,'phone'=>$uPhone,'role'=>$uRole,'active'=>$uActive]) }})">ویرایش</button>
                                        <form method="post" action="{{ url('/app/settings/users/'.$u->id) }}" style="display:inline;" onsubmit="return confirm('کاربر «{{ $uName }}» حذف شود؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="settings-badge is-bad">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="settings-empty"><div style="font-size:2rem;">👤</div><h3>هنوز کاربری اضافه نشده است</h3><p>برای افزودن اولین کاربر از دکمه بالا استفاده کنید.</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="settings-block" style="margin-top:1rem;">
                    <div class="settings-block-header"><h4>راهنمای نقش‌ها</h4></div>
                    <div style="display:grid; gap:0.6rem; color:var(--txt); font-size:0.85rem; line-height:2;">
                        <div>🛡 <b>مدیر سیستم:</b> دسترسی کامل به همه بخش‌ها و تنظیمات</div>
                        <div>👔 <b>مدیر فروش:</b> دسترسی به سفارش‌ها، فاکتور، مشتریان</div>
                        <div>🎧 <b>پشتیبان:</b> فقط تیکت‌ها، مشتریان، پایگاه دانش</div>
                        <div>📊 <b>گزارش‌گیر:</b> فقط مشاهده گزارش‌ها و آمار</div>
                    </div>
                </div>
            </section>

            {{-- ========== تب ۳: اتصال‌ها ========== --}}
            <section class="settings-panel" data-panel="connections">
                <header class="settings-panel-header">
                    <div><span class="chip">سرویس‌های بیرونی</span><h3>اتصال به سرویس‌های خارجی</h3>
                        <p>وضعیت خلاصه اتصال به سرویس‌های مختلف. برای پیکربندی هر کدام روی «پیکربندی» بزنید.</p></div>
                </header>
                <div class="integration-grid">
                    <div class="integration-card">
                        <header><div class="logo" style="--int-color:#7f54b3;">🛒</div><div class="info"><h4>فروشگاه ووکامرس</h4><small>همگام‌سازی محصولات، سفارش و مشتری</small></div></header>
                        <p>اتصال به فروشگاه وردپرس شما برای دریافت خودکار سفارش‌ها.</p>
                        <div class="actions">
                            <span class="settings-badge {{ $wcActive=='1'?'is-ok':'is-muted' }}">{{ $wcActive=='1'?'فعال':'غیرفعال' }}</span>
                            <a class="btn" href="{{ url('/app/woocommerce') }}">پیکربندی کامل</a>
                            <a class="btn btn-ghost" href="{{ url('/app/sync-logs') }}">لاگ همگام‌سازی</a>
                        </div>
                    </div>
                    <div class="integration-card">
                        <header><div class="logo" style="--int-color:#0ea5e9;">🧾</div><div class="info"><h4>سامانه مودیان</h4><small>ارسال رسمی فاکتور به سازمان امور مالیاتی</small></div></header>
                        <p>ارسال فاکتور رسمی به سامانه مودیان کشور.</p>
                        <div class="actions">
                            <span class="settings-badge {{ $moadianOn=='1'?'is-ok':'is-muted' }}">{{ $moadianOn=='1'?'فعال':'غیرفعال' }}</span>
                            <button class="btn" type="button" onclick="switchTab('finance')">پیکربندی</button>
                        </div>
                    </div>
                    <div class="integration-card">
                        <header><div class="logo" style="--int-color:#10b981;">📱</div><div class="info"><h4>پیامک، ایمیل و پیام‌رسان‌ها</h4><small>۴ پیام‌رسان ایرانی + واتساپ + پیامک + ایمیل</small></div></header>
                        <p>پیکربندی کامل کانال‌های ارتباط با مشتری.</p>
                        <div class="actions">
                            <span class="settings-badge {{ $connections['پیامک']?'is-ok':'is-muted' }}">پیامک</span>
                            <span class="settings-badge {{ $connections['ایمیل']?'is-ok':'is-muted' }}">ایمیل</span>
                            <span class="settings-badge {{ $connections['بله']?'is-ok':'is-muted' }}">بله</span>
                            <span class="settings-badge {{ $connections['روبیکا']?'is-ok':'is-muted' }}">روبیکا</span>
                            <span class="settings-badge {{ $connections['ایتا']?'is-ok':'is-muted' }}">ایتا</span>
                            <span class="settings-badge {{ $connections['واتساپ']?'is-ok':'is-muted' }}">واتساپ</span>
                            <button class="btn" type="button" onclick="switchTab('notifications')">پیکربندی</button>
                        </div>
                    </div>
                    <div class="integration-card">
                        <header><div class="logo" style="--int-color:#f59e0b;">💰</div><div class="info"><h4>درگاه‌های پرداخت</h4><small>{{ to_fa_num(count($gateways)) }} درگاه پشتیبانی‌شده</small></div></header>
                        <p>{{ to_fa_num($activeGateways) }} درگاه فعال از {{ to_fa_num(count($gateways)) }} درگاه.</p>
                        <div class="actions">
                            <span class="settings-badge {{ $activeGateways>0?'is-ok':'is-muted' }}">{{ $activeGateways>0?'فعال':'غیرفعال' }}</span>
                            <button class="btn" type="button" onclick="switchTab('gateways')">پیکربندی</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ========== تب ۴: مالی و مالیات ========== --}}
            <section class="settings-panel" data-panel="finance">
                <header class="settings-panel-header">
                    <div><span class="chip">مالی</span><h3>تنظیمات مالی و مالیاتی</h3>
                        <p>کد اقتصادی، شناسه ملی، شماره ثبت، نرخ مالیات پیش‌فرض و اتصال به سامانه مودیان.</p></div>
                </header>
                <form method="post" action="{{ url('/app/settings') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="finance">
                    <div class="settings-block is-accent" style="--block-color:#f59e0b;">
                        <div class="settings-block-header"><h4>اطلاعات مالیاتی رسمی</h4><small>در فاکتور رسمی و پیش‌فاکتور نمایش داده می‌شود</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field ltr"><label>کد اقتصادی</label><input name="business_economic_code" value="{{ $bizEconomic }}" placeholder="۱۲ رقم"></div>
                            <div class="settings-field ltr"><label>شناسه ملی</label><input name="business_national_id" value="{{ $bizNational }}" placeholder="۱۱ رقم"></div>
                            <div class="settings-field ltr"><label>شماره ثبت</label><input name="business_registration_no" value="{{ $bizRegNo }}" placeholder="شماره ثبت شرکت"></div>
                            <div class="settings-field ltr"><label>شماره ثبت مالیاتی</label><input name="business_vat_no" value="{{ $bizVatNo }}" placeholder="اختیاری"></div>
                            <div class="settings-field"><label>نرخ مالیات پیش‌فرض (٪)</label><input class="ltr" name="default_vat_percent" type="number" min="0" max="100" step="0.1" value="{{ $defaultVat }}"><small>روی همه اسناد جدید اعمال می‌شود.</small></div>
                            <div class="settings-field"><label>واحد پول پیش‌فرض</label>
                                <select name="currency">
                                    <option value="toman" @selected($currency==='toman')>تومان</option>
                                    <option value="rial"  @selected($currency==='rial')>ریال</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="settings-block is-accent" style="--block-color:#0ea5e9;">
                        <div class="settings-block-header"><h4>اتصال به سامانه مودیان</h4><small>ارسال رسمی فاکتور به سازمان امور مالیاتی</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field ltr col-span-2"><label>کلید API مودیان</label><input name="moadian_api_key" value="{{ $moadianKey }}" placeholder="کلید API"></div>
                            <div class="settings-field"><label>وضعیت اتصال</label>
                                <label class="settings-check"><input type="checkbox" name="moadian_active" value="1" @checked($moadianOn=='1')><span>فعال‌سازی ارسال خودکار</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="settings-block is-accent" style="--block-color:#8b5cf6;">
                        <div class="settings-block-header"><h4>شماره‌گذاری پیش‌فاکتور</h4><small>الگوی سریال خودکار</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field"><label>پیشوند</label><input name="proforma_serial_prefix" value="{{ $proPrefix }}" placeholder="مثلاً پف"></div>
                            <div class="settings-field"><label>جداکننده</label><input name="proforma_serial_separator" value="{{ $proSep }}" placeholder="مثلاً -"></div>
                            <div class="settings-field ltr"><label>تعداد رقم</label><input name="proforma_serial_padding" type="number" min="1" max="10" value="{{ (int) $proPad }}"></div>
                            <div class="settings-field"><label>نوع تاریخ در شماره</label>
                                <select name="proforma_serial_date">
                                    <option value="none"      @selected($proDate==='none')>بدون تاریخ</option>
                                    <option value="jalali"    @selected($proDate==='jalali')>شمسی</option>
                                    <option value="gregorian" @selected($proDate==='gregorian')>میلادی</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="settings-form-footer"><button type="submit" class="btn">💾 ذخیره تغییرات مالی</button></div>
                </form>
            </section>
            
            
            {{-- ========== تب ۵: اعلان‌ها ========== --}}
            <section class="settings-panel" data-panel="notifications">
                <header class="settings-panel-header">
                    <div><span class="chip">پیام‌ها</span><h3>پیامک، ایمیل و پیام‌رسان‌ها</h3>
                        <p>پیکربندی سرویس‌های اعلان و انتخاب دقیق رویدادها.</p></div>
                </header>
                <form method="post" action="{{ url('/app/settings') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="notifications">

                    {{-- پیامک --}}
                    <div class="settings-block is-accent" style="--block-color:#10b981;">
                        <div class="settings-block-header"><h4>سرویس پیامک</h4><small>با انتخاب هر سرویس، فیلدهای اختصاصی نمایش داده می‌شود</small></div>
                        <label class="settings-check" style="margin-bottom:0.5rem;">
                            <input type="checkbox" name="sms_enabled" value="1" @checked($smsEnabled=='1')>
                            <span>فعال‌سازی سرویس پیامک</span>
                        </label>
                        <div class="settings-form-grid">
                            <div class="settings-field">
                                <label>ارائه‌دهنده پیامک</label>
                                <select name="sms_provider" id="smsProviderSelect" onchange="switchSmsProvider(this.value)">
                                    <option value="kavenegar"    @selected($smsProv==='kavenegar')>کاوه‌نگار</option>
                                    <option value="smsir"        @selected($smsProv==='smsir')>SMS.ir</option>
                                    <option value="melipayamak"  @selected($smsProv==='melipayamak')>ملی‌پیامک</option>
                                    <option value="farapayamak"  @selected($smsProv==='farapayamak')>فراپیامک</option>
                                    <option value="ippanel"      @selected($smsProv==='ippanel')>آی‌پی پنل</option>
                                    <option value="mediana"      @selected($smsProv==='mediana')>مدیانا</option>
                                    <option value="farazsms"     @selected($smsProv==='farazsms')>فراز اس‌ام‌اس</option>
                                    <option value="payamak_yas"  @selected($smsProv==='payamak_yas')>پیامک یاس</option>
                                    <option value="asanak"       @selected($smsProv==='asanak')>آسانک</option>
                                    <option value="sabanovin"    @selected($smsProv==='sabanovin')>صبانوین</option>
                                </select>
                            </div>
                            <div class="settings-field ltr col-span-2"><label>شماره فرستنده پیش‌فرض</label><input name="sms_sender" value="{{ $smsSender }}" placeholder="مثلاً 10004346 یا 3000"></div>
                        </div>

                        <div class="sms-cred" data-prov="kavenegar" style="margin-top:0.75rem;">
                            <div class="settings-form-grid"><div class="settings-field ltr col-span-3"><label>API Key کاوه‌نگار</label><input name="sms_kavenegar_api_key" value="{{ $smsCred['kavenegar']['api_key'] }}" placeholder="کلید API"><small>panel.kavenegar.com → تنظیمات → API</small></div></div>
                        </div>
                        <div class="sms-cred" data-prov="smsir" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid">
                                <div class="settings-field ltr col-span-2"><label>API Key سامانه SMS.ir</label><input name="sms_smsir_api_key" value="{{ $smsCred['smsir']['api_key'] }}" placeholder="کلید وب‌سرویس V3"></div>
                                <div class="settings-field ltr"><label>شماره خط اختصاصی</label><input name="sms_smsir_line_number" value="{{ $smsCred['smsir']['line_number'] }}" placeholder="مثلاً 30007732"></div>
                            </div>
                        </div>
                        <div class="sms-cred" data-prov="melipayamak" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid">
                                <div class="settings-field ltr"><label>نام کاربری ملی‌پیامک</label><input name="sms_melipayamak_username" value="{{ $smsCred['melipayamak']['username'] }}"></div>
                                <div class="settings-field ltr"><label>رمز عبور</label><input name="sms_melipayamak_password" value="{{ $smsCred['melipayamak']['password'] }}"></div>
                            </div>
                        </div>
                        <div class="sms-cred" data-prov="farapayamak" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid">
                                <div class="settings-field ltr"><label>نام کاربری فراپیامک</label><input name="sms_farapayamak_username" value="{{ $smsCred['farapayamak']['username'] }}"></div>
                                <div class="settings-field ltr"><label>رمز عبور</label><input name="sms_farapayamak_password" value="{{ $smsCred['farapayamak']['password'] }}"></div>
                            </div>
                        </div>
                        <div class="sms-cred" data-prov="ippanel" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid">
                                <div class="settings-field ltr"><label>نام کاربری آی‌پی پنل</label><input name="sms_ippanel_username" value="{{ $smsCred['ippanel']['username'] }}"></div>
                                <div class="settings-field ltr"><label>رمز عبور</label><input name="sms_ippanel_password" value="{{ $smsCred['ippanel']['password'] }}"></div>
                                <div class="settings-field ltr"><label>کد پترن پیش‌فرض</label><input name="sms_ippanel_pattern" value="{{ $smsCred['ippanel']['pattern_code'] }}" placeholder="اختیاری"></div>
                            </div>
                        </div>
                        <div class="sms-cred" data-prov="mediana" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid"><div class="settings-field ltr col-span-3"><label>API Key مدیانا</label><input name="sms_mediana_api_key" value="{{ $smsCred['mediana']['api_key'] }}"></div></div>
                        </div>
                        <div class="sms-cred" data-prov="farazsms" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid"><div class="settings-field ltr col-span-3"><label>API Key فراز اس‌ام‌اس</label><input name="sms_farazsms_api_key" value="{{ $smsCred['farazsms']['api_key'] }}"></div></div>
                        </div>
                        <div class="sms-cred" data-prov="payamak_yas" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid">
                                <div class="settings-field ltr"><label>نام کاربری پیامک یاس</label><input name="sms_payamakyas_username" value="{{ $smsCred['payamak_yas']['username'] }}"></div>
                                <div class="settings-field ltr"><label>رمز عبور</label><input name="sms_payamakyas_password" value="{{ $smsCred['payamak_yas']['password'] }}"></div>
                            </div>
                        </div>
                        <div class="sms-cred" data-prov="asanak" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid">
                                <div class="settings-field ltr"><label>نام کاربری آسانک</label><input name="sms_asanak_username" value="{{ $smsCred['asanak']['username'] }}"></div>
                                <div class="settings-field ltr"><label>رمز عبور</label><input name="sms_asanak_password" value="{{ $smsCred['asanak']['password'] }}"></div>
                            </div>
                        </div>
                        <div class="sms-cred" data-prov="sabanovin" style="margin-top:0.75rem; display:none;">
                            <div class="settings-form-grid">
                                <div class="settings-field ltr"><label>نام کاربری صبانوین</label><input name="sms_sabanovin_username" value="{{ $smsCred['sabanovin']['username'] }}"></div>
                                <div class="settings-field ltr"><label>رمز عبور</label><input name="sms_sabanovin_password" value="{{ $smsCred['sabanovin']['password'] }}"></div>
                            </div>
                        </div>
                    </div>

                    {{-- قالب‌های OTP برای sms.ir و کاوه‌نگار و ملی‌پیامک --}}
                    <div class="settings-block is-accent" style="--block-color:#f59e0b; margin-top:1rem;">
                        <div class="settings-block-header"><h4>🔐 قالب‌های پیامکی OTP - کد ورود و بازیابی رمز</h4></div>
                        <div class="settings-form-grid" style="grid-template-columns: repeat(4, 1fr);">
                            <div class="settings-field ltr">
                                <label>🟢 SMS.ir - شناسه قالب Verify</label>
                                <input name="smsir_verify_template_id" value="{{ $smsirVerifyTemplate }}" placeholder="مثلاً 100000" style="direction:ltr;">
                            </div>
                            <div class="settings-field ltr">
                                <label>🔵 کاوه‌نگار - نام الگوی OTP</label>
                                <input name="kavenegar_otp_template" value="{{ $kavenegarOtpTemplate }}" placeholder="مثلاً verify-otp">
                            </div>
                            <div class="settings-field ltr">
                                <label>🟠 ملی‌پیامک - شناسه BodyId</label>
                                <input name="melipayamak_otp_body_id" value="{{ $melipayamakBodyId }}" placeholder="مثلاً 123456">
                            </div>
                            <div class="settings-field ltr">
                                <label>🔘 شناسه قالب عمومی</label>
                                <input name="otp_template_id" value="{{ $genericOtpTemplate }}" placeholder="Fallback برای همه پنل‌ها">
                            </div>
                        </div>
                    </div>

                    {{-- ایمیل --}}
                    <div class="settings-block is-accent" style="--block-color:#3b82f6;">
                        <div class="settings-block-header"><h4>سرور ایمیل SMTP</h4><small>پیکربندی ارسال ایمیل تراکنشی</small></div>
                        <label class="settings-check" style="margin-bottom:0.5rem;">
                            <input type="checkbox" name="email_enabled" value="1" @checked($mailEnabled=='1')>
                            <span>فعال‌سازی سرویس ایمیل</span>
                        </label>
                        <div class="settings-form-grid">
                            <div class="settings-field ltr"><label>هاست SMTP</label><input name="mail_host" value="{{ $mailHost }}" placeholder="smtp.example.com"></div>
                            <div class="settings-field ltr"><label>پورت</label><input name="mail_port" type="number" value="{{ $mailPort }}" placeholder="587 یا 465"></div>
                            <div class="settings-field"><label>نوع رمزنگاری</label>
                                <select name="mail_encryption">
                                    <option value="tls"  @selected($mailEnc==='tls')>TLS</option>
                                    <option value="ssl"  @selected($mailEnc==='ssl')>SSL</option>
                                    <option value="none" @selected($mailEnc==='none')>بدون رمزنگاری</option>
                                </select>
                            </div>
                            <div class="settings-field ltr"><label>نام کاربری</label><input name="mail_username" value="{{ $mailUser }}"></div>
                            <div class="settings-field ltr"><label>رمز عبور</label><input name="mail_password" type="password" value="{{ $mailPass }}" placeholder="اگر تغییر نمی‌دهید خالی بگذارید"></div>
                            <div class="settings-field ltr"><label>ایمیل فرستنده</label><input name="mail_from_address" type="email" value="{{ $mailFrom }}"></div>
                            <div class="settings-field"><label>نام فرستنده</label><input name="mail_from_name" value="{{ $mailName }}"></div>
                        </div>
                    </div>

                    {{-- پیام‌رسان‌ها --}}
                    <div class="settings-block is-accent" style="--block-color:#0ea5e9;">
                        <div class="settings-block-header"><h4>پیام‌رسان‌ها (ربات)</h4><small>هر کدام را جداگانه فعال و پیکربندی کنید</small></div>

                        <div class="messenger-block">
                            <label class="settings-check messenger-toggle">
                                <input type="checkbox" name="telegram_enabled" value="1" @checked($tgEnabled=='1')>
                                <span>📨 تلگرام</span>
                            </label>
                            <div class="settings-form-grid" style="margin-top:0.5rem;">
                                <div class="settings-field ltr col-span-2"><label>توکن ربات</label><input name="telegram_bot_token" value="{{ $tgToken }}" placeholder="123456:ABC-DEF..."></div>
                                <div class="settings-field ltr"><label>شناسه چت / کانال</label><input name="telegram_chat_id" value="{{ $tgChatId }}" placeholder="-100XXXXXXX"></div>
                            </div>
                        </div>

                        <div class="messenger-block">
                            <label class="settings-check messenger-toggle">
                                <input type="checkbox" name="bale_enabled" value="1" @checked($baleEnabled=='1')>
                                <span>💬 بله (Bale)</span>
                            </label>
                            <div class="settings-form-grid" style="margin-top:0.5rem;">
                                <div class="settings-field ltr col-span-2"><label>توکن ربات بله</label><input name="bale_bot_token" value="{{ $baleToken }}" placeholder="توکن دریافتی از BotFather بله"></div>
                                <div class="settings-field ltr"><label>شناسه چت / کانال</label><input name="bale_chat_id" value="{{ $baleChatId }}" placeholder="مثلاً @channel یا -100..."></div>
                            </div>
                        </div>

                        <div class="messenger-block">
                            <label class="settings-check messenger-toggle">
                                <input type="checkbox" name="rubika_enabled" value="1" @checked($rubikaEnabled=='1')>
                                <span>🤖 روبیکا (Rubika)</span>
                            </label>
                            <div class="settings-form-grid" style="margin-top:0.5rem;">
                                <div class="settings-field ltr col-span-2"><label>توکن ربات روبیکا</label><input name="rubika_bot_token" value="{{ $rubikaToken }}" placeholder="توکن ربات از پنل روبیکا"></div>
                                <div class="settings-field ltr"><label>شناسه چت / کانال</label><input name="rubika_chat_id" value="{{ $rubikaChatId }}"></div>
                            </div>
                        </div>

                        <div class="messenger-block">
                            <label class="settings-check messenger-toggle">
                                <input type="checkbox" name="eitaa_enabled" value="1" @checked($eitaaEnabled=='1')>
                                <span>🌐 ایتا (Eitaa)</span>
                            </label>
                            <div class="settings-form-grid" style="margin-top:0.5rem;">
                                <div class="settings-field ltr col-span-2"><label>توکن ربات ایتا</label><input name="eitaa_bot_token" value="{{ $eitaaToken }}" placeholder="توکن از eitaayar.ir"></div>
                                <div class="settings-field ltr"><label>شناسه چت / کانال</label><input name="eitaa_chat_id" value="{{ $eitaaChatId }}"></div>
                            </div>
                        </div>

                        <div class="messenger-block">
                            <label class="settings-check messenger-toggle">
                                <input type="checkbox" name="whatsapp_enabled" value="1" @checked($waEnabled=='1')>
                                <span>💚 واتساپ (WhatsApp)</span>
                            </label>
                            <div class="settings-form-grid" style="margin-top:0.5rem;">
                                <div class="settings-field"><label>ارائه‌دهنده</label>
                                    <select name="whatsapp_provider">
                                        <option value="wp_api"    @selected($waProvider==='wp_api')>WhatsApp Business API</option>
                                        <option value="ultramsg"  @selected($waProvider==='ultramsg')>UltraMsg</option>
                                        <option value="360dialog" @selected($waProvider==='360dialog')>360dialog</option>
                                        <option value="chatapi"   @selected($waProvider==='chatapi')>ChatAPI</option>
                                    </select>
                                </div>
                                <div class="settings-field ltr"><label>توکن / API Key</label><input name="whatsapp_token" value="{{ $waToken }}"></div>
                                <div class="settings-field ltr"><label>Instance ID (اختیاری)</label><input name="whatsapp_instance" value="{{ $waInstance }}"></div>
                                <div class="settings-field ltr col-span-3"><label>شماره واتساپ فرستنده</label><input name="whatsapp_sender" value="{{ $waSender }}" placeholder="مثلاً 989123456789"></div>
                            </div>
                        </div>
                    </div>

                    {{-- رویدادهای اعلان --}}
                    <div class="settings-block is-accent" style="--block-color:#8b5cf6;">
                        <div class="settings-block-header"><h4>رویدادهای اعلان</h4><small>در چه رویدادهایی و به چه کسی اعلان ارسال شود</small></div>
                        <div class="notif-events">
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_order" value="1" @checked($notifOrder=='1')><span>🛒 ثبت سفارش جدید (به مدیر)</span></label>
                                <label class="settings-check"><input type="checkbox" name="notif_order_customer" value="1" @checked($notifOrderCustomer=='1')><span>↳ ارسال تأیید به مشتری</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_ticket" value="1" @checked($notifTicket=='1')><span>🎧 ثبت تیکت جدید (به پشتیبان)</span></label>
                                <label class="settings-check"><input type="checkbox" name="notif_ticket_customer" value="1" @checked($notifTicketCustomer=='1')><span>↳ اعلام دریافت به مشتری</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_payment" value="1" @checked($notifPay=='1')><span>💳 تأیید پرداخت (رسید به مشتری)</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_low_stock" value="1" @checked($notifLowStock=='1')><span>📦 هشدار موجودی کم (به مدیر)</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_abandoned_cart" value="1" @checked($notifAbandonedCart=='1')><span>🛍 سبد رها شده (به مشتری، پس از ۲۴ ساعت)</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_birthday" value="1" @checked($notifBirthday=='1')><span>🎂 تبریک تولد مشتری</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_new_review" value="1" @checked($notifNewReview=='1')><span>⭐ ثبت نظر جدید (به مدیر)</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_membership_expire" value="1" @checked($notifMemExpire=='1')><span>👥 هشدار انقضای عضویت باشگاه</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_campaign" value="1" @checked($notifCamp=='1')><span>📢 پایان مهلت کمپین (به مدیر)</span></label>
                            </div>
                            <div class="notif-event">
                                <label class="settings-check"><input type="checkbox" name="notif_daily_report" value="1" @checked($notifDailyReport=='1')><span>📊 گزارش روزانه (به مدیر، ساعت ۹ صبح)</span></label>
                            </div>
                        </div>
                    </div>

                    {{-- گزارش بازگشت و حفظ خودکار قابل تنظیم --}}
                    <div class="settings-block is-accent" style="--block-color:#10b981; margin-top:1rem;">
                        <div class="settings-block-header"><h4>🛟 گزارش خودکار بازگشت و حفظ مشتری - قابل تنظیم</h4><small>مدیر می‌تواند خودش انتخاب کند چه روزها و چه ساعتی گزارش بازگشت و حفظ با گراف ماهانه شمسی را بگیرد. امکان ارسال انتهای روز هم هست.</small></div>
                        
                        <label class="settings-check" style="margin-bottom:.6rem;">
                            <input type="checkbox" name="recovery_report_enabled" value="1" @checked($recoveryEnabled=='1')>
                            <span>✅ فعال‌سازی گزارش خودکار بازگشت و حفظ (ایمیل + اعلان داخلی + ثبت در داشبورد)</span>
                        </label>

                        <div class="settings-form-grid">
                            <div class="settings-field">
                                <label>⏰ ساعت ارسال صبح (اصلی)</label>
                                <input type="time" name="recovery_report_time" value="{{ $recoveryTime }}" style="direction:ltr; text-align:center;">
                                <small>مثلاً 09:00 برای شنبه صبح</small>
                            </div>
                            <div class="settings-field">
                                <label>🌙 ارسال انتهای روز هم؟</label>
                                <div style="display:flex; gap:.5rem; align-items:center;">
                                    <label class="settings-check" style="margin:0;">
                                        <input type="checkbox" name="recovery_report_evening_enabled" value="1" @checked($recoveryEveningEnabled=='1')>
                                        <span>فعال</span>
                                    </label>
                                    <input type="time" name="recovery_report_evening_time" value="{{ $recoveryEveningTime }}" style="direction:ltr; text-align:center; max-width:120px;">
                                </div>
                                <small>مثلاً 21:00 برای جمع‌بندی انتهای روز</small>
                            </div>
                            <div class="settings-field col-span-2">
                                <label>📧 ایمیل اضافی برای گزارش (اختیاری - با کاما جدا کن)</label>
                                <input name="recovery_report_email" value="{{ $recoveryEmail }}" placeholder="مثلا manager@company.com, owner@company.com" style="direction:ltr;">
                                <small>علاوه بر ایمیل ادمین‌ها، به این ایمیل‌ها هم ارسال می‌شود</small>
                            </div>
                        </div>

                        <div style="margin-top:.9rem;">
                            <label style="font-weight:900; font-size:.84rem; display:block; margin-bottom:.4rem;">📅 چه روزهایی گزارش بگیرد؟</label>
                            <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
                                @php
                                    $weekDays = [
                                        6 => 'شنبه',
                                        0 => 'یکشنبه',
                                        1 => 'دوشنبه',
                                        2 => 'سه‌شنبه',
                                        3 => 'چهارشنبه',
                                        4 => 'پنج‌شنبه',
                                        5 => 'جمعه',
                                    ];
                                @endphp
                                @foreach($weekDays as $num => $name)
                                    <label class="settings-check" style="min-width:90px; background:var(--panel2); padding:.35rem .6rem; border-radius:.6rem; border:1px solid var(--line);">
                                        <input type="checkbox" name="recovery_report_days[]" value="{{ $num }}" @checked(in_array($num, $recoveryDaysArray))>
                                        <span>{{ $name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <small style="display:block; margin-top:.4rem; color:var(--mut);">مثلاً فقط شنبه، یا شنبه و چهارشنبه، یا هر روز - اگر همه را بزنی هر روز گزارش می‌آید</small>
                        </div>

                        <div style="margin-top:.9rem; padding:.7rem; background:var(--panel2); border-radius:.7rem; border:1px dashed var(--line); font-size:.8rem; line-height:1.8;">
                            <b>💡 نحوه کار:</b> سیستم هر ساعت تنظیمات را چک می‌کند. اگر الان در یکی از روزهای انتخابی باشی و ساعت برابر ساعت تنظیم شده (مثلاً 09:00 یا 21:00) باشد، گزارش با گراف ماهانه شمسی یونیک و لیست ۵ بازگشته برتر به ایمیل مدیران و ایمیل اضافی بالا ارسال می‌شود + در داشبورد به عنوان تسک ثبت می‌شود.
                        </div>
                    </div>

                    <div class="settings-form-footer"><button type="submit" class="btn">💾 ذخیره تنظیمات اعلان</button></div>
                </form>
            </section>

            {{-- ========== تب ۶: درگاه پرداخت ========== --}}
            <section class="settings-panel" data-panel="gateways">
                <header class="settings-panel-header">
                    <div><span class="chip">درگاه‌ها</span><h3>تنظیمات درگاه‌های پرداخت</h3>
                        <p>درگاه‌های واسط (زرین‌پال، زیبال، ...) و بانکی مستقیم (ملت، پارسیان، ...).</p></div>
                </header>
                <form method="post" action="{{ url('/app/settings') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="gateways">
                    <div class="settings-block is-accent" style="--block-color:#22c55e;">
                        <div class="settings-block-header"><h4>درگاه پیش‌فرض</h4><small>درگاهی که به‌طور خودکار برای مشتری باز می‌شود</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field col-span-3">
                                <label>انتخاب درگاه پیش‌فرض</label>
                                <select name="payment_default_gateway">
                                    @foreach($gateways as $key => $g)
                                        <option value="{{ $key }}" @selected($payDefault === $key)>{{ $g['label'] }} — {{ $g['kind'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @foreach([['title'=>'درگاه‌های واسط','kind'=>'واسط','icon'=>'💳','desc'=>'راه‌اندازی سریع، بدون نیاز به مجوز بانکی'], ['title'=>'درگاه‌های بانکی مستقیم','kind'=>'بانکی مستقیم','icon'=>'🏦','desc'=>'نیازمند مجوز مستقیم از بانک — کارمزد پایین‌تر']] as $group)
                        <div class="settings-block" style="margin-top:1rem;">
                            <div class="settings-block-header"><h4>{{ $group['title'] }}</h4><small>{{ $group['desc'] }}</small></div>
                            <div class="integration-grid">
                                @foreach($gateways as $key => $g)
                                    @continue($g['kind'] !== $group['kind'])
                                    <div class="integration-card">
                                        <header>
                                            <div class="logo" style="--int-color:{{ $g['color'] }};">{{ $group['icon'] }}</div>
                                            <div class="info"><h4>{{ $g['label'] }}</h4><small>{{ $g['kind'] }}</small></div>
                                        </header>
                                        @foreach($g['fields'] as $fieldName => $fieldValue)
                                            @if($fieldName === 'sandbox')
                                                <label class="settings-check"><input type="checkbox" name="gw_{{ $key }}_sandbox" value="1" @checked($fieldValue == '1')><span>حالت آزمایشی (Sandbox)</span></label>
                                            @else
                                                @php
                                                    $labelMap = ['merchant_id'=>'شناسه مرچنت','api_key'=>'کلید API','token'=>'توکن','api'=>'کلید سرویس','pin'=>'شناسه پین','terminal_id'=>'شناسه ترمینال','terminal_key'=>'کلید ترمینال','username'=>'نام کاربری','password'=>'رمز عبور','private_key'=>'کلید خصوصی'];
                                                    $nameSuffix = $fieldName === 'merchant_id' ? 'merchant' : ($fieldName === 'terminal_id' ? 'terminal' : ($fieldName === 'terminal_key' ? 'key' : ($fieldName === 'api_key' ? 'key' : $fieldName)));
                                                @endphp
                                                <div class="settings-field ltr">
                                                    <label>{{ $labelMap[$fieldName] ?? $fieldName }}</label>
                                                    <input name="gw_{{ $key }}_{{ $nameSuffix }}" value="{{ $fieldValue }}" {{ $fieldName === 'password' ? 'type="password"' : '' }} placeholder="{{ $labelMap[$fieldName] ?? $fieldName }}">
                                                </div>
                                            @endif
                                        @endforeach
                                        <div class="actions">
                                            <label class="settings-check"><input type="checkbox" name="gw_{{ $key }}_active" value="1" @checked($g['active'] == '1')><span>فعال‌سازی این درگاه</span></label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="settings-form-footer"><button type="submit" class="btn">💾 ذخیره تنظیمات درگاه‌ها</button></div>
                </form>
            </section>

            {{-- ========== تب ۷: ظاهر و برند (پیش‌نمایش زنده + تنظیمات کامل) ========== --}}
            <section class="settings-panel" data-panel="branding">
                <header class="settings-panel-header">
                    <div><span class="chip">طراحی</span><h3>ظاهر و برند فاکتور</h3>
                        <p>طراحی کامل ظاهر فاکتور با پیش‌نمایش زنده — قابل تنظیم برای هر دو حالت فاکتور و پیش‌فاکتور.</p></div>
                    <button type="button" class="btn" onclick="openFullPreview()">👁 پیش‌نمایش کامل</button>
                </header>

                <div class="branding-layout">

                    {{-- ستون چپ: فرم تنظیمات --}}
                    <form method="post" action="{{ url('/app/settings') }}" enctype="multipart/form-data" id="brandingForm" class="branding-form">
                        @csrf
                        <input type="hidden" name="_tab" value="branding">

                        {{-- کاغذ و طرح --}}
                        <div class="settings-block is-accent" style="--block-color:#ec4899;">
                            <div class="settings-block-header"><h4>کاغذ و طرح کلی</h4><small>اندازه کاغذ، جهت و طرح پایه</small></div>
                            <div class="settings-form-grid">
                                <div class="settings-field">
                                    <label>اندازه کاغذ</label>
                                    <select name="invoice_page_size" data-preview="pageSize">
                                        <option value="A4" @selected($invPageSize==='A4')>A4 (۲۱۰×۲۹۷ میلی‌متر)</option>
                                        <option value="A5" @selected($invPageSize==='A5')>A5 (۱۴۸×۲۱۰ میلی‌متر)</option>
                                    </select>
                                </div>
                                <div class="settings-field">
                                    <label>جهت صفحه</label>
                                    <select name="invoice_page_orient" data-preview="pageOrient">
                                        <option value="portrait"  @selected($invPageOrient==='portrait')>عمودی (طولی)</option>
                                        <option value="landscape" @selected($invPageOrient==='landscape')>افقی (عرضی)</option>
                                    </select>
                                </div>
                                <div class="settings-field">
                                    <label>حاشیه (میلی‌متر)</label>
                                    <input class="ltr" type="number" name="invoice_margin" min="0" max="30" value="{{ $invMargin }}" data-preview="margin">
                                </div>
                                <div class="settings-field col-span-3">
                                    <label>طرح فاکتور</label>
                                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                        <label class="settings-check"><input type="radio" name="invoice_theme" value="classic" @checked($invTheme==='classic') data-preview="theme"><span>کلاسیک رسمی</span></label>
                                        <label class="settings-check"><input type="radio" name="invoice_theme" value="modern" @checked($invTheme==='modern') data-preview="theme"><span>مدرن مینیمال</span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- رنگ‌ها --}}
                        <div class="settings-block is-accent" style="--block-color:#8b5cf6;">
                            <div class="settings-block-header"><h4>رنگ‌های سند</h4><small>رنگ اصلی و رنگ تأکیدی</small></div>
                            <div class="settings-form-grid">
                                <div class="settings-field col-span-3">
                                    <label>رنگ اصلی سند</label>
                                    <div class="color-row">
                                        <input type="color" name="invoice_primary" id="brandingColor" value="{{ $invPrimary }}" data-preview="primary">
                                        <div class="color-swatches">
                                            <button type="button" class="color-swatch" style="background:#0f172a;" data-color="#0f172a" onclick="pickBrandColor('#0f172a')" title="سرمه‌ای"></button>
                                            <button type="button" class="color-swatch" style="background:#10b981;" data-color="#10b981" onclick="pickBrandColor('#10b981')" title="سبز"></button>
                                            <button type="button" class="color-swatch" style="background:#0369a1;" data-color="#0369a1" onclick="pickBrandColor('#0369a1')" title="آبی"></button>
                                            <button type="button" class="color-swatch" style="background:#6d28d9;" data-color="#6d28d9" onclick="pickBrandColor('#6d28d9')" title="بنفش"></button>
                                            <button type="button" class="color-swatch" style="background:#b45309;" data-color="#b45309" onclick="pickBrandColor('#b45309')" title="نارنجی"></button>
                                            <button type="button" class="color-swatch" style="background:#b91c1c;" data-color="#b91c1c" onclick="pickBrandColor('#b91c1c')" title="قرمز"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="settings-field col-span-3">
                                    <label>رنگ تأکیدی (مبلغ نهایی، برچسب‌ها)</label>
                                    <input type="color" name="invoice_accent" value="{{ $invAccent }}" data-preview="accent" style="width:4rem; height:2.75rem;">
                                </div>
                            </div>
                        </div>

                        {{-- تایپوگرافی --}}
                        <div class="settings-block is-accent" style="--block-color:#3b82f6;">
                            <div class="settings-block-header"><h4>فونت و اندازه متن</h4></div>
                            <div class="settings-form-grid">
                                <div class="settings-field">
                                    <label>خانواده فونت</label>
                                    <select name="invoice_font_family" data-preview="fontFamily">
                                        <option value="inherit" @selected($invFontFamily==='inherit')>پیش‌فرض سایت</option>
                                        <option value="Vazirmatn, sans-serif" @selected($invFontFamily==='Vazirmatn, sans-serif')>وزیرمتن</option>
                                        <option value="IRANSans, sans-serif"  @selected($invFontFamily==='IRANSans, sans-serif')>ایران‌سنس</option>
                                        <option value="Sahel, sans-serif"     @selected($invFontFamily==='Sahel, sans-serif')>ساحل</option>
                                        <option value="Tanha, sans-serif"     @selected($invFontFamily==='Tanha, sans-serif')>تنها</option>
                                        <option value="'B Nazanin', serif"    @selected($invFontFamily==="'B Nazanin', serif")>بی‌نازنین</option>
                                    </select>
                                </div>
                                <div class="settings-field">
                                    <label>اندازه متن</label>
                                    <select name="invoice_font_size" data-preview="fontSize">
                                        <option value="small"  @selected($invFontSize==='small')>کوچک (فشرده)</option>
                                        <option value="medium" @selected($invFontSize==='medium')>متوسط (استاندارد)</option>
                                        <option value="large"  @selected($invFontSize==='large')>بزرگ (خوانا)</option>
                                    </select>
                                </div>
                                <div class="settings-field">
                                    <label>پس‌زمینه هدر</label>
                                    <select name="invoice_header_bg" data-preview="headerBg">
                                        <option value="primary" @selected($invHeaderBg==='primary')>رنگ اصلی</option>
                                        <option value="soft"    @selected($invHeaderBg==='soft')>ملایم</option>
                                        <option value="white"   @selected($invHeaderBg==='white')>سفید</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- کادر و جدول --}}
                        <div class="settings-block is-accent" style="--block-color:#f59e0b;">
                            <div class="settings-block-header"><h4>کادر و جدول</h4></div>
                            <div class="settings-form-grid">
                                <div class="settings-field">
                                    <label>نوع کادر دور سند</label>
                                    <select name="invoice_border_style" data-preview="borderStyle">
                                        <option value="solid"  @selected($invBorderStyle==='solid')>ممتد</option>
                                        <option value="dashed" @selected($invBorderStyle==='dashed')>خط‌چین</option>
                                        <option value="double" @selected($invBorderStyle==='double')>دو خطه</option>
                                        <option value="none"   @selected($invBorderStyle==='none')>بدون کادر</option>
                                    </select>
                                </div>
                                <div class="settings-field">
                                    <label>ضخامت کادر (px)</label>
                                    <input class="ltr" type="number" name="invoice_border_width" min="0" max="5" value="{{ $invBorderWidth }}" data-preview="borderWidth">
                                </div>
                                <div class="settings-field">
                                    <label>سبک جدول اقلام</label>
                                    <select name="invoice_table_style" data-preview="tableStyle">
                                        <option value="bordered" @selected($invTableStyle==='bordered')>کادردار</option>
                                        <option value="striped"  @selected($invTableStyle==='striped')>راه‌راه</option>
                                        <option value="minimal"  @selected($invTableStyle==='minimal')>مینیمال</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- لوگو --}}
                        <div class="settings-block is-accent" style="--block-color:#0ea5e9;">
                            <div class="settings-block-header"><h4>لوگوی سند</h4><small>فرمت‌های مجاز: JPG، PNG، WebP — همه به WebP تبدیل می‌شوند</small></div>
                            <div class="logo-upload-row">
                                <div class="logo-preview">
                                    @if(!empty($invLogoUrl))
                                        <img src="{{ asset(ltrim($invLogoUrl,'/')) }}?v={{ time() }}" alt="لوگو" id="brandingLogoImg" onerror="this.style.display='none'">
                                    @else
                                        <div class="logo-placeholder" id="brandingLogoPlaceholder">🖼</div>
                                    @endif
                                </div>
                                <div class="logo-controls">
                                    <label class="btn btn-ghost" style="cursor:pointer;">
                                        📁 انتخاب فایل لوگو
                                        <input type="file" name="invoice_logo_file" accept="image/*" data-hint="فرمت‌های مجاز: JPG، PNG، WebP - حداکثر ۴ مگابایت" style="display:none;" onchange="previewLogo(this)">
                                    </label>
                                    <small style="color:var(--mut); display:block; margin-top:0.5rem; line-height:2;">
                                        • حداکثر حجم: ۴ مگابایت • حداکثر عرض: ۶۰۰ px • خروجی: WebP کیفیت ۹۰٪
                                    </small>
                                    @if(!empty($invLogoUrl))
                                        <button type="button" class="settings-badge is-bad" onclick="if(confirm('لوگو حذف شود؟')) document.getElementById('deleteInvoiceLogoForm').submit();" style="margin-top:0.5rem;">🗑 حذف لوگو</button>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" name="invoice_logo_url" value="{{ $invLogoUrl }}">
                            <div class="settings-form-grid" style="margin-top:0.75rem;">
                                <div class="settings-field">
                                    <label>محل لوگو</label>
                                    <select name="invoice_logo_position" data-preview="logoPos">
                                        <option value="right"  @selected($invLogoPos==='right')>راست</option>
                                        <option value="center" @selected($invLogoPos==='center')>وسط</option>
                                        <option value="left"   @selected($invLogoPos==='left')>چپ</option>
                                    </select>
                                </div>
                                <div class="settings-field col-span-2">
                                    <label>متن جایگزین لوگو (وقتی تصویر ندارید)</label>
                                    <input name="invoice_logo_text" value="{{ $invLogoText }}" placeholder="حداکثر ۱۲ کاراکتر" maxlength="12" data-preview="logoText">
                                </div>
                                <div class="settings-field col-span-3">
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_logo" value="1" @checked($invShowLogo=='1') data-preview="showLogo"><span>نمایش لوگو در بالای فاکتور</span></label>
                                </div>
                            </div>
                        </div>

                        {{-- مهر، امضا، واتر مارک --}}
                        <div class="settings-block is-accent" style="--block-color:#ef4444;">
                            <div class="settings-block-header"><h4>مهر، امضا و واترمارک</h4></div>
                            <div class="settings-form-grid">
                                <div class="settings-field col-span-3">
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_sig" value="1" @checked($invShowSig=='1') data-preview="showSig"><span>نمایش بخش امضای فروشنده، خریدار و تاریخ</span></label>
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_stamp" value="1" @checked($invShowStamp=='1') data-preview="showStamp"><span>نمایش مهر (پرداخت شد، معتبر، ...)</span></label>
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_watermark" value="1" @checked($invShowWatermark=='1') data-preview="showWatermark"><span>نمایش واترمارک متنی در پس‌زمینه</span></label>
                                </div>
                                <div class="settings-field col-span-2"><label>نام و امضای فروشنده</label><input name="invoice_seller_sig" value="{{ $invSeller }}" data-preview="sellerSig"></div>
                                <div class="settings-field"><label>متن مهر</label><input name="invoice_stamp_text" value="{{ $invStampText }}" placeholder="مثلاً پرداخت شد" data-preview="stampText"></div>
                                <div class="settings-field"><label>رنگ مهر</label><input type="color" name="invoice_stamp_color" value="{{ $invStampColor }}" data-preview="stampColor" style="width:4rem; height:2.75rem;"></div>
                                <div class="settings-field col-span-2"><label>متن واترمارک</label><input name="invoice_watermark_text" value="{{ $invWatermarkText }}" placeholder="مثلاً نمونه، پیش‌فاکتور، محرمانه" data-preview="watermarkText"></div>
                            </div>
                        </div>

                        {{-- بارکد و QR --}}
                        <div class="settings-block is-accent" style="--block-color:#22c55e;">
                            <div class="settings-block-header"><h4>بارکد و QR رهگیری</h4><small>در پایین یا کنار سند نمایش داده می‌شود</small></div>
                            <div class="settings-form-grid">
                                <div class="settings-field col-span-3">
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_barcode" value="1" @checked($invShowBarcode=='1') data-preview="showBarcode"><span>نمایش بارکد شماره فاکتور</span></label>
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_qr" value="1" @checked($invShowQR=='1') data-preview="showQR"><span>نمایش QR رهگیری فاکتور</span></label>
                                </div>
                                <div class="settings-field">
                                    <label>محتوای QR</label>
                                    <select name="invoice_qr_content" data-preview="qrContent">
                                        <option value="auto"   @selected($invQrContent==='auto')>خودکار (لینک فاکتور)</option>
                                        <option value="url"    @selected($invQrContent==='url')>آدرس سایت</option>
                                        <option value="id"     @selected($invQrContent==='id')>فقط شماره فاکتور</option>
                                        <option value="custom" @selected($invQrContent==='custom')>متن دلخواه</option>
                                    </select>
                                </div>
                                <div class="settings-field col-span-2 ltr"><label>متن دلخواه QR (فقط اگر «متن دلخواه» انتخاب شد)</label><input name="invoice_qr_custom" value="{{ $invQrCustom }}" placeholder="مثلاً https://ayarpro.ir"></div>
                            </div>
                        </div>

                        {{-- متن‌ها --}}
                        <div class="settings-block is-accent" style="--block-color:#10b981;">
                            <div class="settings-block-header"><h4>متن‌های پیش‌فرض</h4></div>
                            <div class="settings-form-grid">
                                <div class="settings-field col-span-3">
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_notes" value="1" @checked($invShowNotes=='1') data-preview="showNotes"><span>نمایش بخش یادداشت و شرایط در فاکتور</span></label>
                                    <label class="settings-check"><input type="checkbox" name="invoice_show_total_word" value="1" @checked($invShowTotalWord=='1') data-preview="showTotalWord"><span>نمایش مبلغ نهایی به حروف زیر جدول</span></label>
                                </div>
                                <div class="settings-field col-span-3"><label>متن شرایط پیش‌فرض</label><textarea name="invoice_terms_text" data-preview="terms">{{ $invTerms }}</textarea></div>
                                <div class="settings-field col-span-3"><label>متن فوتر (پایین برگه)</label><input name="invoice_footer_text" value="{{ $invFooter }}" data-preview="footer"></div>
                            </div>
                        </div>

                        <div class="settings-form-footer">
                            <button type="button" class="btn btn-ghost" onclick="openFullPreview()">👁 پیش‌نمایش کامل</button>
                            <a class="btn btn-ghost" href="{{ url('/app/tax-invoices') }}">مشاهده فاکتور نمونه</a>
                            <button type="submit" class="btn">💾 ذخیره ظاهر فاکتور</button>
                        </div>
                    </form>

                    {{-- ستون راست: پیش‌نمایش زنده --}}
                    <div class="branding-preview-col">
                        <div class="branding-preview-sticky">
                            <div class="branding-preview-header">
                                <span class="chip">پیش‌نمایش زنده</span>
                                <small>تغییرات را همینجا ببینید</small>
                            </div>
                            <div class="branding-preview-wrap">
                                <div class="mini-invoice theme-{{ $invTheme }} page-{{ $invPageSize }} orient-{{ $invPageOrient }} font-{{ $invFontSize }} header-{{ $invHeaderBg }} table-{{ $invTableStyle }}"
                                     id="miniInvoice"
                                     style="--mini-primary: {{ $invPrimary }}; --mini-accent: {{ $invAccent }}; --mini-border-style: {{ $invBorderStyle }}; --mini-border-width: {{ $invBorderWidth }}px; --mini-font: {{ $invFontFamily }}; --mini-margin: {{ $invMargin }}mm;">
                                    <div class="mini-frame">
                                        <div class="mini-watermark" id="miniWatermark" @if($invShowWatermark!='1') style="display:none;" @endif>{{ $invWatermarkText }}</div>
                                        <div class="mini-stamp" id="miniStamp" @if($invShowStamp!='1') style="display:none;" @endif style="color: {{ $invStampColor }}; border-color: {{ $invStampColor }};">{{ $invStampText }}</div>

                                        <div class="mini-head logo-{{ $invLogoPos }}" id="miniHead">
                                            <div class="mini-logo" id="miniLogo" @if($invShowLogo!='1') style="display:none;" @endif>
                                                @if(!empty($invLogoUrl))
                                                    <img src="{{ asset(ltrim($invLogoUrl,'/')) }}?v={{ time() }}" alt="لوگو" id="miniLogoImg">
                                                @else
                                                    <span id="miniLogoText">{{ $invLogoText }}</span>
                                                @endif
                                            </div>
                                            <div class="mini-title">
                                                <h1>فاکتور نمونه</h1>
                                                <small>شماره: ۱۰۰۱ · تاریخ: امروز</small>
                                            </div>
                                        </div>

                                        <div class="mini-parties">
                                            <div><b>فروشنده:</b> {{ $bizName ?: 'کسب‌وکار شما' }}</div>
                                            <div><b>خریدار:</b> مشتری نمونه</div>
                                        </div>

                                        <table class="mini-items">
                                            <thead>
                                                <tr><th>ردیف</th><th>شرح</th><th>تعداد</th><th>مبلغ</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr><td>۱</td><td>محصول اول</td><td>۲</td><td>۱۰۰,۰۰۰</td></tr>
                                                <tr><td>۲</td><td>محصول دوم</td><td>۱</td><td>۲۵۰,۰۰۰</td></tr>
                                                <tr><td>۳</td><td>خدمات نصب</td><td>۱</td><td>۵۰,۰۰۰</td></tr>
                                            </tbody>
                                        </table>

                                        <div class="mini-totals">
                                            <div><span>جمع:</span> <b>۴۰۰,۰۰۰ تومان</b></div>
                                            <div class="grand"><span>قابل پرداخت:</span> <b>۴۳۶,۰۰۰ تومان</b></div>
                                        </div>

                                        <div class="mini-word" id="miniWord" @if($invShowTotalWord!='1') style="display:none;" @endif>
                                            به حروف: چهارصد و سی و شش هزار تومان
                                        </div>

                                        <div class="mini-notes" id="miniNotes" @if($invShowNotes!='1') style="display:none;" @endif>
                                            <b>یادداشت:</b> <span id="miniTerms">{{ $invTerms }}</span>
                                        </div>

                                        <div class="mini-sig" id="miniSig" @if($invShowSig!='1') style="display:none;" @endif>
                                            <div><small>مهر فروشنده</small><br><b id="miniSellerSig">{{ $invSeller }}</b></div>
                                            <div><small>امضای خریدار</small><br><b>—</b></div>
                                        </div>

                                        <div class="mini-codes">
                                            <div class="mini-barcode" id="miniBarcode" @if($invShowBarcode!='1') style="display:none;" @endif>
                                                <div class="bars"></div>
                                                <small>1001-INV</small>
                                            </div>
                                            <div class="mini-qr" id="miniQR" @if($invShowQR!='1') style="display:none;" @endif>
                                                <div class="qr-pattern"></div>
                                                <small>اسکن</small>
                                            </div>
                                        </div>

                                        <div class="mini-foot" id="miniFoot">{{ $invFooter }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="branding-preview-hint">
                                💡 این پیش‌نمایش کوچک است. برای مشاهده کامل روی دکمه <b>پیش‌نمایش کامل</b> بزنید.
                            </div>
                        </div>
                    </div>
                </div>

            </section>

            {{-- ========== تب طراحی سایت - جدید ========== --}}
            <section class="settings-panel" data-panel="design">
                <header class="settings-panel-header">
                    <div><span class="chip">شخصی‌سازی</span><h3>تنظیمات طراحی سایت</h3>
                        <p>مدیر هر کسب‌وکار می‌تواند نام برند، لوگو، فاوآیکن، آیکون و تم پیش‌فرض سایت خودش را شخصی‌سازی کند. این تنظیمات بلافاصله در هدر پنل مدیریت و صفحه ورود نمایش داده می‌شود.</p></div>
                </header>
                <form method="post" action="{{ url('/app/settings') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_tab" value="design">

                    <div class="settings-block is-accent" style="--block-color:#f59e0b;">
                        <div class="settings-block-header"><h4>هویت برند</h4><small>نام و شعار برند شما به جای مشتری‌یار نمایش داده می‌شود</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field"><label>نام برند یا شرکت</label><input name="site_brand_name" value="{{ $siteBrandName }}" placeholder="مثلاً فروشگاه آراد"></div>
                            <div class="settings-field"><label>شعار یا توضیح کوتاه برند</label><input name="site_brand_tagline" value="{{ $siteBrandTagline }}" placeholder="مثلاً مدیریت هوشمند مشتریان"></div>
                            <div class="settings-field"><label>نام صاحب برند (نمایش در فوتر)</label><input name="site_owner_name" value="{{ $siteOwnerName }}" placeholder="مثلاً هومن‌وب"></div>
                            <div class="settings-field ltr"><label>آدرس سایت صاحب برند</label><input name="site_owner_url" value="{{ $siteOwnerUrl }}" placeholder="https://example.com"></div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#0ea5e9;">
                        <div class="settings-block-header"><h4>لوگو و فاوآیکن</h4><small>لوگو در هدر پنل و صفحه ورود، فاوآیکن در تب مرورگر نمایش داده می‌شود</small></div>
                        <div class="settings-form-grid design-logo-grid">
                            <div class="settings-field col-span-3">
                                <label>لوگوی سایت (پیشنهاد 256×256، PNG یا SVG)</label>
                                <div class="logo-upload-row">
                                    <div class="logo-preview" style="width:5rem; height:5rem; border-radius:1rem;">
                                        @if(!empty($siteLogoUrl))
                                            <img src="{{ asset(ltrim($siteLogoUrl,'/')) }}?v={{ time() }}" alt="لوگو">
                                        @else
                                            <div class="logo-placeholder" style="font-size:2rem;">{{ $siteIcon }}</div>
                                        @endif
                                    </div>
                                    <div class="logo-controls">
                                        <label class="btn btn-ghost" style="cursor:pointer;">
                                            📁 انتخاب لوگو
                                            <input type="file" name="site_logo_file" accept="image/*" data-hint="فرمت‌های مجاز: JPG، PNG، WebP، SVG - حداکثر ۴ مگابایت" style="display:none;" onchange="previewDesignLogo(this)">
                                        </label>
                                        @if(!empty($siteLogoUrl))
                                            <button type="button" class="settings-badge is-bad" onclick="if(confirm('لوگوی سایت حذف شود؟')) document.getElementById('deleteSiteLogoForm').submit();" style="margin-top:0.5rem;">🗑 حذف لوگو</button>
                                        @endif
                                        <small style="color:var(--mut); display:block; margin-top:0.5rem; line-height:1.9;">فرمت‌های مجاز JPG, PNG, WebP, SVG - حداکثر ۴ مگابایت</small>
                                    </div>
                                </div>
                                <input type="hidden" name="site_logo_url" value="{{ $siteLogoUrl }}">
                            </div>

                            <div class="settings-field col-span-3">
                                <label>فاوآیکن (آیکون تب مرورگر - 32×32 یا 64×64)</label>
                                <div class="logo-upload-row">
                                    <div class="logo-preview" style="width:3.5rem; height:3.5rem; border-radius:.7rem;">
                                        @if(!empty($siteFaviconUrl))
                                            <img src="{{ asset(ltrim($siteFaviconUrl,'/')) }}?v={{ time() }}" alt="فاوآیکن">
                                        @else
                                            <div class="logo-placeholder" style="font-size:1.5rem;">🌐</div>
                                        @endif
                                    </div>
                                    <div class="logo-controls">
                                        <label class="btn btn-ghost" style="cursor:pointer;">
                                            🌐 انتخاب فاوآیکن
                                            <input type="file" name="site_favicon_file" accept="image/*,.ico" data-hint="فرمت‌های مجاز: ICO، PNG، WebP، SVG - حداکثر ۲ مگابایت" style="display:none;" onchange="previewDesignFavicon(this)">
                                        </label>
                                        @if(!empty($siteFaviconUrl))
                                            <button type="button" class="settings-badge is-bad" onclick="if(confirm('فاوآیکن حذف شود؟')) document.getElementById('deleteFaviconForm').submit();" style="margin-top:0.5rem;">🗑 حذف فاوآیکن</button>
                                        @endif
                                        <small style="color:var(--mut); display:block; margin-top:0.5rem; line-height:1.9;">فرمت‌های مجاز ICO, PNG, WebP, SVG - حداکثر ۲ مگابایت</small>
                                    </div>
                                </div>
                                <input type="hidden" name="site_favicon_url" value="{{ $siteFaviconUrl }}">
                            </div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#8b5cf6;">
                        <div class="settings-block-header"><h4>ظاهر پیش‌فرض پنل</h4><small>مدیر کسب‌وکار می‌تواند تم پیش‌فرض روشن یا تیره را برای کاربرانش انتخاب کند</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field">
                                <label>تم پیش‌فرض سایت</label>
                                <select name="site_default_theme">
                                    <option value="light" @selected($siteDefaultTheme==='light')>☀️ روشن (پیش‌فرض)</option>
                                    <option value="dark" @selected($siteDefaultTheme==='dark')>🌙 تیره</option>
                                </select>
                                <small>کاربر می‌تواند بعداً از بالای صفحه تم را عوض کند، ولی اولین بار این تم نمایش داده می‌شود.</small>
                            </div>
                            <div class="settings-field">
                                <label>آیکون برند (نمایش در هدر کنار نام)</label>
                                <select name="site_icon">
                                    <option value="logo" @selected($siteIcon==='logo')>🖼️ استفاده از لوگوی سایت</option>
                                    <option value="✦" @selected($siteIcon==='✦')>✦ ستاره</option>
                                    <option value="✨" @selected($siteIcon==='✨')>✨ درخشان</option>
                                    <option value="🚀" @selected($siteIcon==='🚀')>🚀 موشک</option>
                                    <option value="💎" @selected($siteIcon==='💎')>💎 الماس</option>
                                    <option value="🔥" @selected($siteIcon==='🔥')>🔥 آتش</option>
                                    <option value="⭐" @selected($siteIcon==='⭐')>⭐ ستاره توپر</option>
                                    <option value="🌟" @selected($siteIcon==='🌟')>🌟 ستاره برجسته</option>
                                    <option value="🎯" @selected($siteIcon==='🎯')>🎯 هدف</option>
                                    <option value="🏢" @selected($siteIcon==='🏢')>🏢 شرکت</option>
                                    <option value="🛒" @selected($siteIcon==='🛒')>🛒 فروشگاه</option>
                                    <option value="💼" @selected($siteIcon==='💼')>💼 کسب‌وکار</option>
                                </select>
                                <small style="display:block; margin-top:.4rem; color:var(--mut);">اگر «استفاده از لوگوی سایت» را انتخاب کنید و لوگو آپلود شده باشد، لوگوی شما به جای آیکون نمایش داده می‌شود.</small>
                            </div>
                            <div class="settings-field">
                                <label>رنگ اصلی برند</label>
                                <input type="color" name="site_primary_color" value="{{ $sitePrimaryColor }}" style="width:4rem; height:2.8rem;">
                                <small>این رنگ به صورت خودکار در دکمه‌ها، هدر، آیکن‌های منو و لینک‌ها اعمال می‌شود.</small>
                            </div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#10b981;">
                        <div class="settings-block-header"><h4>تنظیمات ظاهر پیشخوان باشگاه مشتریان</h4><small>لوگو، نام برند و تم پیش‌فرض که مشتریان در باشگاه می‌بینند</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field"><label>نام برند در باشگاه مشتریان</label><input name="club_brand_name" value="{{ $clubBrandName }}" placeholder="مثلاً باشگاه وفاداری آراد"></div>
                            <div class="settings-field">
                                <label>تم پیش‌فرض باشگاه مشتریان</label>
                                <select name="club_default_theme">
                                    <option value="light" @selected($clubDefaultTheme==='light')>☀️ روشن</option>
                                    <option value="dark" @selected($clubDefaultTheme==='dark')>🌙 تیره</option>
                                </select>
                            </div>
                            <div class="settings-field">
                                <label>رنگ اصلی باشگاه</label>
                                <input type="color" name="club_primary_color" value="{{ $clubPrimaryColor }}" style="width:4rem; height:2.8rem;">
                                <small>در هدر و دکمه‌های باشگاه اعمال می‌شود</small>
                            </div>
                            <div class="settings-field col-span-3">
                                <label>لوگوی باشگاه مشتریان (اگر خالی باشد از لوگوی اصلی سایت استفاده می‌شود)</label>
                                <div class="logo-upload-row">
                                    <div class="logo-preview" style="width:4rem; height:4rem;">
                                        @if(!empty($clubLogoUrl))
                                            <img src="{{ asset(ltrim($clubLogoUrl,'/')) }}?v={{ time() }}" alt="لوگوی باشگاه">
                                        @else
                                            <div class="logo-placeholder">🎁</div>
                                        @endif
                                    </div>
                                    <div class="logo-controls">
                                        <label class="btn btn-ghost" style="cursor:pointer;">
                                            🎁 انتخاب لوگوی باشگاه
                                            <input type="file" name="club_logo_file" accept="image/*" data-hint="فرمت‌های مجاز: JPG، PNG، WebP، SVG" style="display:none;" onchange="previewDesignLogo(this)">
                                        </label>
                                        @if(!empty($clubLogoUrl))
                                            <button type="button" class="settings-badge is-bad" onclick="if(confirm('لوگوی باشگاه حذف شود؟')) document.getElementById('deleteClubLogoForm').submit();">🗑 حذف لوگو</button>
                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" name="club_logo_url" value="{{ $clubLogoUrl }}">
                            </div>
                        </div>
                    </div>

                    <div class="settings-block" style="background:linear-gradient(135deg, rgba(245,158,11,.10), rgba(14,165,233,.06)); border-color:rgba(245,158,11,.22);">
                        <div class="settings-block-header"><h4>💡 پیش‌نمایش زنده برند</h4><small>همین الان ببین لوگو و نام جدید چطور نمایش داده می‌شود</small></div>
                        <div style="display:flex; gap:1rem; align-items:center; padding:1rem; background:var(--panel); border-radius:1rem; border:1px solid var(--line);">
                            <div style="width:3rem; height:3rem; border-radius:.9rem; background:linear-gradient(135deg, var(--acc2), var(--acc)); display:grid; place-items:center; color:#fff; font-size:1.4rem; overflow:hidden;">
                                @if(!empty($siteLogoUrl))
                                    <img src="{{ asset(ltrim($siteLogoUrl,'/')) }}?v={{ time() }}" alt="لوگو" style="width:100%; height:100%; object-fit:cover;">
                                @else
                                    <span id="previewBrandIcon">{{ $siteIcon }}</span>
                                @endif
                            </div>
                            <div>
                                <b id="previewBrandName" style="display:block; font-size:1.1rem; color:var(--txt);">{{ $siteBrandName }}</b>
                                <small id="previewBrandTagline" style="color:var(--mut);">{{ $siteBrandTagline }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="settings-form-footer">
                        <button type="submit" class="btn">💾 ذخیره تنظیمات طراحی</button>
                    </div>
                </form>
            </section>

            
            {{-- ========== تب ۸: امنیت و بک‌آپ ========== --}}
            <section class="settings-panel" data-panel="security">
                <header class="settings-panel-header">
                    <div><span class="chip">امنیت</span><h3>امنیت، لاگ فعالیت و بک‌آپ</h3>
                        <p>ورود دومرحله‌ای دقیق، ثبت لاگ، قفل خودکار و بک‌آپ روی مقاصد مختلف.</p></div>
                </header>
                <form method="post" action="{{ url('/app/settings') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="security">

                    {{-- ۲FA --}}
                    <div class="settings-block is-accent" style="--block-color:#ef4444;">
                        <div class="settings-block-header"><h4>ورود دو مرحله‌ای (2FA)</h4><small>تنظیمات دقیق روش‌های احراز هویت</small></div>
                        <label class="settings-check" style="margin-bottom:0.75rem;">
                            <input type="checkbox" name="security_two_fa" value="1" @checked($twoFA=='1')>
                            <span><b>فعال‌سازی ورود دو مرحله‌ای</b></span>
                        </label>
                        <div class="two-fa-methods">
                            <div class="settings-block-header"><h4 style="font-size:0.85rem;">روش‌های احراز</h4></div>
                            <label class="settings-check"><input type="checkbox" name="two_fa_sms" value="1" @checked($twoFaSms=='1')><span>📱 ارسال کد از طریق پیامک</span></label>
                            <label class="settings-check"><input type="checkbox" name="two_fa_email" value="1" @checked($twoFaEmail=='1')><span>📧 ارسال کد از طریق ایمیل</span></label>
                            <label class="settings-check"><input type="checkbox" name="two_fa_totp" value="1" @checked($twoFaTotp=='1')><span>🔑 اپلیکیشن TOTP (Google Authenticator، Authy، ...)</span></label>
                        </div>
                        <div class="settings-form-grid" style="margin-top:0.75rem;">
                            <div class="settings-field">
                                <label>طول کد تأیید</label>
                                <select name="two_fa_code_length">
                                    <option value="4" @selected($twoFaCodeLen=='4')>۴ رقمی</option>
                                    <option value="5" @selected($twoFaCodeLen=='5')>۵ رقمی</option>
                                    <option value="6" @selected($twoFaCodeLen=='6')>۶ رقمی (استاندارد)</option>
                                    <option value="8" @selected($twoFaCodeLen=='8')>۸ رقمی</option>
                                </select>
                            </div>
                            <div class="settings-field">
                                <label>اعتبار کد (دقیقه)</label>
                                <input class="ltr" name="two_fa_expire_min" type="number" min="1" max="30" value="{{ $twoFaExpire }}">
                                <small>پس از این مدت، کد تأیید منقضی می‌شود.</small>
                            </div>
                            <div class="settings-field">
                                <label>محدوده کاربران</label>
                                <label class="settings-check"><input type="checkbox" name="two_fa_admin_only" value="1" @checked($twoFaAdminOnly=='1')><span>فقط برای مدیران سیستم</span></label>
                            </div>
                        </div>
                    </div>

                    {{-- لاگ و رمز قوی --}}
                    <div class="settings-block is-accent" style="--block-color:#8b5cf6;">
                        <div class="settings-block-header"><h4>سیاست ورود و لاگ</h4><small>ثبت لاگ فعالیت و اجبار رمز عبور قوی</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field col-span-3">
                                <label class="settings-check"><input type="checkbox" name="security_log_activity" value="1" @checked($logActivity=='1')><span>ثبت لاگ فعالیت کاربران (ورود، تغییر، حذف)</span></label>
                                <label class="settings-check"><input type="checkbox" name="security_force_strong_password" value="1" @checked($strongPass=='1')><span>اجبار رمز عبور قوی (حداقل ۸ کاراکتر + حرف + عدد)</span></label>
                                <label class="settings-check"><input type="checkbox" name="security_lock_after_fail" value="1" @checked($lockAfter=='1')><span>قفل خودکار پس از تلاش ناموفق</span></label>
                            </div>
                            <div class="settings-field">
                                <label>مدت نشست فعال (دقیقه)</label>
                                <input class="ltr" name="security_session_min" type="number" min="10" max="1440" value="{{ $sessionMin }}">
                            </div>
                            <div class="settings-field">
                                <label>تعداد تلاش قبل از قفل</label>
                                <input class="ltr" name="security_lock_threshold" type="number" min="3" max="20" value="{{ $lockThreshold }}">
                            </div>
                            <div class="settings-field">
                                <label>مدت قفل (دقیقه)</label>
                                <input class="ltr" name="security_lock_minutes" type="number" min="1" max="1440" value="{{ $lockMinutes }}">
                            </div>
                        </div>
                    </div>

                    {{-- IP Whitelist --}}
                    <div class="settings-block is-accent" style="--block-color:#0ea5e9;">
                        <div class="settings-block-header"><h4>محدودیت IP</h4><small>محدود کردن ورود مدیر به آدرس‌های خاص</small></div>
                        <label class="settings-check"><input type="checkbox" name="security_ip_whitelist_enabled" value="1" @checked($ipWhitelistOn=='1')><span>فعال‌سازی محدودیت IP</span></label>
                        <div class="settings-form-grid" style="margin-top:0.5rem;">
                            <div class="settings-field col-span-3 ltr"><label>لیست IP مجاز (با ویرگول جدا کنید)</label><textarea name="security_ip_whitelist" placeholder="192.168.1.1, 10.0.0.5, 5.160.x.x">{{ $ipWhitelist }}</textarea></div>
                        </div>
                    </div>

                    {{-- بک‌آپ --}}
                    <div class="settings-block is-accent" style="--block-color:#22c55e;">
                        <div class="settings-block-header"><h4>بک‌آپ خودکار دیتابیس</h4><small>پیکربندی تناوب، محتوا و مقصد بک‌آپ</small></div>
                        <label class="settings-check" style="margin-bottom:0.75rem;">
                            <input type="checkbox" name="backup_auto" value="1" @checked($backupAuto=='1')>
                            <span><b>فعال‌سازی بک‌آپ خودکار</b></span>
                        </label>
                        <div class="settings-form-grid">
                            <div class="settings-field">
                                <label>دوره تناوب</label>
                                <select name="backup_frequency">
                                    <option value="hourly"  @selected($backupFreq==='hourly')>هر ساعت</option>
                                    <option value="daily"   @selected($backupFreq==='daily')>روزانه</option>
                                    <option value="weekly"  @selected($backupFreq==='weekly')>هفتگی</option>
                                    <option value="monthly" @selected($backupFreq==='monthly')>ماهانه</option>
                                </select>
                            </div>
                            <div class="settings-field">
                                <label>ساعت اجرا</label>
                                <input class="ltr" name="backup_time" type="time" value="{{ $backupTime }}">
                            </div>
                            <div class="settings-field">
                                <label>نگهداری تا (روز)</label>
                                <input class="ltr" name="backup_retention_days" type="number" min="1" max="365" value="{{ $backupRetain }}">
                                <small>بک‌آپ‌های قدیمی‌تر از این تعداد روز، حذف می‌شوند.</small>
                            </div>
                            <div class="settings-field col-span-3">
                                <label class="settings-check"><input type="checkbox" name="backup_include_files" value="1" @checked($backupIncludeFile=='1')><span>علاوه بر دیتابیس، فایل‌های آپلود شده هم بک‌آپ گرفته شود</span></label>
                            </div>
                        </div>

                        <div class="backup-targets">
                            <div class="settings-block-header" style="margin-top:0.75rem;"><h4 style="font-size:0.85rem;">محل ذخیره بک‌آپ (می‌توانید چند مقصد را انتخاب کنید)</h4></div>

                            <div class="messenger-block">
                                <label class="settings-check messenger-toggle">
                                    <input type="checkbox" name="backup_target_local" value="1" @checked($backupTargetLocal=='1')>
                                    <span>💾 داخل هاست (پیش‌فرض: <code>storage/backups</code>)</span>
                                </label>
                                <small style="color:var(--mut); display:block; margin-top:0.35rem;">
                                    امن‌ترین و سریع‌ترین گزینه — اما اگر هاست از دست برود، بک‌آپ هم از بین می‌رود.
                                </small>
                            </div>

                            <div class="messenger-block">
                                <label class="settings-check messenger-toggle">
                                    <input type="checkbox" name="backup_target_hosting" value="1" @checked($backupTargetHost=='1')>
                                    <span>📁 پوشه دلخواه در هاست</span>
                                </label>
                                <div class="settings-form-grid" style="margin-top:0.5rem;">
                                    <div class="settings-field col-span-3 ltr"><label>مسیر مطلق پوشه (خارج از پوشه سایت پیشنهاد می‌شود)</label><input name="backup_hosting_path" value="{{ $backupHostPath }}" placeholder="/home/ayarproi/backups"></div>
                                </div>
                            </div>

                            <div class="messenger-block">
                                <label class="settings-check messenger-toggle">
                                    <input type="checkbox" name="backup_target_google" value="1" @checked($backupTargetGoogle=='1')>
                                    <span>☁ گوگل درایو (Google Drive)</span>
                                </label>
                                <div class="settings-form-grid" style="margin-top:0.5rem;">
                                    <div class="settings-field ltr"><label>Client ID</label><input name="backup_google_client_id" value="{{ $backupGoogleClientId }}" placeholder="xxx.apps.googleusercontent.com"></div>
                                    <div class="settings-field ltr"><label>Client Secret</label><input name="backup_google_client_secret" type="password" value="{{ $backupGoogleClientSec }}"></div>
                                    <div class="settings-field ltr"><label>Folder ID (اختیاری)</label><input name="backup_google_folder_id" value="{{ $backupGoogleFolderId }}" placeholder="1AbC..."></div>
                                </div>
                            </div>

                            <div class="messenger-block">
                                <label class="settings-check messenger-toggle">
                                    <input type="checkbox" name="backup_target_ftp" value="1" @checked($backupTargetFtp=='1')>
                                    <span>🌐 سرور FTP خارجی (بک‌آپ روی سرور دیگر)</span>
                                </label>
                                <div class="settings-form-grid" style="margin-top:0.5rem;">
                                    <div class="settings-field ltr col-span-2"><label>هاست FTP</label><input name="backup_ftp_host" value="{{ $backupFtpHost }}" placeholder="ftp.example.com"></div>
                                    <div class="settings-field ltr"><label>پورت</label><input name="backup_ftp_port" type="number" value="{{ $backupFtpPort }}"></div>
                                    <div class="settings-field ltr"><label>نام کاربری</label><input name="backup_ftp_username" value="{{ $backupFtpUser }}"></div>
                                    <div class="settings-field ltr"><label>رمز عبور</label><input name="backup_ftp_password" type="password" value="{{ $backupFtpPass }}" placeholder="اگر تغییر نمی‌دهید خالی بگذارید"></div>
                                    <div class="settings-field ltr"><label>مسیر مقصد</label><input name="backup_ftp_path" value="{{ $backupFtpPath }}" placeholder="/backups"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-form-footer">
                        <button type="submit" class="btn">💾 ذخیره تنظیمات امنیت و بک‌آپ</button>
                    </div>
                </form>
            </section>

            {{-- ========== تب ۹: سئو ========== --}}
            <section class="settings-panel" data-panel="seo">
                <header class="settings-panel-header">
                    <div><span class="chip">سئو</span><h3>بهینه‌سازی موتور جستجو</h3>
                        <p>عنوان، توضیح، کلمات کلیدی، اشتراک‌گذاری در شبکه‌های اجتماعی، تأیید موتورهای جستجو و نقشه سایت.</p></div>
                </header>
                <form method="post" action="{{ url('/app/settings') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="seo">

                    <div class="settings-block is-accent" style="--block-color:#14b8a6;">
                        <div class="settings-block-header"><h4>معرفی اصلی سایت</h4><small>عنوان، توضیح و کلمات کلیدی که در نتایج جستجو نمایش داده می‌شوند</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field col-span-2"><label>عنوان سایت</label><input name="seo_title" value="{{ $seoTitle }}" maxlength="80"><small>حداکثر ۶۰ تا ۷۰ کاراکتر برای نمایش کامل در نتایج گوگل.</small></div>
                            <div class="settings-field">
                                <label>جداکننده عنوان</label>
                                <select name="seo_title_separator">
                                    <option value="-"  @selected($seoTitleSep==='-')>خط تیره ( - )</option>
                                    <option value="|"  @selected($seoTitleSep==='|')>خط عمودی ( | )</option>
                                    <option value="—"  @selected($seoTitleSep==='—')>خط بلند ( — )</option>
                                    <option value="•"  @selected($seoTitleSep==='•')>گلوله ( • )</option>
                                </select>
                            </div>
                            <div class="settings-field col-span-3"><label>توضیح کوتاه</label><textarea name="seo_description" maxlength="180">{{ $seoDesc }}</textarea><small>حداکثر ۱۵۰ تا ۱۶۰ کاراکتر — این متن زیر عنوان در نتایج گوگل نمایش داده می‌شود.</small></div>
                            <div class="settings-field col-span-3"><label>کلمات کلیدی (با ویرگول جدا کنید)</label><input name="seo_keywords" value="{{ $seoKeywords }}" placeholder="مثلاً: سی آر ام، مدیریت مشتری، فروشگاه"></div>
                            <div class="settings-field">
                                <label>زبان اصلی محتوا</label>
                                <select name="seo_lang">
                                    <option value="fa-IR" @selected($seoLang==='fa-IR')>فارسی</option>
                                    <option value="en-US" @selected($seoLang==='en-US')>انگلیسی</option>
                                    <option value="ar-SA" @selected($seoLang==='ar-SA')>عربی</option>
                                </select>
                            </div>
                            <div class="settings-field col-span-2"><label>نام نویسنده پیش‌فرض</label><input name="seo_default_author" value="{{ $seoAuthor }}" placeholder="نام مدیر سایت یا نویسنده اصلی"></div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#3b82f6;">
                        <div class="settings-block-header"><h4>وضعیت نمایش در موتورهای جستجو</h4><small>کنترل نمایه‌سازی و آدرس اصلی سایت</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field">
                                <label>اجازه نمایش در گوگل</label>
                                <select name="seo_robots">
                                    <option value="index,follow"     @selected($seoRobots==='index,follow')>نمایه‌سازی و دنبال کردن لینک‌ها (پیشنهادی)</option>
                                    <option value="index,nofollow"   @selected($seoRobots==='index,nofollow')>نمایه‌سازی، بدون دنبال کردن لینک‌ها</option>
                                    <option value="noindex,follow"   @selected($seoRobots==='noindex,follow')>بدون نمایه‌سازی، دنبال کردن لینک‌ها</option>
                                    <option value="noindex,nofollow" @selected($seoRobots==='noindex,nofollow')>بدون نمایه‌سازی و بدون دنبال کردن</option>
                                </select>
                                <small>اگر می‌خواهید سایت در گوگل دیده شود، گزینه اول را انتخاب کنید.</small>
                            </div>
                            <div class="settings-field col-span-2 ltr"><label>آدرس اصلی سایت</label><input name="seo_canonical_url" value="{{ $seoCanonical }}" placeholder="https://ayarpro.ir"><small>آدرس رسمی سایت شما — برای جلوگیری از محتوای تکراری.</small></div>
                            <div class="settings-field col-span-3">
                                <label class="settings-check"><input type="checkbox" name="seo_sitemap_auto" value="1" @checked($seoSitemapAuto=='1')><span>ساخت خودکار نقشه سایت (sitemap.xml) — شامل همه صفحه‌ها، محصولات و مطالب</span></label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#8b5cf6;">
                        <div class="settings-block-header"><h4>پیش‌نمایش هنگام اشتراک‌گذاری</h4><small>نحوه نمایش سایت شما هنگام ارسال لینک در پیام‌رسان‌ها و شبکه‌های اجتماعی</small></div>
                        <label class="settings-check" style="margin-bottom:0.5rem;"><input type="checkbox" name="seo_share_enabled" value="1" @checked($seoShareEnabled=='1')><span>فعال‌سازی پیش‌نمایش زیبا در هنگام اشتراک‌گذاری لینک</span></label>
                        <div class="settings-form-grid">
                            <div class="settings-field col-span-3 ltr"><label>آدرس تصویر پیش‌نمایش</label><input name="seo_share_image" value="{{ $seoShareImage }}" placeholder="/uploads/branding/share.webp"><small>اندازه پیشنهادی: ۱۲۰۰ × ۶۳۰ پیکسل — این تصویر در تلگرام، واتساپ، بله، ایتا، لینکدین و ... نمایش داده می‌شود.</small></div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#ec4899;">
                        <div class="settings-block-header"><h4>حساب‌های شبکه‌های اجتماعی</h4><small>آدرس صفحه‌های شما در شبکه‌های اجتماعی و پیام‌رسان‌ها (در فوتر سایت و اطلاعات سازمان استفاده می‌شوند)</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field ltr"><label>📷 اینستاگرام</label><input name="social_instagram" value="{{ $socialInstagram }}" placeholder="https://instagram.com/username"></div>
                            <div class="settings-field ltr"><label>📨 تلگرام</label><input name="social_telegram" value="{{ $socialTelegram }}" placeholder="https://t.me/username"></div>
                            <div class="settings-field ltr"><label>💚 واتساپ</label><input name="social_whatsapp" value="{{ $socialWhatsapp }}" placeholder="https://wa.me/989XXXXXXXXX"></div>
                            <div class="settings-field ltr"><label>💼 لینکدین</label><input name="social_linkedin" value="{{ $socialLinkedin }}" placeholder="https://linkedin.com/company/name"></div>
                            <div class="settings-field ltr"><label>🤖 روبیکا</label><input name="social_rubika" value="{{ $socialRubika }}" placeholder="https://rubika.ir/username"></div>
                            <div class="settings-field ltr"><label>💬 بله</label><input name="social_bale" value="{{ $socialBale }}" placeholder="https://ble.ir/username"></div>
                            <div class="settings-field ltr"><label>🌐 ایتا</label><input name="social_eitaa" value="{{ $socialEitaa }}" placeholder="https://eitaa.com/username"></div>
                            <div class="settings-field ltr"><label>🎬 آپارات</label><input name="social_aparat" value="{{ $socialAparat }}" placeholder="https://aparat.com/username"></div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#10b981;">
                        <div class="settings-block-header"><h4>معرفی سازمان به موتورهای جستجو (داده‌های ساختاریافته)</h4><small>افزایش شانس نمایش سایت شما به‌صورت غنی در نتایج گوگل (با لوگو، نظرات، امتیاز و ...)</small></div>
                        <label class="settings-check" style="margin-bottom:0.5rem;"><input type="checkbox" name="seo_schema_enabled" value="1" @checked($seoSchemaOn=='1')><span>فعال‌سازی داده‌های ساختاریافته در همه صفحه‌های سایت</span></label>
                        <div class="settings-form-grid">
                            <div class="settings-field">
                                <label>نوع کسب‌وکار شما</label>
                                <select name="seo_org_type">
                                    <option value="business"    @selected($seoOrgType==='business' || $seoOrgType==='Organization')>سازمان یا کسب‌وکار عمومی</option>
                                    <option value="local"       @selected($seoOrgType==='local' || $seoOrgType==='LocalBusiness')>کسب‌وکار محلی (مغازه، دفتر)</option>
                                    <option value="shop"        @selected($seoOrgType==='shop' || $seoOrgType==='OnlineStore')>فروشگاه اینترنتی</option>
                                    <option value="corporation" @selected($seoOrgType==='corporation' || $seoOrgType==='Corporation')>شرکت رسمی</option>
                                    <option value="school"      @selected($seoOrgType==='school' || $seoOrgType==='EducationalOrganization')>مؤسسه آموزشی</option>
                                </select>
                            </div>
                            <div class="settings-field col-span-2 ltr"><label>آدرس لوگوی رسمی سازمان</label><input name="seo_org_logo" value="{{ $seoOrgLogo }}" placeholder="/uploads/branding/logo.webp"></div>
                        </div>
                    </div>

                    <div class="settings-block is-accent" style="--block-color:#f59e0b;">
                        <div class="settings-block-header"><h4>تأیید مالکیت در موتورهای جستجو و ابزار آمار</h4><small>کدهایی که موتورهای جستجو برای تأیید مالکیت سایت می‌دهند</small></div>
                        <div class="settings-form-grid">
                            <div class="settings-field ltr col-span-3"><label>کد تأیید گوگل (Google Search Console)</label><input name="seo_google_verification" value="{{ $seoGoogleVerif }}" placeholder="مثلاً abcdEfGHi_JklMno..."><small>از پنل search.google.com/search-console دریافت کنید.</small></div>
                            <div class="settings-field ltr col-span-3"><label>کد تأیید بینگ (Bing Webmaster)</label><input name="seo_bing_verification" value="{{ $seoBingVerif }}" placeholder="کد ۳۲ کاراکتری از bing.com/webmasters"></div>
                            <div class="settings-field ltr col-span-3"><label>کد تأیید یاندکس (Yandex)</label><input name="seo_yandex_verification" value="{{ $seoYandexVerif }}" placeholder="کد از webmaster.yandex.com"></div>
                            <div class="settings-field ltr col-span-3"><label>شناسه ابزار تحلیل ترافیک گوگل (Analytics)</label><input name="seo_analytics_id" value="{{ $seoAnalytics }}" placeholder="مثلاً G-XXXXXXX"><small>شناسه‌ای که گوگل آنالیتیکس در قسمت مدیریت به شما می‌دهد.</small></div>
                        </div>
                    </div>

                    <div class="settings-form-footer">
                        <button type="submit" class="btn">💾 ذخیره تنظیمات سئو</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

{{-- ==================== پاپ‌آپ پیش‌نمایش کامل فاکتور ==================== --}}
<div class="finance-modal" id="fullPreviewModal">
    <div class="finance-modal-card" style="max-width: 240mm; width:96%;">
        <header>
            <div>
                <span class="finance-chip">پیش‌نمایش کامل فاکتور</span>
                <h2 id="fullPreviewTitle">پیش‌نمایش کامل ({{ $invPageSize }})</h2>
                <p>این پیش‌نمایش دقیقاً همان چیزی است که در چاپ و صفحه جزئیات فاکتور دیده می‌شود.</p>
            </div>
            <button type="button" onclick="closeFullPreview()" title="بستن">×</button>
        </header>
        <div style="padding:1.15rem; background:#e2e8f0; max-height:calc(100vh - 10rem); overflow-y:auto;">
            <div id="fullPreviewContainer" style="display:flex; justify-content:center;"></div>
        </div>
        <div class="finance-modal-actions">
            <button class="btn btn-ghost" type="button" onclick="closeFullPreview()">بستن</button>
            <a class="btn" href="{{ url('/app/tax-invoices') }}">مشاهده فاکتور واقعی</a>
        </div>
    </div>
</div>

{{-- ==================== پاپ‌آپ افزودن / ویرایش کاربر ==================== --}}
<div class="finance-modal" id="userModal">
    <div class="finance-modal-card">
        <header>
            <div>
                <span class="finance-chip" id="userModalChip">افزودن کاربر جدید</span>
                <h2 id="userModalTitle">افزودن کاربر جدید</h2>
                <p>اطلاعات کاربر و نقش را وارد کنید. رمز عبور فقط برای کاربر جدید یا هنگام تغییر رمز لازم است.</p>
            </div>
            <button type="button" onclick="closeUserModal()" title="بستن">×</button>
        </header>
        <form method="post" action="{{ url('/app/settings/users') }}" class="finance-modal-form" id="userForm">
            @csrf
            <input type="hidden" name="user_id" id="userId" value="">
            <div class="finance-modal-grid">
                <div><label>نام و نام خانوادگی</label><input name="name" id="userName" required placeholder="مثلاً علی رضایی"></div>
                <div><label>موبایل</label><input name="phone" id="userPhone" class="ltr" placeholder="09XXXXXXXXX"></div>
                <div><label>ایمیل</label><input name="email" id="userEmail" type="email" class="ltr" placeholder="user@example.com"></div>
                <div><label>رمز عبور</label><input name="password" id="userPassword" type="password" placeholder="حداقل ۶ کاراکتر"><small style="color:var(--mut);font-size:0.72rem;">در ویرایش، اگر خالی بگذارید تغییر نمی‌کند</small></div>
                <div>
                    <label>نقش</label>
                    <select name="role" id="userRole">
                        <option value="admin">مدیر سیستم</option>
                        <option value="manager">مدیر فروش</option>
                        <option value="support">پشتیبان</option>
                        <option value="reporter">گزارش‌گیر</option>
                        <option value="user" selected>کارمند</option>
                    </select>
                </div>
                <div><label>وضعیت</label><label class="settings-check" style="padding:0;"><input type="checkbox" name="active" id="userActive" value="1" checked><span>کاربر فعال باشد</span></label></div>
            </div>
            <div class="finance-modal-actions">
                <button class="btn btn-ghost" type="button" onclick="closeUserModal()">انصراف</button>
                <button class="btn" type="submit">💾 ذخیره کاربر</button>
            </div>
        </form>
    </div>
</div>

<script>
    var ACTIVE_KEY = 'ayarpro_settings_tab';

    function switchTab(name) {
        document.querySelectorAll('.settings-tab-btn').forEach(function(b){
            b.classList.toggle('is-active', b.dataset.tab === name);
        });
        document.querySelectorAll('.settings-panel').forEach(function(p){
            p.classList.toggle('is-active', p.dataset.panel === name);
        });
        try { localStorage.setItem(ACTIVE_KEY, name); } catch(e){}
        try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch(e) { window.scrollTo(0,0); }
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            history.replaceState({}, '', url.toString());
        } catch(e) {}
    }
    function initTabs() {
        document.querySelectorAll('.settings-tab-btn').forEach(function(btn){
            btn.addEventListener('click', function(){ switchTab(btn.dataset.tab); });
        });
        var urlTab = new URLSearchParams(window.location.search).get('tab');
        var savedTab; try { savedTab = localStorage.getItem(ACTIVE_KEY); } catch(e){ savedTab = null; }
        var initial = urlTab || savedTab || '{{ $active }}' || 'business';
        if (!document.querySelector('.settings-tab-btn[data-tab="' + initial + '"]')) initial = 'business';
        switchTab(initial);
    }

    function pickBrandColor(color) {
        var input = document.getElementById('brandingColor');
        if (input) input.value = color;
        document.querySelectorAll('.color-swatch').forEach(function(b){
            b.classList.toggle('is-active', b.dataset.color && b.dataset.color.toLowerCase() === color.toLowerCase());
        });
        updatePreview();
    }
    function initColorSwatches() {
        var input = document.getElementById('brandingColor');
        if (!input) return;
        pickBrandColor(input.value);
        input.addEventListener('input', function(){ pickBrandColor(input.value); });
    }

    function switchSmsProvider(prov) {
        document.querySelectorAll('.sms-cred').forEach(function(el){
            el.style.display = (el.dataset.prov === prov) ? 'block' : 'none';
        });
    }
    function initSmsProvider() {
        var sel = document.getElementById('smsProviderSelect');
        if (sel) switchSmsProvider(sel.value);
    }

    function previewLogo(input) {
        if (!input.files || !input.files[0]) return;
        var reader = new FileReader();
        reader.onload = function(e){
            var box = document.querySelector('.logo-preview');
            if (box) box.innerHTML = '<img src="' + e.target.result + '" alt="پیش‌نمایش">';
            var miniLogo = document.getElementById('miniLogo');
            if (miniLogo) miniLogo.innerHTML = '<img src="' + e.target.result + '" alt="لوگو">';
        };
        reader.readAsDataURL(input.files[0]);
    }

    function previewDesignLogo(input){
        if (!input.files || !input.files[0]) return;
        var reader = new FileReader();
        reader.onload = function(e){
            // update all logo previews in design tab
            document.querySelectorAll('[data-panel="design"] .logo-preview').forEach(function(box){
                box.innerHTML = '<img src="' + e.target.result + '" alt="پیش‌نمایش لوگو">';
            });
            // update live brand preview
            var pb = document.querySelector('#previewBrandName');
            var iconEl = document.querySelector('#previewBrandIcon');
            if(pb){
                // keep name, just ensure image preview container shows?
                var parent = pb.closest('div').previousElementSibling;
                if(parent && parent.querySelector('img')){
                    parent.querySelector('img').src = e.target.result;
                }
            }
        };
        reader.readAsDataURL(input.files[0]);
    }

    function previewDesignFavicon(input){
        if (!input.files || !input.files[0]) return;
        var reader = new FileReader();
        reader.onload = function(e){
            document.querySelectorAll('[data-panel="design"] .logo-preview').forEach(function(box, idx){
                // second preview is favicon, first is logo - we update second occurrence
                if(idx===1 || box.innerHTML.includes('🌐')){
                    box.innerHTML = '<img src="' + e.target.result + '" alt="پیش‌نمایش فاوآیکن">';
                }
            });
        };
        reader.readAsDataURL(input.files[0]);
    }

    function bindDesignLivePreview(){
        var nameInput = document.querySelector('[name="site_brand_name"]');
        var taglineInput = document.querySelector('[name="site_brand_tagline"]');
        var iconSelect = document.querySelector('[name="site_icon"]');
        var previewName = document.getElementById('previewBrandName');
        var previewTagline = document.getElementById('previewBrandTagline');
        var previewIcon = document.getElementById('previewBrandIcon');
        if(nameInput && previewName){
            nameInput.addEventListener('input', function(){ previewName.textContent = this.value || 'مشتری‌یار'; });
        }
        if(taglineInput && previewTagline){
            taglineInput.addEventListener('input', function(){ previewTagline.textContent = this.value || 'مدیریت هوشمند مشتریان'; });
        }
        if(iconSelect && previewIcon){
            iconSelect.addEventListener('change', function(){ previewIcon.textContent = this.value; });
        }
    }

    document.addEventListener('DOMContentLoaded', function(){
        bindDesignLivePreview();
    });

    /* ==================== پیش‌نمایش زنده تب branding ==================== */
    function bindLivePreview() {
        var form = document.getElementById('brandingForm');
        if (!form) return;
        form.querySelectorAll('[data-preview]').forEach(function(el){
            var evt = (el.type === 'checkbox' || el.type === 'radio' || el.tagName === 'SELECT') ? 'change' : 'input';
            el.addEventListener(evt, updatePreview);
            el.addEventListener('input', updatePreview);
        });
        updatePreview();
    }

    function updatePreview() {
        var mini = document.getElementById('miniInvoice');
        if (!mini) return;

        var get = function(name){ var e = document.querySelector('#brandingForm [name="' + name + '"]'); return e ? e.value : ''; };
        var checked = function(name){
            var e = document.querySelector('#brandingForm [name="' + name + '"]');
            if (!e) return false;
            if (e.type === 'checkbox') return e.checked;
            return e.value === '1' || e.value === 'true';
        };
        var radio = function(name){
            var e = document.querySelector('#brandingForm [name="' + name + '"]:checked');
            return e ? e.value : '';
        };

        mini.style.setProperty('--mini-primary',       get('invoice_primary') || '#0f172a');
        mini.style.setProperty('--mini-accent',        get('invoice_accent')  || '#10b981');
        mini.style.setProperty('--mini-border-style',  get('invoice_border_style') || 'solid');
        mini.style.setProperty('--mini-border-width', (get('invoice_border_width') || '2') + 'px');
        mini.style.setProperty('--mini-font',          get('invoice_font_family') || 'inherit');
        mini.style.setProperty('--mini-margin',       (get('invoice_margin') || '12') + 'mm');

        mini.className = 'mini-invoice';
        mini.classList.add('theme-'  + (radio('invoice_theme') || 'classic'));
        mini.classList.add('page-'   + (get('invoice_page_size') || 'A4'));
        mini.classList.add('orient-' + (get('invoice_page_orient') || 'portrait'));
        mini.classList.add('font-'   + (get('invoice_font_size') || 'medium'));
        mini.classList.add('header-' + (get('invoice_header_bg') || 'primary'));
        mini.classList.add('table-'  + (get('invoice_table_style') || 'bordered'));

        var toggle = function(id, show){
            var el = document.getElementById(id);
            if (el) el.style.display = show ? '' : 'none';
        };
        toggle('miniLogo',      checked('invoice_show_logo'));
        toggle('miniSig',       checked('invoice_show_sig'));
        toggle('miniStamp',     checked('invoice_show_stamp'));
        toggle('miniWatermark', checked('invoice_show_watermark'));
        toggle('miniBarcode',   checked('invoice_show_barcode'));
        toggle('miniQR',        checked('invoice_show_qr'));
        toggle('miniNotes',     checked('invoice_show_notes'));
        toggle('miniWord',      checked('invoice_show_total_word'));

        var head = document.getElementById('miniHead');
        if (head) {
            head.classList.remove('logo-right','logo-center','logo-left');
            head.classList.add('logo-' + (get('invoice_logo_position') || 'right'));
        }

        var setText = function(id, val){ var e = document.getElementById(id); if (e) e.textContent = val || ''; };
        setText('miniLogoText',   get('invoice_logo_text'));
        setText('miniSellerSig',  get('invoice_seller_sig'));
        setText('miniTerms',      get('invoice_terms_text'));
        setText('miniFoot',       get('invoice_footer_text'));

        var stamp = document.getElementById('miniStamp');
        if (stamp) {
            var stampText = get('invoice_stamp_text');
            var stampColor= get('invoice_stamp_color');
            stamp.textContent = stampText;
            if (stampColor) { stamp.style.color = stampColor; stamp.style.borderColor = stampColor; }
        }

        var wm = document.getElementById('miniWatermark');
        if (wm) wm.textContent = get('invoice_watermark_text');
    }

    /* ==================== پاپ‌آپ پیش‌نمایش کامل ==================== */
    function openFullPreview() {
        var modal = document.getElementById('fullPreviewModal');
        if (!modal) return;
        var mini = document.getElementById('miniInvoice');
        if (!mini) return;

        var container = document.getElementById('fullPreviewContainer');
        var clone = mini.cloneNode(true);
        clone.id = 'fullInvoicePreview';
        clone.classList.add('is-full-preview');
        container.innerHTML = '';
        container.appendChild(clone);

        var pageSize = document.querySelector('#brandingForm [name="invoice_page_size"]')?.value || 'A4';
        var title = document.getElementById('fullPreviewTitle');
        if (title) title.textContent = 'پیش‌نمایش کامل (' + pageSize + ')';

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeFullPreview() {
        var modal = document.getElementById('fullPreviewModal');
        if (modal) modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    function openUserModal(id, data) {
        var modal = document.getElementById('userModal');
        var form  = document.getElementById('userForm');
        if (!modal || !form) return;
        if (id && data) {
            document.getElementById('userModalChip').textContent  = 'ویرایش کاربر #' + id;
            document.getElementById('userModalTitle').textContent = 'ویرایش کاربر';
            document.getElementById('userId').value       = id;
            document.getElementById('userName').value     = data.name  || '';
            document.getElementById('userPhone').value    = data.phone || '';
            document.getElementById('userEmail').value    = data.email || '';
            document.getElementById('userRole').value     = data.role  || 'user';
            document.getElementById('userActive').checked = data.active ? true : false;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            form.action = '{{ url('/app/settings/users') }}/' + id;
        } else {
            document.getElementById('userModalChip').textContent  = 'افزودن کاربر جدید';
            document.getElementById('userModalTitle').textContent = 'افزودن کاربر جدید';
            document.getElementById('userId').value       = '';
            document.getElementById('userName').value     = '';
            document.getElementById('userPhone').value    = '';
            document.getElementById('userEmail').value    = '';
            document.getElementById('userRole').value     = 'user';
            document.getElementById('userActive').checked = true;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = true;
            form.action = '{{ url('/app/settings/users') }}';
        }
        modal.classList.add('show');
    }
    function closeUserModal() { document.getElementById('userModal')?.classList.remove('show'); }

    function saveActivePanel() {
        var panel = document.querySelector('.settings-panel.is-active');
        if (!panel) return;
        var form = panel.querySelector('form');
        if (form) form.submit();
        else alert('این تب فرم قابل ذخیره ندارد.');
    }

    window.addEventListener('click', function (event) {
        if (event.target && event.target.classList && event.target.classList.contains('finance-modal')) {
            event.target.classList.remove('show');
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeUserModal(); closeFullPreview(); }
    });

    document.addEventListener('DOMContentLoaded', function(){
        initTabs();
        initColorSwatches();
        initSmsProvider();
        bindLivePreview();
    });
</script>
@endsection