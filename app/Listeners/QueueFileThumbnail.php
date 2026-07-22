<?php

namespace App\Listeners;

use App\Jobs\GenerateFileThumbnail;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class QueueFileThumbnail
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        GenerateFileThumbnail::dispatch($event->media)->afterCommit();
    }
}
