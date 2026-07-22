<?php

namespace App\Support\Files;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;

class PrivateFileRemover extends DefaultFileRemover
{
    public function removeAllFiles(Media $media): void
    {
        parent::removeAllFiles($media);

        foreach (array_unique([$media->disk, $media->conversions_disk ?: $media->disk]) as $disk) {
            $thumbnailPath = $this->thumbnailPath($media);
            $thumbnailDirectory = dirname($thumbnailPath);
            $mediaDirectory = (string) $media->getKey();

            $this->filesystem->disk($disk)->delete($thumbnailPath);

            if ($this->filesystem->disk($disk)->allFiles($thumbnailDirectory) === []) {
                $this->filesystem->disk($disk)->deleteDirectory($thumbnailDirectory);
            }

            if ($this->filesystem->disk($disk)->allFiles($mediaDirectory) === []) {
                $this->filesystem->disk($disk)->deleteDirectory($mediaDirectory);
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
