<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class Upload
{
    private const WEBP_QUALITY = 82;

    private string $uploadDir;
    private string $uploadUrl;
    private int $maxSize;
    /** @var string[] */
    private array $allowedTypes;

    public function __construct(Config $config)
    {
        $this->uploadDir    = $config->uploadDir();
        $this->uploadUrl    = $config->uploadUrl();
        $this->maxSize      = $config->maxFileSize();
        $this->allowedTypes = $config->allowedTypes();
    }

    public function store(UploadedFileInterface $file): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with error code ' . $file->getError());
        }

        if ($file->getSize() > $this->maxSize) {
            throw new RuntimeException('File exceeds maximum size of ' . ($this->maxSize / 1024 / 1024) . 'MB');
        }

        $clientMime = $file->getClientMediaType();
        if (!in_array($clientMime, $this->allowedTypes, true)) {
            throw new RuntimeException('File type not allowed: ' . $clientMime);
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Move to temp location first, then verify real MIME from content
        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
        $baseName = bin2hex(random_bytes(16));
        $name = $baseName . '.' . strtolower($ext);
        $originalPath = $this->uploadDir . '/' . $name;
        $file->moveTo($originalPath);

        $realMime = $this->detectMime($originalPath);
        if ($realMime !== null && !in_array($realMime, $this->allowedTypes, true)) {
            @unlink($originalPath);
            throw new RuntimeException('File content does not match an allowed type');
        }

        $webpUrl = $this->convertToWebP($originalPath, $baseName, $realMime ?? $clientMime);

        return $webpUrl ?? ($this->uploadUrl . '/' . $name);
    }

    private function convertToWebP(string $sourcePath, string $baseName, string $mime): ?string
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return null;
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            default      => null,
        };

        if (!$image) {
            return null;
        }

        if ($mime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        $webpName = $baseName . '.webp';
        $webpPath = $this->uploadDir . '/' . $webpName;

        $success = @imagewebp($image, $webpPath, self::WEBP_QUALITY);
        imagedestroy($image);

        if (!$success || !is_file($webpPath)) {
            return null;
        }

        $originalSize = filesize($sourcePath);
        $webpSize = filesize($webpPath);

        if ($webpSize >= $originalSize) {
            @unlink($webpPath);
            return null;
        }

        @unlink($sourcePath);

        return $this->uploadUrl . '/' . $webpName;
    }

    /** Detect real MIME type from file content using finfo. */
    private function detectMime(string $path): ?string
    {
        if (!function_exists('finfo_open')) {
            return null; // can't verify — allow through
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime ?: null;
    }

    public function deleteFile(string $url): bool
    {
        if (!str_starts_with($url, $this->uploadUrl . '/')) {
            return false;
        }

        $filename = basename($url);
        $path = $this->uploadDir . '/' . $filename;

        if (is_file($path)) {
            return unlink($path);
        }

        return false;
    }
}
