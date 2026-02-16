<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Aws\S3\S3Client;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use TinyShop\Models\Setting;

final class Upload
{
    private const WEBP_QUALITY = 82;

    private string $uploadDir;
    private string $uploadUrl;
    private int $maxSize;
    /** @var string[] */
    private array $allowedTypes;
    private Setting $settings;

    public function __construct(Config $config, Setting $settings)
    {
        $this->uploadDir    = $config->uploadDir();
        $this->uploadUrl    = $config->uploadUrl();
        $this->maxSize      = $config->maxFileSize();
        $this->allowedTypes = $config->allowedTypes();
        $this->settings     = $settings;
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
            return $this->finalize($originalPath, $name, 'image/svg+xml');
        }

        $webpName = null;
        $webpPath = null;
        $converted = $this->convertToWebP($originalPath, $baseName, $effectiveMime);
        if ($converted !== null) {
            $webpName = $baseName . '.webp';
            $webpPath = $this->uploadDir . '/' . $webpName;
        }

        $finalPath = $webpPath ?? $originalPath;
        $finalName = $webpName ?? $name;
        $finalMime = $webpPath ? 'image/webp' : ($effectiveMime ?? 'application/octet-stream');

        return $this->finalize($finalPath, $finalName, $finalMime, $originalPath !== $finalPath ? $originalPath : null);
    }

    private function finalize(string $localPath, string $fileName, string $mime, ?string $companionPath = null): string
    {
        $s3Client = $this->buildS3Client();
        if ($s3Client !== null) {
            $s3Key = 'uploads/' . $fileName;
            try {
                $s3Client->putObject([
                    'Bucket'      => $this->getS3Bucket(),
                    'Key'         => $s3Key,
                    'SourceFile'  => $localPath,
                    'ContentType' => $mime,
                    'ACL'         => 'public-read',
                ]);

                // Clean up local temp files — S3 is the source of truth
                @unlink($localPath);
                if ($companionPath !== null && is_file($companionPath)) {
                    @unlink($companionPath);
                }

                return $this->getS3PublicUrl($s3Key);
            } catch (\Throwable $e) {
                // S3 failed — fall through to local storage
            }
        }

        return $this->uploadUrl . '/' . $fileName;
    }

    /* ── S3 helpers ── */

    private function buildS3Client(): ?S3Client
    {
        $all = $this->settings->all();
        $bucket    = trim($all['s3_bucket'] ?? '');
        $accessKey = trim($all['s3_access_key'] ?? '');
        $secretKey = trim($all['s3_secret_key'] ?? '');

        if ($bucket === '' || $accessKey === '' || $secretKey === '') {
            return null;
        }

        $config = [
            'version'     => 'latest',
            'region'      => trim($all['s3_region'] ?? '') ?: 'us-east-1',
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ];

        $endpoint = trim($all['s3_endpoint'] ?? '');
        if ($endpoint !== '') {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }

        return new S3Client($config);
    }

    private function getS3Bucket(): string
    {
        return trim($this->settings->get('s3_bucket', '') ?? '');
    }

    private function getS3PublicUrl(string $key): string
    {
        $cdnUrl = trim($this->settings->get('s3_cdn_url', '') ?? '');
        if ($cdnUrl !== '') {
            return rtrim($cdnUrl, '/') . '/' . $key;
        }

        $endpoint = trim($this->settings->get('s3_endpoint', '') ?? '');
        $bucket   = $this->getS3Bucket();
        $region   = trim($this->settings->get('s3_region', '') ?? '') ?: 'us-east-1';

        if ($endpoint !== '') {
            return rtrim($endpoint, '/') . '/' . $bucket . '/' . $key;
        }

        return 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com/' . $key;
    }

    private function isS3Url(string $url): bool
    {
        if (!str_starts_with($url, 'https://')) {
            return false;
        }

        $bucket = $this->getS3Bucket();
        if ($bucket === '') {
            return false;
        }

        $cdnUrl = trim($this->settings->get('s3_cdn_url', '') ?? '');
        if ($cdnUrl !== '' && str_starts_with($url, rtrim($cdnUrl, '/'))) {
            return true;
        }

        $endpoint = trim($this->settings->get('s3_endpoint', '') ?? '');
        if ($endpoint !== '' && str_starts_with($url, rtrim($endpoint, '/'))) {
            return true;
        }

        return str_contains($url, $bucket . '.s3.')
            || str_contains($url, '.amazonaws.com/' . $bucket . '/');
    }

    private function extractS3Key(string $url): ?string
    {
        $cdnUrl = trim($this->settings->get('s3_cdn_url', '') ?? '');
        if ($cdnUrl !== '' && str_starts_with($url, rtrim($cdnUrl, '/'))) {
            return ltrim(substr($url, strlen(rtrim($cdnUrl, '/'))), '/');
        }

        $bucket = $this->getS3Bucket();

        $endpoint = trim($this->settings->get('s3_endpoint', '') ?? '');
        if ($endpoint !== '') {
            $prefix = rtrim($endpoint, '/') . '/' . $bucket . '/';
            if (str_starts_with($url, $prefix)) {
                return substr($url, strlen($prefix));
            }
        }

        $patterns = [
            '#^https://' . preg_quote($bucket, '#') . '\.s3\.[^/]+\.amazonaws\.com/(.+)$#',
            '#^https://s3\.[^/]+\.amazonaws\.com/' . preg_quote($bucket, '#') . '/(.+)$#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    private function deleteFromS3(string $url): bool
    {
        $s3Client = $this->buildS3Client();
        if ($s3Client === null) {
            return false;
        }

        $key = $this->extractS3Key($url);
        if ($key === null) {
            return false;
        }

        $bucket  = $this->getS3Bucket();
        $dir     = pathinfo($key, PATHINFO_DIRNAME);
        $base    = pathinfo($key, PATHINFO_FILENAME);
        $ext     = pathinfo($key, PATHINFO_EXTENSION);

        try {
            $s3Client->deleteObject(['Bucket' => $bucket, 'Key' => $key]);

            // Delete companion files (original ↔ WebP)
            if ($ext === 'webp') {
                foreach (['jpg', 'jpeg', 'png', 'gif'] as $origExt) {
                    try {
                        $s3Client->deleteObject(['Bucket' => $bucket, 'Key' => $dir . '/' . $base . '.' . $origExt]);
                    } catch (\Throwable $e) {
                        // companion may not exist
                    }
                }
            } else {
                try {
                    $s3Client->deleteObject(['Bucket' => $bucket, 'Key' => $dir . '/' . $base . '.webp']);
                } catch (\Throwable $e) {
                    // companion may not exist
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /* ── WebP conversion ── */

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

    /* ── SVG sanitization ── */

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

    /* ── Delete ── */

    public function deleteFile(string $url): bool
    {
        // S3 URL — full https:// URL
        if ($this->isS3Url($url)) {
            return $this->deleteFromS3($url);
        }

        // Local URL
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
