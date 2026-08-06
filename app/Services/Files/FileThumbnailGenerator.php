<?php

namespace App\Services\Files;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class FileThumbnailGenerator
{
    private const RASTER_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
    ];

    public function generate(Media $media): bool
    {
        try {
            if (! in_array($media->mime_type, self::RASTER_MIME_TYPES, true)) {
                $this->markNotSupported($media);

                return true;
            }

            if (! function_exists('imagecreatefromstring')) {
                throw new RuntimeException('A extensão GD não está disponível.');
            }

            $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            $image = @getimagesizefromstring($contents);

            if ($image === false || ! in_array($image['mime'] ?? null, self::RASTER_MIME_TYPES, true)) {
                $this->markNotSupported($media);

                return true;
            }

            [$width, $height] = $image;
            $maxDimension = (int) config('media-library.thumbnail_max_dimension');
            $maxPixels = (int) config('media-library.thumbnail_max_pixels');

            if ($width > $maxDimension || $height > $maxDimension || $width * $height > $maxPixels) {
                $this->markNotSupported($media);

                return true;
            }

            $source = @imagecreatefromstring($contents);

            if ($source === false) {
                $this->markNotSupported($media);

                return true;
            }

            $thumbnail = null;

            try {
                $maximum = (int) config('media-library.thumbnail_max_side');

                if ($maximum < 1) {
                    throw new RuntimeException('O limite da miniatura é inválido.');
                }

                $scale = min(1, $maximum / max($width, $height));
                $thumbnailWidth = max(1, (int) round($width * $scale));
                $thumbnailHeight = max(1, (int) round($height * $scale));
                $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

                if ($thumbnail === false) {
                    throw new RuntimeException('Não foi possível alocar a miniatura.');
                }

                $background = imagecolorallocate($thumbnail, 255, 255, 255);

                if ($background === false || ! imagefill($thumbnail, 0, 0, $background)) {
                    throw new RuntimeException('Não foi possível preparar a miniatura.');
                }

                if (! imagecopyresampled(
                    $thumbnail,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $thumbnailWidth,
                    $thumbnailHeight,
                    $width,
                    $height,
                )) {
                    throw new RuntimeException('Não foi possível redimensionar a imagem.');
                }

                $thumbnailContents = $this->encodeJpeg($thumbnail);
            } finally {
                if ($thumbnail !== null) {
                    imagedestroy($thumbnail);
                }

                imagedestroy($source);
            }

            $thumbnailPath = $this->thumbnailPath($media);
            $thumbnailDisk = Storage::disk($media->conversions_disk ?: $media->disk);

            if (! $thumbnailDisk->put($thumbnailPath, $thumbnailContents)) {
                throw new RuntimeException('Não foi possível salvar a miniatura.');
            }

            $media->setCustomProperty('thumbnail_status', 'ready');
            $media->setCustomProperty('thumbnail_generated_at', now()->toIso8601String());
            $media->saveQuietly();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function markNotSupported(Media $media): void
    {
        $media->setCustomProperty('thumbnail_status', 'not_supported');
        $media->forgetCustomProperty('thumbnail_generated_at');
        $media->saveQuietly();
    }

    private function encodeJpeg(mixed $thumbnail): string
    {
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            if (! imagejpeg($thumbnail, null, 85)) {
                throw new RuntimeException('Não foi possível codificar a miniatura.');
            }

            $contents = ob_get_contents();

            if ($contents === false || $contents === '') {
                throw new RuntimeException('A miniatura codificada está vazia.');
            }

            return $contents;
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }
    }

    private function thumbnailPath(Media $media): string
    {
        return $media->getKey().'/conversions/'
            .pathinfo($media->file_name, PATHINFO_FILENAME)
            .'-thumbnail.jpg';
    }
}
