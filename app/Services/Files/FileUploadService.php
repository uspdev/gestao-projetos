<?php

namespace App\Services\Files;

use App\Models\Media;
use App\Models\User;
use App\Support\Files\PrivateFileRemover;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class FileUploadService
{
    public function __construct(
        private FileThumbnailGenerator $thumbnailGenerator,
        private PrivateFileRemover $fileRemover,
    ) {}

    public function upload(Model $owner, UploadedFile $file, User $actor): ?Media
    {
        $media = null;
        $thumbnailFailed = false;

        try {
            return DB::transaction(function () use (&$media, &$thumbnailFailed, $owner, $file, $actor): Media {
                $media = $owner->addMedia($file, $actor->getKey())->toMediaCollection();

                if (! $this->thumbnailGenerator->generate($media)) {
                    $thumbnailFailed = true;
                    throw new RuntimeException('Falha técnica ao gerar a miniatura.');
                }

                activity()
                    ->useLog('file')
                    ->event('uploaded')
                    ->performedOn($media)
                    ->causedBy($actor)
                    ->withProperties([
                        'attributes' => [
                            'uuid' => $media->uuid,
                            'owner_type' => $media->model_type,
                            'owner_id' => $media->model_id,
                            'size' => $media->size,
                        ],
                    ])
                    ->log('uploaded');

                return $media;
            });
        } catch (Throwable $exception) {
            if ($media instanceof Media) {
                try {
                    $this->fileRemover->removeAllFiles($media);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            if ($thumbnailFailed) {
                return null;
            }

            throw $exception;
        }
    }
}
