<?php

use App\Models\Media;
use App\Support\Files\PrivateFileRemover;
use App\Support\Files\UuidFileNamer;
use Spatie\MediaLibrary\MediaCollections\FileAdder;

return [
    'disk_name' => env('MEDIA_DISK', 'files'),
    'conversions_disk_name' => env('MEDIA_CONVERSIONS_DISK', 'files'),
    'max_file_size' => 100 * 1024 * 1024,
    'disallowed_extensions' => FileAdder::$defaultDisallowedExtensions,
    'allowed_extensions' => null,
    'queue_connection_name' => env('QUEUE_CONNECTION', 'sync'),
    'queue_name' => env('MEDIA_QUEUE', ''),
    'queue_conversions_by_default' => false,
    'queue_conversions_after_database_commit' => true,
    'thumbnail_max_pixels' => 25_000_000,
    'thumbnail_max_dimension' => 10_000,
    'thumbnail_max_side' => 480,
    'media_model' => Media::class,
    'file_namer' => UuidFileNamer::class,
    'file_remover_class' => PrivateFileRemover::class,
    'image_driver' => env('IMAGE_DRIVER', 'gd'),
];
