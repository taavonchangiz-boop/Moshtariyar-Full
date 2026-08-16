<?php

namespace Modules\Core\Support;

use Illuminate\Http\UploadedFile;

/**
 * ابزار بهینه‌سازی تصویر (سراسری برای کل سایت)
 *
 * قوانین:
 * - همه تصاویر آپلودی به WebP تبدیل می‌شوند
 * - تصویر اصلی ذخیره نمی‌شود (فقط WebP)
 * - در صورت لزوم، اندازه تصویر کاهش پیدا می‌کند
 * - کیفیت پیش‌فرض ۸۵ (بهترین موازنه اندازه/کیفیت برای WebP)
 * - فرمت‌های ورودی مجاز: jpg, jpeg, png, gif, bmp, webp
 *
 * استفاده:
 *   $webpPath = ImageOptimizer::saveAsWebp($request->file('logo'), public_path('uploads/branding'), 'logo', 800, 85);
 *   // خروجی: /home/ayarproi/public_html/uploads/branding/logo.webp
 *
 * یا:
 *   $webpUrl = ImageOptimizer::process($request->file('logo'), 'branding', 'company-logo', 800);
 *   // خروجی: /uploads/branding/company-logo.webp
 */
class ImageOptimizer
{
    /** فرمت‌های ورودی مجاز */
    public const ALLOWED_MIMES = [
        'image/jpeg', 'image/jpg', 'image/png',
        'image/gif', 'image/bmp', 'image/webp',
    ];

    public const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    /** کیفیت پیش‌فرض WebP */
    public const DEFAULT_QUALITY = 85;

    /** حداکثر عرض پیش‌فرض */
    public const DEFAULT_MAX_WIDTH = 1200;

    /**
     * ذخیره تصویر به‌صورت WebP در مسیر مطلق و بازگرداندن مسیر فایل نهایی
     *
     * @param UploadedFile|string $source        فایل آپلودی یا مسیر مطلق تصویر
     * @param string              $absoluteDir   مسیر مطلق پوشه مقصد (بدون / پایانی)
     * @param string              $filename      نام فایل بدون پسوند
     * @param int                 $maxWidth      حداکثر عرض تصویر (px)
     * @param int                 $quality       کیفیت WebP (0-100)
     *
     * @return string|null مسیر مطلق فایل ذخیره‌شده یا null در صورت خطا
     */
    public static function saveAsWebp(
        $source,
        string $absoluteDir,
        string $filename,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $quality = self::DEFAULT_QUALITY
    ): ?string {
        // اطمینان از وجود پوشه
        if (! is_dir($absoluteDir)) {
            @mkdir($absoluteDir, 0755, true);
        }
        if (! is_dir($absoluteDir) || ! is_writable($absoluteDir)) {
            return null;
        }

        // خواندن مسیر تصویر منبع
        if ($source instanceof UploadedFile) {
            if (! $source->isValid()) return null;
            if (! in_array($source->getMimeType(), self::ALLOWED_MIMES, true)) return null;
            $srcPath = $source->getRealPath();
        } else {
            if (! is_string($source) || ! file_exists($source)) return null;
            $srcPath = $source;
        }

        // پاک کردن نام فایل از کاراکترهای خطرناک
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename);
        if (empty($filename)) $filename = 'image_' . time();

        $destPath = rtrim($absoluteDir, '/') . '/' . $filename . '.webp';

        // بارگذاری تصویر با GD
        $image = self::loadImage($srcPath);
        if (! $image) return null;

        // resize در صورت لزوم
        $w = imagesx($image);
        $h = imagesy($image);
        if ($w > $maxWidth) {
            $newW = $maxWidth;
            $newH = (int) round($h * ($maxWidth / $w));
            $resized = imagecreatetruecolor($newW, $newH);
            // شفافیت
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefill($resized, 0, 0, $transparent);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($image);
            $image = $resized;
        }

        // ذخیره WebP
        if (! function_exists('imagewebp')) {
            imagedestroy($image);
            return null;
        }
        $success = @imagewebp($image, $destPath, max(0, min(100, $quality)));
        imagedestroy($image);

        if (! $success || ! file_exists($destPath)) return null;

        return $destPath;
    }

    /**
     * روش سطح بالا برای آپلود در پوشه‌ای زیر public_html/uploads
     *
     * @return string|null آدرس نسبی برای استفاده در سایت (مثلاً /uploads/branding/logo.webp) یا null
     */
    public static function process(
        UploadedFile $file,
        string $subFolder = 'general',
        string $filename = null,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $quality = self::DEFAULT_QUALITY
    ): ?string {
        $filename = $filename ?: ('img_' . time() . '_' . mt_rand(1000, 9999));
        $subFolder = trim($subFolder, '/');

        // مسیر مطلق public_html/uploads/<subFolder>
        $publicPath = self::resolvePublicPath();
        $absoluteDir = $publicPath . '/uploads/' . $subFolder;

        $saved = self::saveAsWebp($file, $absoluteDir, $filename, $maxWidth, $quality);
        if (! $saved) return null;

        return '/uploads/' . $subFolder . '/' . basename($saved);
    }

    /**
     * حذف فایل قدیمی (در صورت وجود)
     */
    public static function deleteIfExists(string $relativeUrl): bool
    {
        if (empty($relativeUrl)) return false;
        $publicPath = self::resolvePublicPath();
        $absolute = $publicPath . '/' . ltrim($relativeUrl, '/');
        if (file_exists($absolute) && is_file($absolute)) {
            return @unlink($absolute);
        }
        return false;
    }

    /**
     * بارگذاری تصویر با GD (پشتیبانی از انواع فرمت‌ها)
     * @return \GdImage|false
     */
    private static function loadImage(string $path)
    {
        if (! function_exists('exif_imagetype')) {
            $info = getimagesize($path);
            $type = $info[2] ?? null;
        } else {
            $type = @exif_imagetype($path);
        }

        switch ($type) {
            case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:  return @imagecreatefrompng($path);
            case IMAGETYPE_GIF:  return @imagecreatefromgif($path);
            case IMAGETYPE_WEBP: return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            case IMAGETYPE_BMP:  return function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false;
        }
        return false;
    }

    /**
     * مسیر public_html — اول با تابع لاراول (public_path) در صورت بارگیری
     */
    private static function resolvePublicPath(): string
    {
        if (function_exists('public_path')) {
            return rtrim(public_path(), '/');
        }
        // fallback
        $base = dirname(dirname(dirname(dirname(__DIR__))));
        return $base . '/public';
    }
}