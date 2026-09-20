<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressor
{
    /**
     * Compress an uploaded image, fix orientation, downscale to max dimensions,
     * convert to WebP, and store it in public disk.
     */
    public function compressAndStore(
        UploadedFile $file,
        string $directory = 'foto-bukti',
        int $maxDimension = 1200,
        int $quality = 80
    ): string {
        $realPath = $file->getRealPath();

        // If GD or imagewebp is not available, fallback to standard Laravel store
        if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! $realPath || ! file_exists($realPath)) {
            return $file->store($directory, 'public');
        }

        try {
            $imageInfo = @getimagesize($realPath);
            if (! $imageInfo) {
                return $file->store($directory, 'public');
            }

            $mime = $imageInfo['mime'] ?? '';
            $sourceImage = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($realPath),
                'image/png' => @imagecreatefrompng($realPath),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($realPath) : null,
                'image/gif' => @imagecreatefromgif($realPath),
                default => null,
            };

            if (! $sourceImage) {
                return $file->store($directory, 'public');
            }

            // Fix EXIF orientation if it's a JPEG
            if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($realPath);
                if ($exif && ! empty($exif['Orientation'])) {
                    $sourceImage = $this->correctOrientation($sourceImage, (int) $exif['Orientation']);
                }
            }

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);

            // Calculate new dimensions preserving aspect ratio
            if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
                if ($origWidth >= $origHeight) {
                    $newWidth = $maxDimension;
                    $newHeight = (int) round(($origHeight / $origWidth) * $maxDimension);
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = (int) round(($origWidth / $origHeight) * $maxDimension);
                }

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve alpha transparency for PNG/WebP
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);

                imagecopyresampled(
                    $resizedImage,
                    $sourceImage,
                    0, 0, 0, 0,
                    $newWidth,
                    $newHeight,
                    $origWidth,
                    $origHeight
                );

                imagedestroy($sourceImage);
                $sourceImage = $resizedImage;
            }

            // Generate unique filename with .webp extension
            $filename = Str::random(40).'.webp';
            $relativePath = trim($directory, '/').'/'.$filename;

            // Ensure destination folder exists in storage
            $storage = Storage::disk('public');
            $fullDir = $storage->path($directory);
            if (! file_exists($fullDir)) {
                @mkdir($fullDir, 0755, true);
            }

            $fullDestination = $storage->path($relativePath);

            // Encode to WebP
            $success = imagewebp($sourceImage, $fullDestination, $quality);
            imagedestroy($sourceImage);

            if ($success && file_exists($fullDestination)) {
                return $relativePath;
            }

            return $file->store($directory, 'public');
        } catch (\Throwable $e) {
            // Safe fallback on any error
            return $file->store($directory, 'public');
        }
    }

    /**
     * Correct image orientation according to EXIF data.
     */
    protected function correctOrientation($image, int $orientation)
    {
        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
