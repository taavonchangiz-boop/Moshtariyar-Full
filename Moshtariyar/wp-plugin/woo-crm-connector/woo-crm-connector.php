<?php
/**
 * Plugin Name: MoshtariYar Connector (مشتری‌یار)
 * Description: اتصال پایدار ووکامرس به مشتری‌یار، ارسال سفارش و مشتری و کالا، خروجی فایل داده، ورود کوپن و کیف پول باشگاه مشتریان.
 * Version: 1.0.0
 * Author: HoomanWeb
 * Requires Plugins: woocommerce
 */

if (! defined('ABSPATH')) {
    exit;
}

class Woo_CRM_Connector
{
    const VERSION = '1.0.0';

    const OPT_URL = 'wcrm_webhook_url';
    const OPT_SECRET = 'wcrm_webhook_secret';
    const OPT_CLUB_URL = 'wcrm_club_url';
    const OPT_CRM_IP = 'wcrm_crm_ip';
    const OPT_SEND_ORDERS = 'wcrm_send_orders';
    const OPT_SEND_CUSTOMERS = 'wcrm_send_customers';
    const OPT_SEND_PRODUCTS = 'wcrm_send_products';
    const OPT_SEND_CART = 'wcrm_send_cart';
    const OPT_BLOCKING = 'wcrm_blocking_send';
    const OPT_LOGS = 'wcrm_local_logs';
    const OPT_LAST_OK = 'wcrm_last_ok_at';
    const OPT_LAST_ERROR = 'wcrm_last_error_at';

    const SESSION_USE = 'wcrm_use_wallet';
    const SESSION_AMOUNT = 'wcrm_wallet_amount';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_wcrm_test_connection', [$this, 'test_connection_action']);
        add_action('admin_post_wcrm_export_data', [$this, 'export_data_action']);
        add_action('admin_post_wcrm_import_coupons', [$this, 'import_coupons_action']);
        add_action('admin_post_wcrm_retry_failed', [$this, 'retry_failed_action']);
        add_action('admin_post_wcrm_clear_logs', [$this, 'clear_logs_action']);

        add_action('woocommerce_new_order', [$this, 'on_new_order'], 20, 1);
        add_action('woocommerce_order_status_changed', [$this, 'on_order_status_changed'], 20, 4);
        add_action('woocommerce_created_customer', [$this, 'on_customer_changed'], 20, 1);
        add_action('woocommerce_update_customer', [$this, 'on_customer_changed'], 20, 1);
        add_action('woocommerce_new_product', [$this, 'on_product_changed'], 20, 1);
        add_action('woocommerce_update_product', [$this, 'on_product_changed'], 20, 1);
        add_action('woocommerce_product_set_stock', [$this, 'on_product_stock_changed'], 20, 1);
        add_action('woocommerce_add_to_cart', [$this, 'on_cart_updated']);

        add_action('woocommerce_review_order_before_payment', [$this, 'wallet_checkout_box']);
        add_action('woocommerce_checkout_update_order_review', [$this, 'capture_wallet_choice']);
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_wallet_fee']);
        add_action('woocommerce_checkout_create_order', [$this, 'save_wallet_meta'], 20, 2);
        add_action('woocommerce_order_status_processing', [$this, 'debit_wallet_for_order']);
        add_action('woocommerce_order_status_completed', [$this, 'debit_wallet_for_order']);
        add_action('woocommerce_order_status_cancelled', [$this, 'refund_wallet_for_order']);
        add_action('woocommerce_order_status_refunded', [$this, 'refund_wallet_for_order']);
        add_action('woocommerce_order_status_failed', [$this, 'refund_wallet_for_order']);
        add_action('woocommerce_account_dashboard', [$this, 'my_account_wallet']);

        add_shortcode('moshtariyar_club_link', [$this, 'shortcode_club_link']);
        add_shortcode('hamdam_club_link', [$this, 'shortcode_club_link']);
    }

    public function admin_menu()
    {
        add_options_page('مشتری‌یار', 'مشتری‌یار', 'manage_options', 'woo-crm-connector', [$this, 'settings_page']);
    }

    public function register_settings()
    {
        register_setting('wcrm_group', self::OPT_URL, ['sanitize_callback' => [$this, 'sanitize_url_option']]);
        register_setting('wcrm_group', self::OPT_SECRET, ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('wcrm_group', self::OPT_CLUB_URL, ['sanitize_callback' => [$this, 'sanitize_url_option']]);
        register_setting('wcrm_group', self::OPT_CRM_IP, ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('wcrm_group', self::OPT_SEND_ORDERS, ['sanitize_callback' => [$this, 'sanitize_bool_option']]);
        register_setting('wcrm_group', self::OPT_SEND_CUSTOMERS, ['sanitize_callback' => [$this, 'sanitize_bool_option']]);
        register_setting('wcrm_group', self::OPT_SEND_PRODUCTS, ['sanitize_callback' => [$this, 'sanitize_bool_option']]);
        register_setting('wcrm_group', self::OPT_SEND_CART, ['sanitize_callback' => [$this, 'sanitize_bool_option']]);
        register_setting('wcrm_group', self::OPT_BLOCKING, ['sanitize_callback' => [$this, 'sanitize_bool_option']]);
    }

    public function sanitize_url_option($value)
    {
        return $this->clean_url($value);
    }

    public function sanitize_bool_option($value)
    {
        return $value ? '1' : '0';
    }

    private function opt_enabled($key, $default = true)
    {
        $value = get_option($key, $default ? '1' : '0');
        return (string) $value === '1';
    }

    public function settings_page()
    {
        $webhookUrl = $this->clean_url(get_option(self::OPT_URL));
        $secret = (string) get_option(self::OPT_SECRET);
        $clubUrl = $this->clean_url(get_option(self::OPT_CLUB_URL));
        $crmIp = (string) get_option(self::OPT_CRM_IP);
        $isReady = $webhookUrl && $secret;
        $logs = $this->logs();
        $failedCount = 0;
        foreach ($logs as $log) {
            if (($log['status'] ?? '') === 'failed') {
                $failedCount++;
            }
        }
        ?>
        <div class="wrap wcrm-wrap" dir="rtl">
            <style>
                .wcrm-wrap{font-family:tahoma,Arial,sans-serif;max-width:1180px}.wcrm-hero{background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;border-radius:18px;padding:24px;margin:18px 0;box-shadow:0 12px 35px rgba(15,23,42,.18)}.wcrm-hero h1{color:#fff;margin:0 0 8px;font-size:26px}.wcrm-hero p{margin:0;color:#cbd5e1;line-height:1.9}.wcrm-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}.wcrm-stat{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px;box-shadow:0 4px 12px rgba(15,23,42,.06)}.wcrm-stat span{display:block;color:#64748b;font-size:12px;font-weight:700}.wcrm-stat b{display:block;margin-top:6px;color:#0f172a;font-size:22px}.wcrm-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;margin:14px 0;box-shadow:0 4px 12px rgba(15,23,42,.06)}.wcrm-card h2{margin:0 0 12px;font-size:18px;color:#0f172a}.wcrm-card p{color:#475569;line-height:1.9}.wcrm-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.wcrm-field label{display:block;font-weight:800;margin-bottom:6px;color:#0f172a}.wcrm-field input[type=text],.wcrm-field input[type=url],.wcrm-field input[type=number]{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;direction:ltr;text-align:left}.wcrm-field .description{color:#64748b;font-size:12px;margin-top:6px}.wcrm-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px}.wcrm-check{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px;font-weight:700}.wcrm-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}.wcrm-badge{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:900}.wcrm-ok{background:#dcfce7;color:#166534}.wcrm-bad{background:#fee2e2;color:#991b1b}.wcrm-warn{background:#fef3c7;color:#92400e}.wcrm-log{border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin:8px 0;background:#f8fafc}.wcrm-log-top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}.wcrm-log pre{background:#0f172a;color:#e2e8f0;border-radius:10px;padding:10px;overflow:auto;direction:ltr;text-align:left;max-height:220px}.wcrm-code{display:block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:10px;direction:ltr;text-align:left;overflow-wrap:anywhere}@media(max-width:900px){.wcrm-grid,.wcrm-form-grid,.wcrm-checks{grid-template-columns:1fr}.wcrm-wrap{max-width:100%}}
            </style>

            <div class="wcrm-hero">
                <h1>اتصال فروشگاه به مشتری‌یار</h1>
                <p>این افزونه پل ارتباطی فروشگاه ووکامرس با مشتری‌یار است. سفارش‌ها، مشتریان، کالاها، کوپن‌ها و کیف پول باشگاه مشتریان از همینجا مدیریت می‌شوند.</p>
            </div>

            <?php if (isset($_GET['wcrm_msg'])) : ?>
                <div class="notice notice-<?php echo esc_attr($_GET['wcrm_notice'] ?? 'info'); ?> is-dismissible"><p><?php echo esc_html(wp_unslash($_GET['wcrm_msg'])); ?></p></div>
            <?php endif; ?>

            <div class="wcrm-grid">
                <div class="wcrm-stat"><span>وضعیت تنظیمات</span><b><?php echo $isReady ? 'آماده' : 'ناقص'; ?></b></div>
                <div class="wcrm-stat"><span>آخرین ارتباط موفق</span><b><?php echo esc_html(get_option(self::OPT_LAST_OK, 'ثبت نشده')); ?></b></div>
                <div class="wcrm-stat"><span>آخرین خطا</span><b><?php echo esc_html(get_option(self::OPT_LAST_ERROR, 'ندارد')); ?></b></div>
                <div class="wcrm-stat"><span>خطاهای محلی</span><b><?php echo esc_html($failedCount); ?></b></div>
            </div>

            <div class="wcrm-card">
                <h2>تنظیمات اتصال</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('wcrm_group'); ?>
                    <div class="wcrm-form-grid">
                        <div class="wcrm-field">
                            <label>نشانی اتصال مشتری‌یار</label>
                            <input type="url" name="<?php echo esc_attr(self::OPT_URL); ?>" value="<?php echo esc_attr($webhookUrl); ?>" placeholder="https://example.ir/api/v1/woobridge/webhook/1">
                            <div class="description">این نشانی از صفحه اتصال فروشگاه در مشتری‌یار برداشته می‌شود.</div>
                        </div>
                        <div class="wcrm-field">
                            <label>کلید امنیتی</label>
                            <input type="text" name="<?php echo esc_attr(self::OPT_SECRET); ?>" value="<?php echo esc_attr($secret); ?>" placeholder="کلید امنیتی اتصال">
                            <div class="description">این کلید برای امضای امن درخواست‌ها استفاده می‌شود.</div>
                        </div>
                        <div class="wcrm-field">
                            <label>لینک باشگاه مشتریان</label>
                            <input type="url" name="<?php echo esc_attr(self::OPT_CLUB_URL); ?>" value="<?php echo esc_attr($clubUrl); ?>" placeholder="https://example.ir/club/login">
                            <div class="description">در حساب کاربری مشتری و کد کوتاه باشگاه استفاده می‌شود.</div>
                        </div>
                        <div class="wcrm-field">
                            <label>آی‌پی مستقیم مشتری‌یار، اختیاری</label>
                            <input type="text" name="<?php echo esc_attr(self::OPT_CRM_IP); ?>" value="<?php echo esc_attr($crmIp); ?>" placeholder="185.00.00.00">
                            <div class="description">اگر هاست فروشگاه دامنه مشتری‌یار را درست پیدا نمی‌کند، آی‌پی را وارد کنید.</div>
                        </div>
                    </div>

                    <div class="wcrm-checks">
                        <label class="wcrm-check"><input type="hidden" name="<?php echo esc_attr(self::OPT_SEND_ORDERS); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr(self::OPT_SEND_ORDERS); ?>" value="1" <?php checked($this->opt_enabled(self::OPT_SEND_ORDERS, true)); ?>> ارسال سفارش‌ها</label>
                        <label class="wcrm-check"><input type="hidden" name="<?php echo esc_attr(self::OPT_SEND_CUSTOMERS); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr(self::OPT_SEND_CUSTOMERS); ?>" value="1" <?php checked($this->opt_enabled(self::OPT_SEND_CUSTOMERS, true)); ?>> ارسال مشتری‌ها</label>
                        <label class="wcrm-check"><input type="hidden" name="<?php echo esc_attr(self::OPT_SEND_PRODUCTS); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr(self::OPT_SEND_PRODUCTS); ?>" value="1" <?php checked($this->opt_enabled(self::OPT_SEND_PRODUCTS, true)); ?>> ارسال کالاها و موجودی</label>
                        <label class="wcrm-check"><input type="hidden" name="<?php echo esc_attr(self::OPT_SEND_CART); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr(self::OPT_SEND_CART); ?>" value="1" <?php checked($this->opt_enabled(self::OPT_SEND_CART, false)); ?>> ثبت سبد رهاشده در گزارش محلی</label>
                        <label class="wcrm-check"><input type="hidden" name="<?php echo esc_attr(self::OPT_BLOCKING); ?>" value="0"><input type="checkbox" name="<?php echo esc_attr(self::OPT_BLOCKING); ?>" value="1" <?php checked($this->opt_enabled(self::OPT_BLOCKING, false)); ?>> ارسال همزمان و دریافت پاسخ</label>
                    </div>
                    <?php submit_button('ذخیره تنظیمات'); ?>
                </form>
            </div>

            <div class="wcrm-card">
                <h2>آزمون اتصال و ابزارهای اضطراری</h2>
                <div class="wcrm-actions">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('wcrm_test_connection'); ?>
                        <input type="hidden" name="action" value="wcrm_test_connection">
                        <?php submit_button('آزمون اتصال', 'secondary', 'submit', false); ?>
                    </form>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('wcrm_retry_failed'); ?>
                        <input type="hidden" name="action" value="wcrm_retry_failed">
                        <?php submit_button('ارسال دوباره خطاها', 'secondary', 'submit', false); ?>
                    </form>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('گزارش‌های محلی پاک شوند؟')">
                        <?php wp_nonce_field('wcrm_clear_logs'); ?>
                        <input type="hidden" name="action" value="wcrm_clear_logs">
                        <?php submit_button('پاک کردن گزارش محلی', 'delete', 'submit', false); ?>
                    </form>
                </div>
            </div>

            <div class="wcrm-card">
                <h2>خروجی فایل برای مشتری‌یار</h2>
                <p>اگر ارتباط مستقیم بین دو هاست بسته است، از این بخش خروجی بگیرید و در مشتری‌یار از گزینه ورود فایل داده استفاده کنید.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('wcrm_export_data'); ?>
                    <input type="hidden" name="action" value="wcrm_export_data">
                    <div class="wcrm-form-grid">
                        <div class="wcrm-field"><label>تعداد سفارش آخر</label><input type="number" name="limit" value="200" min="1" max="1000"></div>
                        <div class="wcrm-field"><label>تعداد محصول آخر</label><input type="number" name="product_limit" value="500" min="1" max="2000"></div>
                    </div>
                    <?php submit_button('دانلود فایل داده', 'primary'); ?>
                </form>
            </div>

            <div class="wcrm-card">
                <h2>ورود کوپن‌های مشتری‌یار به فروشگاه</h2>
                <p>از مشتری‌یار خروجی کوپن‌ها را دریافت کنید و اینجا وارد کنید تا کوپن‌ها در ووکامرس ساخته یا به‌روزرسانی شوند.</p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('wcrm_import_coupons'); ?>
                    <input type="hidden" name="action" value="wcrm_import_coupons">
                    <input type="file" name="coupon_file" accept=".json,application/json" required>
                    <?php submit_button('ورود کوپن‌ها', 'secondary'); ?>
                </form>
            </div>

            <div class="wcrm-card">
                <h2>گزارش محلی افزونه</h2>
                <?php if (empty($logs)) : ?>
                    <p>هنوز گزارشی ثبت نشده است.</p>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <div class="wcrm-log">
                            <div class="wcrm-log-top">
                                <div>
                                    <span class="wcrm-badge <?php echo ($log['status'] ?? '') === 'success' ? 'wcrm-ok' : (($log['status'] ?? '') === 'failed' ? 'wcrm-bad' : 'wcrm-warn'); ?>"><?php echo esc_html($log['status_label'] ?? 'نامشخص'); ?></span>
                                    <b><?php echo esc_html($log['title'] ?? 'گزارش'); ?></b>
                                </div>
                                <div><?php echo esc_html($log['time'] ?? ''); ?></div>
                            </div>
                            <p><?php echo esc_html($log['message'] ?? ''); ?></p>
                            <?php if (! empty($log['debug'])) : ?><pre><?php echo esc_html(wp_json_encode($log['debug'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function test_connection_action()
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('wcrm_test_connection')) {
            wp_die('دسترسی غیرمجاز');
        }

        $testUrl = $this->test_url();
        if (! $testUrl || ! get_option(self::OPT_SECRET)) {
            $this->redirect_with_message('error', 'نشانی اتصال یا کلید امنیتی تنظیم نشده است.');
        }

        $ping = $this->remote_get($testUrl, [
            'timeout' => 30,
            'sslverify' => false,
            'redirection' => 3,
            'headers' => ['User-Agent' => 'MoshtariYarConnector/' . self::VERSION],
        ]);

        if (is_wp_error($ping)) {
            $message = 'اتصال شبکه ناموفق بود: ' . $ping->get_error_message();
            $this->add_log('آزمون اتصال', 'failed', $message, ['url' => $testUrl]);
            update_option(self::OPT_LAST_ERROR, current_time('mysql'));
            $this->redirect_with_message('error', $message);
        }

        $payload = ['test' => true, 'site' => home_url(), 'time' => current_time('mysql'), 'plugin_version' => self::VERSION];
        $result = $this->post_to_crm('connection.test', $payload, true, $testUrl);

        if (is_array($result) && ! empty($result['ok'])) {
            update_option(self::OPT_LAST_OK, current_time('mysql'));
            $this->add_log('آزمون اتصال', 'success', 'اتصال با موفقیت انجام شد.', ['http' => $result['_http_code'] ?? null]);
            $this->redirect_with_message('success', 'اتصال با موفقیت انجام شد.');
        }

        $message = 'اتصال انجام نشد. پاسخ دریافتی معتبر نبود.';
        if (is_array($result) && ! empty($result['_raw'])) {
            $message .= ' پاسخ: ' . mb_substr((string) $result['_raw'], 0, 250);
        }
        update_option(self::OPT_LAST_ERROR, current_time('mysql'));
        $this->add_log('آزمون اتصال', 'failed', $message, $result ?: []);
        $this->redirect_with_message('error', $message);
    }

    public function export_data_action()
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('wcrm_export_data')) {
            wp_die('دسترسی غیرمجاز');
        }
        if (! function_exists('wc_get_orders') || ! function_exists('wc_get_products')) {
            wp_die('ووکامرس فعال نیست.');
        }

        $limit = max(1, min(1000, intval($_POST['limit'] ?? 200)));
        $productLimit = max(1, min(2000, intval($_POST['product_limit'] ?? 500)));
        $orders = wc_get_orders(['limit' => $limit, 'orderby' => 'ID', 'order' => 'DESC', 'return' => 'objects']);
        $products = wc_get_products(['limit' => $productLimit, 'orderby' => 'ID', 'order' => 'DESC', 'return' => 'objects']);

        $output = ['exported_at' => current_time('mysql'), 'site' => home_url(), 'orders' => [], 'products' => []];
        foreach ($orders as $order) {
            $output['orders'][] = $this->order_payload($order);
        }
        foreach ($products as $product) {
            $output['products'][] = $this->product_payload($product);
        }

        $filename = 'moshtariyar-store-data-' . date('Ymd-His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo wp_json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function import_coupons_action()
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('wcrm_import_coupons')) {
            wp_die('دسترسی غیرمجاز');
        }
        if (empty($_FILES['coupon_file']['tmp_name'])) {
            wp_die('فایل ارسال نشده است.');
        }
        if (! class_exists('WC_Coupon')) {
            wp_die('ووکامرس فعال نیست.');
        }

        $raw = file_get_contents($_FILES['coupon_file']['tmp_name']);
        $json = json_decode($raw, true);
        if (! is_array($json)) {
            wp_die('فایل داده معتبر نیست.');
        }

        $coupons = $json['coupons'] ?? $json;
        $count = 0;
        foreach ($coupons as $couponData) {
            if (empty($couponData['code'])) {
                continue;
            }
            $code = wc_format_coupon_code($couponData['code']);
            $couponId = wc_get_coupon_id_by_code($code);
            $coupon = $couponId ? new WC_Coupon($couponId) : new WC_Coupon();
            $coupon->set_code($code);
            $coupon->set_description($couponData['title'] ?? 'کوپن مشتری‌یار');
            $coupon->set_discount_type(($couponData['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed_cart');
            $coupon->set_amount((float) ($couponData['discount_value'] ?? 0));
            if (! empty($couponData['min_order_total'])) {
                $coupon->set_minimum_amount((float) $couponData['min_order_total']);
            }
            if (! empty($couponData['expires_at'])) {
                $coupon->set_date_expires($couponData['expires_at']);
            }
            $coupon->set_usage_limit((int) ($couponData['usage_limit'] ?? 1));
            if (! empty($couponData['email'])) {
                $coupon->set_email_restrictions([$couponData['email']]);
            }
            $coupon->save();
            $count++;
        }

        $this->add_log('ورود کوپن', 'success', $count . ' کوپن وارد یا به‌روزرسانی شد.', []);
        $this->redirect_with_message('success', $count . ' کوپن وارد یا به‌روزرسانی شد.');
    }

    public function retry_failed_action()
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('wcrm_retry_failed')) {
            wp_die('دسترسی غیرمجاز');
        }

        $logs = $this->logs();
        $sent = 0;
        $failed = 0;
        foreach ($logs as $log) {
            if (($log['status'] ?? '') !== 'failed' || empty($log['topic']) || empty($log['payload']) || ! is_array($log['payload'])) {
                continue;
            }
            $result = $this->post_to_crm($log['topic'], $log['payload'], true);
            if (is_array($result) && ! empty($result['ok'])) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->add_log('ارسال دوباره', $failed ? 'failed' : 'success', $sent . ' مورد ارسال شد و ' . $failed . ' مورد ناموفق ماند.', []);
        $this->redirect_with_message($failed ? 'warning' : 'success', $sent . ' مورد ارسال شد و ' . $failed . ' مورد ناموفق ماند.');
    }

    public function clear_logs_action()
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('wcrm_clear_logs')) {
            wp_die('دسترسی غیرمجاز');
        }
        update_option(self::OPT_LOGS, []);
        $this->redirect_with_message('success', 'گزارش محلی افزونه پاک شد.');
    }

    public function on_new_order($orderId)
    {
        if (! $this->opt_enabled(self::OPT_SEND_ORDERS, true)) {
            return;
        }
        $order = wc_get_order($orderId);
        if ($order) {
            $this->send_order($order, 'order.created');
        }
    }

    public function on_order_status_changed($orderId, $oldStatus = '', $newStatus = '', $order = null)
    {
        if (! $this->opt_enabled(self::OPT_SEND_ORDERS, true)) {
            return;
        }
        $order = $order instanceof WC_Order ? $order : wc_get_order($orderId);
        if ($order) {
            $this->send_order($order, 'order.updated');
        }
    }

    public function on_customer_changed($customerId)
    {
        if (! $this->opt_enabled(self::OPT_SEND_CUSTOMERS, true)) {
            return;
        }
        if (! $customerId) {
            return;
        }
        $customer = new WC_Customer($customerId);
        if ($customer && $customer->get_id()) {
            $this->send_payload('customer.updated', $this->customer_payload_from_customer($customer), 'ارسال مشتری');
        }
    }

    public function on_product_changed($productId)
    {
        if (! $this->opt_enabled(self::OPT_SEND_PRODUCTS, true)) {
            return;
        }
        $product = wc_get_product($productId);
        if ($product) {
            $this->send_payload('product.updated', $this->product_payload($product), 'ارسال کالا');
        }
    }

    public function on_product_stock_changed($product)
    {
        if (! $this->opt_enabled(self::OPT_SEND_PRODUCTS, true)) {
            return;
        }
        if ($product instanceof WC_Product) {
            $this->send_payload('product.updated', $this->product_payload($product), 'ارسال موجودی کالا');
        }
    }

    public function on_cart_updated()
    {
        if (! $this->opt_enabled(self::OPT_SEND_CART, false)) {
            return;
        }
        if (! function_exists('WC') || ! WC()->cart) {
            return;
        }

        $key = 'wcrm_cart_log_' . md5((string) WC()->session->get_customer_id());
        if (get_transient($key)) {
            return;
        }
        set_transient($key, '1', 5 * MINUTE_IN_SECONDS);

        $customer = WC()->customer;
        $payload = [
            'email' => $customer ? $customer->get_billing_email() : null,
            'phone' => $customer ? $customer->get_billing_phone() : null,
            'value' => WC()->cart->get_cart_contents_total(),
            'items_count' => WC()->cart->get_cart_contents_count(),
            'site' => home_url(),
            'time' => current_time('mysql'),
        ];
        $this->add_log('سبد رهاشده', 'pending', 'سبد خرید برای بررسی محلی ثبت شد.', $payload);
    }

    private function send_order($order, $topic)
    {
        $payload = $this->order_payload($order);
        $hash = md5(wp_json_encode($payload));
        $metaKey = '_wcrm_last_sent_' . sanitize_key($topic);
        if ($order->get_meta($metaKey) === $hash) {
            return;
        }
        $result = $this->send_payload($topic, $payload, 'ارسال سفارش ' . $order->get_order_number());
        if ($result) {
            $order->update_meta_data($metaKey, $hash);
            $order->save();
        }
    }

    private function send_payload($topic, array $payload, $title)
    {
        $blocking = $this->opt_enabled(self::OPT_BLOCKING, false);
        $result = $this->post_to_crm($topic, $payload, $blocking);
        if ($blocking) {
            if (is_array($result) && ! empty($result['ok'])) {
                update_option(self::OPT_LAST_OK, current_time('mysql'));
                $this->add_log($title, 'success', 'ارسال با موفقیت انجام شد.', ['topic' => $topic, 'http' => $result['_http_code'] ?? null]);
                return true;
            }
            update_option(self::OPT_LAST_ERROR, current_time('mysql'));
            $this->add_log($title, 'failed', 'ارسال ناموفق بود.', ['topic' => $topic, 'response' => $result], $topic, $payload);
            return false;
        }

        $this->add_log($title, 'pending', 'ارسال غیرهمزمان انجام شد؛ پاسخ سرور دریافت نمی‌شود.', ['topic' => $topic]);
        return true;
    }

    private function order_payload($order)
    {
        $lineItems = [];
        foreach ($order->get_items('line_item') as $item) {
            $product = $item->get_product();
            $qty = max(1, (int) $item->get_quantity());
            $lineItems[] = [
                'id' => $item->get_id(),
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'name' => $item->get_name(),
                'sku' => $product ? $product->get_sku() : null,
                'quantity' => $qty,
                'price' => $qty ? ((float) $item->get_total() / $qty) : 0,
                'total' => (string) $item->get_total(),
                'meta_data' => $this->item_meta($item),
            ];
        }

        return [
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'date_created' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
            'date_created_gmt' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : null,
            'customer_id' => $order->get_customer_id(),
            'total' => (string) $order->get_total(),
            'total_tax' => (string) $order->get_total_tax(),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'billing' => [
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ],
            'shipping' => [
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
            ],
            'line_items' => $lineItems,
        ];
    }

    private function customer_payload_from_customer($customer)
    {
        return [
            'id' => $customer->get_id(),
            'email' => $customer->get_email(),
            'username' => $customer->get_username(),
            'first_name' => $customer->get_first_name(),
            'last_name' => $customer->get_last_name(),
            'phone' => $customer->get_billing_phone(),
            'billing' => [
                'first_name' => $customer->get_billing_first_name(),
                'last_name' => $customer->get_billing_last_name(),
                'company' => $customer->get_billing_company(),
                'address_1' => $customer->get_billing_address_1(),
                'address_2' => $customer->get_billing_address_2(),
                'city' => $customer->get_billing_city(),
                'state' => $customer->get_billing_state(),
                'postcode' => $customer->get_billing_postcode(),
                'country' => $customer->get_billing_country(),
                'email' => $customer->get_billing_email(),
                'phone' => $customer->get_billing_phone(),
            ],
            'shipping' => [
                'first_name' => $customer->get_shipping_first_name(),
                'last_name' => $customer->get_shipping_last_name(),
                'company' => $customer->get_shipping_company(),
                'address_1' => $customer->get_shipping_address_1(),
                'address_2' => $customer->get_shipping_address_2(),
                'city' => $customer->get_shipping_city(),
                'state' => $customer->get_shipping_state(),
                'postcode' => $customer->get_shipping_postcode(),
                'country' => $customer->get_shipping_country(),
            ],
        ];
    }

    private function product_payload($product)
    {
        $categories = [];
        foreach ($product->get_category_ids() as $categoryId) {
            $term = get_term($categoryId, 'product_cat');
            if ($term && ! is_wp_error($term)) {
                $categories[] = ['id' => $categoryId, 'name' => $term->name];
            }
        }

        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'status' => $product->get_status(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'categories' => $categories,
        ];
    }

    private function item_meta($item)
    {
        $out = [];
        foreach ($item->get_meta_data() as $meta) {
            $data = $meta->get_data();
            if (! empty($data['key']) && ! is_array($data['value'])) {
                $out[] = ['key' => $data['key'], 'value' => $data['value']];
            }
        }
        return $out;
    }

    private function post_to_crm($topic, $payload, $blocking = false, $url = null)
    {
        $url = $this->clean_url($url ?: get_option(self::OPT_URL));
        $secret = trim((string) get_option(self::OPT_SECRET));
        if (! $url || ! $secret) {
            return ['ok' => false, '_http_code' => 'NO_SETTINGS', '_raw' => 'تنظیمات اتصال کامل نیست.'];
        }

        $body = wp_json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));
        $result = $this->remote_post($url, [
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 3,
            'sslverify' => false,
            'blocking' => $blocking,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'MoshtariYarConnector/' . self::VERSION,
                'x-wc-webhook-topic' => $topic,
                'x-wc-webhook-signature' => $signature,
                'x-wc-webhook-delivery-id' => wp_generate_uuid4(),
            ],
            'body' => $body,
        ]);

        if (is_wp_error($result)) {
            return ['ok' => false, '_http_code' => 'WP_ERROR', '_raw' => $result->get_error_message()];
        }

        if (! $blocking) {
            return ['ok' => true, '_http_code' => 'ASYNC', '_raw' => 'ارسال غیرهمزمان'];
        }

        $code = wp_remote_retrieve_response_code($result);
        $raw = wp_remote_retrieve_body($result);
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            $data = ['ok' => $code >= 200 && $code < 300];
        }
        $data['_http_code'] = $code;
        $data['_raw'] = $raw;
        return $data;
    }

    private function with_forced_resolve($url, $callback)
    {
        $ip = trim((string) get_option(self::OPT_CRM_IP));
        $host = parse_url($url, PHP_URL_HOST);
        $hasIp = $ip && $host && filter_var($ip, FILTER_VALIDATE_IP);
        $resolver = function ($handle) use ($host, $ip, $hasIp) {
            if (defined('CURLOPT_CONNECTTIMEOUT')) {
                curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
            }
            if (defined('CURLOPT_TIMEOUT')) {
                curl_setopt($handle, CURLOPT_TIMEOUT, 45);
            }
            if ($hasIp && defined('CURLOPT_RESOLVE')) {
                curl_setopt($handle, CURLOPT_RESOLVE, ["{$host}:443:{$ip}", "{$host}:80:{$ip}"]);
            }
        };
        add_action('http_api_curl', $resolver, 10, 1);
        try {
            return $callback();
        } finally {
            remove_action('http_api_curl', $resolver, 10);
        }
    }

    private function remote_get($url, $args)
    {
        return $this->with_forced_resolve($url, function () use ($url, $args) {
            return wp_remote_get($url, $args);
        });
    }

    private function remote_post($url, $args)
    {
        return $this->with_forced_resolve($url, function () use ($url, $args) {
            return wp_remote_post($url, $args);
        });
    }

    private function clean_url($url)
    {
        $url = trim((string) $url);
        if (preg_match('#https?://[^\s\]\)\<\>]+#i', $url, $match)) {
            $url = $match[0];
        }
        $url = html_entity_decode(strip_tags($url), ENT_QUOTES, 'UTF-8');
        $url = trim($url, " \t\n\r\0\x0B[]()<>/؛،");
        return esc_url_raw($url);
    }

    private function test_url()
    {
        $url = $this->clean_url(get_option(self::OPT_URL));
        if (! $url) {
            return null;
        }
        return preg_replace('#/webhook/(\d+)(\?.*)?$#', '/test/$1', $url);
    }

    private function wallet_url($action)
    {
        $url = $this->clean_url(get_option(self::OPT_URL));
        if (! $url) {
            return null;
        }
        return preg_replace('#/webhook/(\d+)(\?.*)?$#', '/wallet/$1/' . $action, $url);
    }

    private function customer_payload($order = null)
    {
        $phone = $order ? $order->get_billing_phone() : (WC()->customer ? WC()->customer->get_billing_phone() : '');
        $email = $order ? $order->get_billing_email() : (WC()->customer ? WC()->customer->get_billing_email() : '');
        return ['phone' => $phone, 'email' => $email];
    }

    private function wallet_balance($order = null)
    {
        $url = $this->wallet_url('balance');
        if (! $url) {
            return 0;
        }
        $data = $this->post_to_crm('wallet.balance', $this->customer_payload($order), true, $url);
        return is_array($data) && ! empty($data['ok']) ? (int) ($data['balance'] ?? 0) : 0;
    }

    public function wallet_checkout_box()
    {
        if (! function_exists('WC') || ! WC()->session) {
            return;
        }
        $balance = $this->wallet_balance();
        if ($balance <= 0) {
            return;
        }
        echo '<div class="woocommerce-info" style="margin-bottom:12px"><label><input type="checkbox" name="wcrm_use_wallet" value="1" ' . checked(WC()->session->get(self::SESSION_USE), '1', false) . '> استفاده از کیف پول مشتری‌یار - موجودی: <b>' . wc_price($balance) . '</b></label></div>';
    }

    public function capture_wallet_choice($postData)
    {
        if (! function_exists('WC') || ! WC()->session) {
            return;
        }
        parse_str($postData, $data);
        WC()->session->set(self::SESSION_USE, ! empty($data['wcrm_use_wallet']) ? '1' : '0');
    }

    public function apply_wallet_fee($cart)
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            return;
        }
        if (! function_exists('WC') || ! WC()->session || WC()->session->get(self::SESSION_USE) !== '1') {
            if (function_exists('WC') && WC()->session) {
                WC()->session->set(self::SESSION_AMOUNT, 0);
            }
            return;
        }
        $balance = $this->wallet_balance();
        if ($balance <= 0) {
            return;
        }
        $total = max(0, $cart->get_subtotal() + $cart->get_shipping_total() + $cart->get_fee_total());
        $amount = min($balance, (int) round($total));
        if ($amount > 0) {
            $cart->add_fee('استفاده از کیف پول مشتری‌یار', -$amount, false);
            WC()->session->set(self::SESSION_AMOUNT, $amount);
        }
    }

    public function save_wallet_meta($order, $data)
    {
        $amount = (int) (function_exists('WC') && WC()->session ? WC()->session->get(self::SESSION_AMOUNT) : 0);
        if ($amount > 0) {
            $order->update_meta_data('_wcrm_wallet_amount', $amount);
        }
    }

    public function debit_wallet_for_order($orderId)
    {
        $order = wc_get_order($orderId);
        if (! $order || $order->get_meta('_wcrm_wallet_debited')) {
            return;
        }
        $amount = (int) $order->get_meta('_wcrm_wallet_amount');
        if ($amount <= 0) {
            return;
        }
        $payload = array_merge($this->customer_payload($order), ['order_id' => $orderId, 'amount' => $amount]);
        $data = $this->post_to_crm('wallet.debit', $payload, true, $this->wallet_url('debit'));
        if (is_array($data) && ! empty($data['ok'])) {
            $order->update_meta_data('_wcrm_wallet_debited', '1');
            $order->save();
        }
    }

    public function refund_wallet_for_order($orderId)
    {
        $order = wc_get_order($orderId);
        if (! $order || ! $order->get_meta('_wcrm_wallet_debited') || $order->get_meta('_wcrm_wallet_refunded')) {
            return;
        }
        $amount = (int) $order->get_meta('_wcrm_wallet_amount');
        if ($amount <= 0) {
            return;
        }
        $payload = array_merge($this->customer_payload($order), ['order_id' => $orderId, 'amount' => $amount]);
        $data = $this->post_to_crm('wallet.refund', $payload, true, $this->wallet_url('refund'));
        if (is_array($data) && ! empty($data['ok'])) {
            $order->update_meta_data('_wcrm_wallet_refunded', '1');
            $order->save();
        }
    }

    public function my_account_wallet()
    {
        $balance = $this->wallet_balance();
        $club = esc_url(get_option(self::OPT_CLUB_URL));
        echo '<section class="woocommerce-info"><h3>باشگاه مشتریان مشتری‌یار</h3><p>موجودی کیف پول شما: <b>' . wc_price($balance) . '</b></p>';
        if ($club) {
            echo '<p><a class="button" href="' . $club . '" target="_blank">ورود به باشگاه مشتریان</a></p>';
        }
        echo '</section>';
    }

    public function shortcode_club_link()
    {
        $club = esc_url(get_option(self::OPT_CLUB_URL));
        return $club ? '<a class="button" href="' . $club . '">باشگاه مشتریان</a>' : '';
    }

    private function add_log($title, $status, $message, $debug = [], $topic = null, $payload = null)
    {
        $logs = $this->logs();
        array_unshift($logs, [
            'time' => current_time('mysql'),
            'title' => $title,
            'status' => $status,
            'status_label' => $status === 'success' ? 'موفق' : ($status === 'failed' ? 'ناموفق' : 'در صف'),
            'message' => $message,
            'debug' => $debug,
            'topic' => $topic,
            'payload' => $payload,
        ]);
        $logs = array_slice($logs, 0, 80);
        update_option(self::OPT_LOGS, $logs, false);
        if (function_exists('wc_get_logger')) {
            $level = $status === 'failed' ? 'error' : 'info';
            wc_get_logger()->{$level}($title . ' - ' . $message, ['source' => 'moshtariyar-crm']);
        }
    }

    private function logs()
    {
        $logs = get_option(self::OPT_LOGS, []);
        return is_array($logs) ? $logs : [];
    }

    private function redirect_with_message($notice, $message)
    {
        wp_safe_redirect(add_query_arg([
            'page' => 'woo-crm-connector',
            'wcrm_notice' => $notice,
            'wcrm_msg' => rawurlencode($message),
        ], admin_url('options-general.php')));
        exit;
    }
}

new Woo_CRM_Connector();