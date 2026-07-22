<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateFileThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RASTER_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
    ];

    public function __construct(public Media $media) {}

    public function handle(): void
    {
        $media = $this->media->fresh();

        if (! $media) {
            return;
        }

        try {
            $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            $image = @getimagesizefromstring($contents);

            if ($image === false || ! in_array($image['mime'] ?? null, self::RASTER_MIME_TYPES, true)) {
                $this->setStatus($media, 'not_supported');

                return;
            }

            [$width, $height] = $image;
            $maxDimension = (int) config('media-library.thumbnail_max_dimension');
            $maxPixels = (int) config('media-library.thumbnail_max_pixels');

            if ($width > $maxDimension || $height > $maxDimension || $width * $height > $maxPixels) {
                $this->setStatus($media, 'not_supported');

                return;
            }

            if (! function_exists('imagecreatefromstring')) {
                $this->setStatus($media, 'not_supported');

                return;
            }

            $source = @imagecreatefromstring($contents);

            if ($source === false) {
                $this->setStatus($media, 'not_supported');

                return;
            }

            $maximum = (int) config('media-library.thumbnail_max_side');
            $scale = min(1, $maximum / max($width, $height));
            $thumbnailWidth = max(1, (int) round($width * $scale));
            $thumbnailHeight = max(1, (int) round($height * $scale));
            $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

            imagefill($thumbnail, 0, 0, imagecolorallocate($thumbnail, 255, 255, 255));
            imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $width, $height);

            ob_start();
            imagejpeg($thumbnail, null, 85);
            $thumbnailContents = (string) ob_get_clean();

            imagedestroy($thumbnail);
            imagedestroy($source);

            Storage::disk($media->conversions_disk ?: $media->disk)
                ->put($this->thumbnailPath($media), $thumbnailContents);

            $media->setCustomProperty('thumbnail_status', 'ready');
            $media->setCustomProperty('thumbnail_generated_at', now()->toIso8601String());
            $media->saveQuietly();
        } catch (Throwable) {
            $this->setStatus($media, 'failed');
        }
    }

    private function setStatus(Media $media, string $status): void
    {
        $media->setCustomProperty('thumbnail_status', $status);
        $media->saveQuietly();
    }

    private function thumbnailPath(Media $media): string
    {
        return $media->getKey().'/conversions/'
            .pathinfo($media->file_name, PATHINFO_FILENAME)
            .'-thumbnail.jpg';
    }
}
