#!/usr/bin/env bash
#
# ساخت بستهٔ «آمادهٔ نصب» مشتری‌یار برای هاست اشتراکی cPanel.
# این اسکریپت را یک‌بار روی سیستمی که PHP + Composer دارد اجرا کنید.
# خروجی: moshtariyar-ready.zip  که کاربر فقط آن را آپلود و اکسترکت می‌کند،
# سپس نصب از طریق مرورگر (/install) انجام می‌شود — بدون نیاز به ترمینال.
#
set -e

echo "==> نصب وابستگی‌ها (composer)..."
composer install --no-dev --optimize-autoloader

echo "==> آماده‌سازی پوشه‌ها..."
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R 0775 storage bootstrap/cache 2>/dev/null || true

# اطمینان از نبودن قفل نصب و .env در بسته
rm -f storage/installed.lock .env

echo "==> ساخت فایل zip نهایی..."
OUT="moshtariyar-ready.zip"
rm -f "$OUT"
zip -rq "$OUT" . \
  -x '*.git*' \
  -x 'node_modules/*' \
  -x 'build-package.sh' \
  -x 'ui-preview.html'

echo ""
echo "✅ بستهٔ آمادهٔ نصب ساخته شد: $OUT"
echo "مراحل برای کاربر:"
echo "  ۱) فایل را در هاست آپلود و اکسترکت کنید."
echo "  ۲) محتوای پوشهٔ public را در public_html قرار دهید (یا public_html را به public اشاره دهید)."
echo "  ۳) در مرورگر به آدرس سایت بروید؛ به نصاب هدایت می‌شوید."
