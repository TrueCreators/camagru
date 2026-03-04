<?php

declare(strict_types=1);

namespace Services;

/**
 * Image processing service using GD Library
 */
class ImageService
{
    private const UPLOAD_DIR = '/var/www/html/public/uploads/images/';
    private const OVERLAYS_DIR = '/var/www/html/assets/overlays/';
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    private const OUTPUT_WIDTH = 640;
    private const OUTPUT_HEIGHT = 480;

    /**
     * Process uploaded image with overlay
     */
    public static function processUploadedImage(array $file, string $overlayName): ?string
    {
        // Validate file
        if (!self::validateUpload($file)) {
            return null;
        }

        // Create image from uploaded file
        $sourceImage = self::createImageFromFile($file['tmp_name'], $file['type']);
        if ($sourceImage === null) {
            return null;
        }

        // Resize to standard dimensions
        $resizedImage = self::resizeImage($sourceImage, self::OUTPUT_WIDTH, self::OUTPUT_HEIGHT);
        imagedestroy($sourceImage);

        // Apply overlay if specified
        if (!empty($overlayName)) {
            $overlayPath = self::OVERLAYS_DIR . basename($overlayName);
            if (file_exists($overlayPath)) {
                self::applyOverlay($resizedImage, $overlayPath);
            }
        }

        // Save final image
        $filename = self::generateFilename();
        $outputPath = self::UPLOAD_DIR . $filename;

        if (!imagepng($resizedImage, $outputPath)) {
            imagedestroy($resizedImage);
            return null;
        }

        imagedestroy($resizedImage);
        return $filename;
    }

    /**
     * Process base64 webcam capture with overlay
     */
    public static function processWebcamCapture(string $base64Data, string $overlayName): ?string
    {
        // Decode base64 data
        $data = self::decodeBase64Image($base64Data);
        if ($data === null) {
            return null;
        }

        // Create image from data
        $sourceImage = imagecreatefromstring($data);
        if ($sourceImage === false) {
            return null;
        }

        // Resize to standard dimensions
        $resizedImage = self::resizeImage($sourceImage, self::OUTPUT_WIDTH, self::OUTPUT_HEIGHT);
        imagedestroy($sourceImage);

        // Apply overlay if specified
        if (!empty($overlayName)) {
            $overlayPath = self::OVERLAYS_DIR . basename($overlayName);
            if (file_exists($overlayPath)) {
                self::applyOverlay($resizedImage, $overlayPath);
            }
        }

        // Save final image
        $filename = self::generateFilename();
        $outputPath = self::UPLOAD_DIR . $filename;

        if (!imagepng($resizedImage, $outputPath)) {
            imagedestroy($resizedImage);
            return null;
        }

        imagedestroy($resizedImage);
        return $filename;
    }

    /**
     * Apply PNG overlay with alpha channel
     */
    private static function applyOverlay(\GdImage $baseImage, string $overlayPath): void
    {
        $overlay = imagecreatefrompng($overlayPath);
        if ($overlay === false) {
            return;
        }

        // Enable alpha blending
        imagealphablending($baseImage, true);

        // Get dimensions
        $baseWidth = imagesx($baseImage);
        $baseHeight = imagesy($baseImage);
        $overlayWidth = imagesx($overlay);
        $overlayHeight = imagesy($overlay);

        // Resize overlay to match base image
        $resizedOverlay = imagecreatetruecolor($baseWidth, $baseHeight);
        imagealphablending($resizedOverlay, false);
        imagesavealpha($resizedOverlay, true);

        $transparent = imagecolorallocatealpha($resizedOverlay, 0, 0, 0, 127);
        imagefill($resizedOverlay, 0, 0, $transparent);

        imagecopyresampled(
            $resizedOverlay,
            $overlay,
            0, 0, 0, 0,
            $baseWidth, $baseHeight,
            $overlayWidth, $overlayHeight
        );

        // Merge overlay onto base
        imagecopy($baseImage, $resizedOverlay, 0, 0, 0, 0, $baseWidth, $baseHeight);

        imagedestroy($overlay);
        imagedestroy($resizedOverlay);
    }

    /**
     * Resize image maintaining aspect ratio and center crop
     */
    private static function resizeImage(\GdImage $source, int $targetWidth, int $targetHeight): \GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        // Calculate scaling
        $scaleX = $targetWidth / $sourceWidth;
        $scaleY = $targetHeight / $sourceHeight;
        $scale = max($scaleX, $scaleY);

        $newWidth = (int)($sourceWidth * $scale);
        $newHeight = (int)($sourceHeight * $scale);

        // Calculate crop position (center)
        $cropX = (int)(($newWidth - $targetWidth) / 2);
        $cropY = (int)(($newHeight - $targetHeight) / 2);

        // Create scaled image
        $scaledImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled(
            $scaledImage,
            $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Create final cropped image
        $finalImage = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopy(
            $finalImage,
            $scaledImage,
            0, 0,
            $cropX, $cropY,
            $targetWidth, $targetHeight
        );

        imagedestroy($scaledImage);

        return $finalImage;
    }

    /**
     * Validate uploaded file
     */
    private static function validateUpload(array $file): bool
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return false;
        }

        // Check MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return false;
        }

        // Verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return false;
        }

        return true;
    }

    /**
     * Create GD image from file
     */
    private static function createImageFromFile(string $filepath, string $mimeType): ?\GdImage
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($filepath) ?: null,
            'image/png' => imagecreatefrompng($filepath) ?: null,
            'image/gif' => imagecreatefromgif($filepath) ?: null,
            default => null
        };
    }

    /**
     * Decode base64 image data
     */
    private static function decodeBase64Image(string $base64Data): ?string
    {
        // Remove data URL prefix if present
        if (preg_match('/^data:image\/\w+;base64,/', $base64Data)) {
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        }

        $data = base64_decode($base64Data, true);
        if ($data === false) {
            return null;
        }

        // Verify it's valid image data
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $data);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return null;
        }

        return $data;
    }

    /**
     * Generate unique filename
     */
    private static function generateFilename(): string
    {
        return uniqid('img_', true) . '.png';
    }

    /**
     * Delete image file
     */
    public static function deleteImage(string $filename): bool
    {
        $filepath = self::UPLOAD_DIR . basename($filename);
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Get available overlays
     */
    public static function getOverlays(): array
    {
        $overlays = [];
        $files = glob(self::OVERLAYS_DIR . '*.png');

        if ($files === false) {
            return $overlays;
        }

        foreach ($files as $file) {
            $overlays[] = [
                'name' => basename($file),
                'url' => '/assets/overlays/' . basename($file)
            ];
        }

        return $overlays;
    }

    /**
     * Ensure upload directory exists
     */
    public static function ensureUploadDir(): bool
    {
        if (!is_dir(self::UPLOAD_DIR)) {
            return mkdir(self::UPLOAD_DIR, 0755, true);
        }
        return true;
    }
}
