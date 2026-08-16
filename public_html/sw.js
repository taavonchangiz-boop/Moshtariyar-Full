/*
 * ═══════════════════════════════════════════════════════
 *  سرویس ورکر — مشتری‌یار
 *  استراتژی: شبکه نخست، کش در نقش پشتیبان (Network First)
 *  آخرین به‌روزرسانی: ۱۴۰۵/۰۵/۰۲
 * ═══════════════════════════════════════════════════════
 */

/* ── نام و نسخهٔ کش ── */
const نام_کش = 'moshtariyar-cache-v3';
const تاریخ_نصب = '۱۴۰۵-۰۵-۰۲';

/* ── فهرست دارایی‌های قابل پیش‌بارگذاری در زمان نصب ── */
const دارایی‌های_ایستا = [
    '/',
    '/css/fonts.css',
    '/css/app-layout.css',
    '/css/floating-actions.css',
    '/css/ui-readability-fix.css',
    '/css/hero-metrics-compact.css',
    '/css/club-portal-master.css',
    '/css/club-portal-dashboard.css',
    '/css/customer-portal.css',
    '/js/jdatepicker.js',
    '/js/app-core.js',
    '/js/iran-cities.js',
    '/fonts/estedad/Estedad-VF.woff2',
    '/fonts/vazirmatn/Vazirmatn-VF.woff2',
    '/img/moshtariyar-logo.svg',
];

/* ── رویداد نصب ── */
self.addEventListener('install', function(رویداد) {
    رویداد.waitUntil(
        caches.open(نام_کش).then(function(کش) {
            return کش.addAll(دارایی‌های_ایستا).catch(function(خطا) {
                // اگر حتی یکی از فایل‌ها بارگذاری نشد، کش ناقص نماند
                console.log('⚠️ پیش‌بارگذاری کش ناقص ماند: ' + خطا.message);
            });
        })
    );
    // فعال‌سازی بلافاصله (بدون انتظار برای بسته شدن تب‌های قدیمی)
    self.skipWaiting();
});

/* ── رویداد فعال‌سازی ── */
self.addEventListener('activate', function(رویداد) {
    رویداد.waitUntil(
        caches.keys().then(function(کلیدها) {
            return Promise.all(
                کلیدها.map(function(کلید) {
                    if (کلید !== نام_کش) {
                        return caches.delete(کلید);
                    }
                })
            );
        })
    );
    // کنترل تمام صفحات بلافاصله
    self.clients.claim();
});

/* ── رویداد دریافت (Fetch) — استراتژی: شبکه نخست، کش پشتیبان ── */
self.addEventListener('fetch', function(رویداد) {
    // فقط درخواست‌های GET را مدیریت کن
    if (رویداد.request.method !== 'GET') return;

    // از درخواست‌های API و مسیرهای مدیریتی رد شو
    var نشانی = new URL(رویداد.request.url);
    if (
        نشانی.pathname.startsWith('/app/') ||
        نشانی.pathname.startsWith('/api/') ||
        نشانی.pathname.startsWith('/superadmin/') ||
        نشانی.pathname.startsWith('/login') ||
        نشانی.pathname.startsWith('/logout') ||
        نشانی.pathname.startsWith('/storage/')
    ) {
        return;
    }

    رویداد.respondWith(
        fetch(رویداد.request)
            .then(function(پاسخ) {
                // پاسخ موفق را در کش ذخیره کن
                if (پاسخ && پاسخ.status === 200 && پاسخ.type === 'basic') {
                    var پاسخ_کش = پاسخ.clone();
                    caches.open(نام_کش).then(function(کش) {
                        کش.put(رویداد.request, پاسخ_کش);
                    });
                }
                return پاسخ;
            })
            .catch(function() {
                // اگر شبکه در دسترس نبود، از کش بخوان
                return caches.match(رویداد.request).then(function(پاسخ_کش) {
                    return پاسخ_کش || new Response('اتصال شبکه قطع است. لطفاً دوباره تلاش کنید.', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
                    });
                });
            })
    );
});