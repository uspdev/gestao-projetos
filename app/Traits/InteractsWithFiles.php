<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait InteractsWithFiles
{
    use InteractsWithMedia;

    /** @return FileAdder<\App\Models\Media> */
    public function addMedia(string|UploadedFile $file, ?int $uploadedBy = null): FileAdder
    {
        $originalName = $file instanceof UploadedFile
            ? $file->getClientOriginalName()
            : basename($file);

        return app(FileAdderFactory::class)
            ->create($this, $file)
            ->usingFileName($this->normalizeFileName($originalName))
            ->withProperties([
                'original_name' => $originalName,
                'uploaded_by' => $uploadedBy ?? auth()->id(),
            ]);
    }

    private function normalizeFileName(string $originalName): string
    {
        $extension = Str::of(pathinfo($originalName, PATHINFO_EXTENSION))
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->substr(0, 20)
            ->toString();

        if ($extension === '') {
            return $originalName;
        }

        $baseName = substr($originalName, 0, -(strlen(pathinfo($originalName, PATHINFO_EXTENSION)) + 1));

        return "{$baseName}.{$extension}";
    }
}
