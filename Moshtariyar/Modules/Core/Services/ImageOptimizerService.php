<?php

namespace Modules\Core\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class ImageOptimizerService
{
    public function storeAsWebp(UploadedFile $file, string $directory = 'img/uploads', int $quality = 82): string
    {
        $mime = (string) $file->getMimeType();
        if (! str_starts_with($mime, 'image/')) {
            throw new RuntimeException('فایل ارسال‌شده تصویر نیست.');
        }

        $targetDirectory = $this->publicDirectory(trim($directory, '/'));
        if (! is_dir($targetDirectory)) {
            @mkdir($targetDirectory, 0775, true);
        }

        $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        if (! $fileName) {
            $fileName = 'image';
        }

        $fileName .= '-' . time() . '-' . Str::random(8) . '.webp';
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

        if ($mime === 'image/webp') {
            $file->move($targetDirectory, $fileName);
            return trim($directory, '/') . '/' . $fileName;
        }

        if (extension_loaded('imagick')) {
            $this->storeWithImagick($file->getRealPath(), $targetPath, $quality);
            return trim($directory, '/') . '/' . $fileName;
        }

        if (function_exists('imagewebp')) {
            $this->storeWithGd($file->getRealPath(), $targetPath, $quality);
            return trim($directory, '/') . '/' . $fileName;
        }

        throw new RuntimeException('امکان تبدیل تصویر به webp روی هاست فعال نیست. لطفاً افزونه GD یا Imagick را فعال کنید.');
    }

    public function storeRemoteAsWebp(string $url, string $directory = 'img/uploads', int $quality = 82): ?string
    {
        $url = trim($url);
        if ($url === '' || ! preg_match('/^https?:\/\//i', $url)) {
            return null;
        }

        $targetDirectory = $this->publicDirectory(trim($directory, '/'));
        if (! is_dir($targetDirectory)) {
            @mkdir($targetDirectory, 0775, true);
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $baseName = Str::slug(pathinfo($path, PATHINFO_FILENAME));
        if (! $baseName) {
            $baseName = 'image';
        }

        $fileName = $baseName . '-' . time() . '-' . Str::random(8) . '.webp';
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;
        $tempPath = tempnam(sys_get_temp_dir(), 'crm_remote_image_');

        try {
            $client = new Client([
                'timeout' => 25,
                'connect_timeout' => 10,
                'force_ip_resolve' => 'v4',
                'http_errors' => true,
                'headers' => [
                    'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                    'User-Agent' => 'MoshtariYar-ImageOptimizer/1.0',
                ],
            ]);

            $response = $client->get($url);
            $content = (string) $response->getBody();

            if ($content === '') {
                return null;
            }

            if (strlen($content) > 15 * 1024 * 1024) {
                return null;
            }

            file_put_contents($tempPath, $content);

            $mime = $response->getHeaderLine('Content-Type');
            if ($mime === '') {
                $detected = @getimagesize($tempPath);
                $mime = is_array($detected) ? (string) ($detected['mime'] ?? '') : '';
            }

            $mime = strtolower(trim(explode(';', $mime)[0] ?? ''));

            if ($mime !== '' && ! str_starts_with($mime, 'image/')) {
                return null;
            }

            if ($mime === 'image/webp') {
                file_put_contents($targetPath, $content);
                return trim($directory, '/') . '/' . $fileName;
            }

            if (extension_loaded('imagick')) {
                $this->storeWithImagick($tempPath, $targetPath, $quality);
                return trim($directory, '/') . '/' . $fileName;
            }

            if (function_exists('imagewebp')) {
                $this->storeWithGd($tempPath, $targetPath, $quality);
                return trim($directory, '/') . '/' . $fileName;
            }

            return null;
        } finally {
            if (is_string($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function publicDirectory(string $directory): string
    {
        $directory = trim($directory, '/');

        $publicHtml = realpath(base_path('../public_html'));
        if ($publicHtml && is_dir($publicHtml)) {
            return $publicHtml . DIRECTORY_SEPARATOR . $directory;
        }

        if (function_exists('public_path')) {
            return public_path($directory);
        }

        return base_path($directory);
    }

    private function storeWithImagick(string $sourcePath, string $targetPath, int $quality): void
    {
        $image = new \Imagick($sourcePath);
        $image->autoOrient();
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($quality);
        $image->stripImage();
        $image->writeImage($targetPath);
        $image->clear();
        $image->destroy();
    }

    private function storeWithGd(string $sourcePath, string $targetPath, int $quality): void
    {
        $content = file_get_contents($sourcePath);
        $image = @imagecreatefromstring($content);

        if (! $image) {
            throw new RuntimeException('تصویر قابل خواندن نیست.');
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        if (! imagewebp($image, $targetPath, $quality)) {
            imagedestroy($image);
            throw new RuntimeException('ذخیره تصویر webp انجام نشد.');
        }

        imagedestroy($image);
    }
}