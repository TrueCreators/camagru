<?php

declare(strict_types=1);

namespace Services;

/**
 * Сервис обработки изображений с использованием библиотеки GD
 */
class ImageService
{
    private const UPLOAD_DIR = '/var/www/html/public/uploads/images/';
    private const OVERLAYS_DIR = '/var/www/html/assets/overlays/';
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 МБ
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    private const OUTPUT_WIDTH = 640;
    private const OUTPUT_HEIGHT = 480;

    /**
     * Проверяет загруженный файл и возвращает понятную ошибку или null, если всё корректно
     */
    public static function getUploadValidationError(array $file): ?string
    {
        if (!isset($file['error'])) {
            return 'Invalid upload payload.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return match ((int)$file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large. Maximum size is 5MB.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                default => 'Upload failed. Please try again.'
            };
        }

        if (!isset($file['size']) || $file['size'] > self::MAX_FILE_SIZE) {
            return 'File is too large. Maximum size is 5MB.';
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'Invalid uploaded file.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return 'Unsupported format. Allowed: JPEG, PNG, GIF.';
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return 'The uploaded file is not a valid image.';
        }

        return null;
    }

    /**
     * Обрабатывает загруженное изображение с наложением
     */
    public static function processUploadedImage(array $file, string $overlayName): ?string
    {
        // Проверка файла
        if (self::getUploadValidationError($file) !== null) {
            return null;
        }

        // Создание изображения из загруженного файла
        $sourceImage = self::createImageFromFile($file['tmp_name'], $file['type']);
        if ($sourceImage === null) {
            return null;
        }

        // Изменение размера до стандартных размеров
        $resizedImage = self::resizeImage($sourceImage, self::OUTPUT_WIDTH, self::OUTPUT_HEIGHT);
        imagedestroy($sourceImage);

        // Наложение оверлея, если он указан
        if (!empty($overlayName)) {
            $overlayPath = self::OVERLAYS_DIR . basename($overlayName);
            if (file_exists($overlayPath)) {
                self::applyOverlay($resizedImage, $overlayPath);
            }
        }

        // Сохранение итогового изображения
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
     * Обрабатывает снимок с веб-камеры в base64 с наложением
     */
    public static function processWebcamCapture(string $base64Data, string $overlayName): ?string
    {
        // Декодирование данных base64
        $data = self::decodeBase64Image($base64Data);
        if ($data === null) {
            return null;
        }

        // Создание изображения из данных
        $sourceImage = imagecreatefromstring($data);
        if ($sourceImage === false) {
            return null;
        }

        // Изменение размера до стандартных размеров
        $resizedImage = self::resizeImage($sourceImage, self::OUTPUT_WIDTH, self::OUTPUT_HEIGHT);
        imagedestroy($sourceImage);

        // Наложение оверлея, если он указан
        if (!empty($overlayName)) {
            $overlayPath = self::OVERLAYS_DIR . basename($overlayName);
            if (file_exists($overlayPath)) {
                self::applyOverlay($resizedImage, $overlayPath);
            }
        }

        // Сохранение итогового изображения
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
     * Применяет PNG-оверлей с альфа-каналом
     */
    private static function applyOverlay(\GdImage $baseImage, string $overlayPath): void
    {
        $overlay = imagecreatefrompng($overlayPath);
        if ($overlay === false) {
            return;
        }

        // Включение альфа-смешивания
        imagealphablending($baseImage, true);

        // Получение размеров
        $baseWidth = imagesx($baseImage);
        $baseHeight = imagesy($baseImage);
        $overlayWidth = imagesx($overlay);
        $overlayHeight = imagesy($overlay);

        // Изменение размера оверлея под базовое изображение
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

        // Наложение оверлея на базовое изображение
        imagecopy($baseImage, $resizedOverlay, 0, 0, 0, 0, $baseWidth, $baseHeight);

        imagedestroy($overlay);
        imagedestroy($resizedOverlay);
    }

    /**
     * Изменяет размер изображения с сохранением пропорций и обрезкой по центру
     */
    private static function resizeImage(\GdImage $source, int $targetWidth, int $targetHeight): \GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        // Вычисление масштаба
        $scaleX = $targetWidth / $sourceWidth;
        $scaleY = $targetHeight / $sourceHeight;
        $scale = max($scaleX, $scaleY);

        $newWidth = (int)($sourceWidth * $scale);
        $newHeight = (int)($sourceHeight * $scale);

        // Вычисление позиции обрезки (по центру)
        $cropX = (int)(($newWidth - $targetWidth) / 2);
        $cropY = (int)(($newHeight - $targetHeight) / 2);

        // Создание масштабированного изображения
        $scaledImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled(
            $scaledImage,
            $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Создание итогового обрезанного изображения
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
     * Создаёт GD-изображение из файла
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
     * Декодирует данные изображения в base64
     */
    private static function decodeBase64Image(string $base64Data): ?string
    {
        // Удаление префикса data URL, если он присутствует
        if (preg_match('/^data:image\/\w+;base64,/', $base64Data)) {
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        }

        $data = base64_decode($base64Data, true);
        if ($data === false) {
            return null;
        }

        // Проверка, что данные изображения валидны
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $data);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return null;
        }

        return $data;
    }

    /**
     * Генерирует уникальное имя файла
     */
    private static function generateFilename(): string
    {
        return uniqid('img_', true) . '.png';
    }

    /**
     * Удаляет файл изображения
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
     * Возвращает доступные оверлеи
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
     * Проверяет, что директория загрузок существует
     */
    public static function ensureUploadDir(): bool
    {
        if (!is_dir(self::UPLOAD_DIR)) {
            return mkdir(self::UPLOAD_DIR, 0755, true);
        }
        return true;
    }
}
