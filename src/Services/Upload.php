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
        // finfo may report SVG as text/xml or text/html — normalize
        $effectiveMime = $realMime ?? $clientMime;
        if ($clientMime === 'image/svg+xml' && in_array($realMime, ['text/xml', 'text/html', 'image/svg+xml'], true)) {
            $effectiveMime = 'image/svg+xml';
        }
        if ($realMime !== null && !in_array($effectiveMime, $this->allowedTypes, true)) {
            @unlink($originalPath);
            throw new RuntimeException('File content does not match an allowed type');
        }

        // SVG: sanitize and serve as-is (no WebP conversion)
        if ($effectiveMime === 'image/svg+xml') {
            $this->sanitizeSvg($originalPath);
            return $this->uploadUrl . '/' . $name;
        }

        $webpUrl = $this->convertToWebP($originalPath, $baseName, $effectiveMime);

        return $webpUrl ?? ($this->uploadUrl . '/' . $name);
    }

    private function convertToWebP(string $sourcePath, string $baseName, string $mime): ?string
    {
        if (!function_exists('imagewebp')) {
            return null;
        }

        // Already WebP — nothing to convert
        if ($mime === 'image/webp') {
            return null;
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/gif'  => @imagecreatefromgif($sourcePath),
            default      => null,
        };

        if (!$image) {
            return null;
        }

        // Preserve transparency for PNG and GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
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

        // Keep the original file for fallback — only return WebP URL
        return $this->uploadUrl . '/' . $webpName;
    }

    /**
     * Sanitize an SVG file by removing dangerous elements and attributes.
     * Strips scripts, event handlers, foreign objects, and dangerous URIs.
     */
    private function sanitizeSvg(string $path): void
    {
        $xml = file_get_contents($path);
        if ($xml === false) {
            throw new RuntimeException('Failed to read SVG file');
        }

        // Remove processing instructions and XML declarations (except <?xml)
        $xml = preg_replace('/<\?(?!xml\s)[^?]*\?>/i', '', $xml);

        // Remove dangerous elements entirely (with contents)
        $dangerousTags = ['script', 'foreignObject', 'iframe', 'embed', 'object', 'applet'];
        foreach ($dangerousTags as $tag) {
            $xml = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/si', '', $xml);
            $xml = preg_replace('/<' . $tag . '\b[^>]*\/?\s*>/si', '', $xml);
        }

        // Remove event handler attributes (on*)
        $xml = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $xml);
        $xml = preg_replace('/\s+on\w+\s*=\s*\'[^\']*\'/i', '', $xml);
        $xml = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $xml);

        // Remove javascript/vbscript/data URIs in href and xlink:href
        $xml = preg_replace('/(href\s*=\s*["\']?)\s*(javascript|vbscript|data)\s*:[^"\'>\s]*/i', '$1#', $xml);
        $xml = preg_replace('/(xlink:href\s*=\s*["\']?)\s*(javascript|vbscript|data)\s*:[^"\'>\s]*/i', '$1#', $xml);

        // Remove set/animate elements that target dangerous attributes
        $xml = preg_replace('/<(?:set|animate)\b[^>]*attributeName\s*=\s*["\'](?:href|xlink:href|on\w+)["\'][^>]*\/?>/i', '', $xml);

        file_put_contents($path, $xml);
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
        $deleted = false;

        if (is_file($path)) {
            $deleted = unlink($path);
        }

        // Also remove the companion file (original ↔ WebP)
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if ($ext === 'webp') {
            // Delete any original that shares the same base name
            foreach (['jpg', 'jpeg', 'png', 'gif'] as $origExt) {
                $origPath = $this->uploadDir . '/' . $baseName . '.' . $origExt;
                if (is_file($origPath)) {
                    @unlink($origPath);
                }
            }
        } else {
            // Delete the WebP companion if it exists
            $webpPath = $this->uploadDir . '/' . $baseName . '.webp';
            if (is_file($webpPath)) {
                @unlink($webpPath);
            }
        }

        return $deleted;
    }
}
