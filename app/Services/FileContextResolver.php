<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class FileContextResolver
{
    /**
     * Resolve the files supplied by the source context without applying reader authorization.
     *
     * @return Collection<int, Media>
     */
    public function filesFor(Model $source): Collection
    {
        $files = match (true) {
            $source instanceof Project => $this->ownedFilesFor($source),
            $source instanceof Task => $this->ownedFilesFor($source)
                ->concat($source->loadMissing('project')->project
                    ? $this->ownedFilesFor($source->project)
                    : collect()),
            $source instanceof Meeting => $this->ownedFilesFor($source)
                ->concat($source->sharedFiles()->latest()->get()),
            $source instanceof MeetingItem => $source->loadMissing('meeting')->meeting
                ? $this->filesFor($source->meeting)
                : collect(),
            $source instanceof Comment => $source->loadMissing('commentable')->commentable
                ? $this->filesFor($source->commentable)
                : collect(),
            default => collect(),
        };

        return $files
            ->unique('uuid')
            ->values();
    }

    /**
     * Resolve files owned directly by an entity without applying reader authorization.
     *
     * @return Collection<int, Media>
     */
    public function ownedFilesFor(Model $owner): Collection
    {
        return method_exists($owner, 'media')
            ? $owner->media()->latest()->get()->unique('uuid')->values()
            : collect();
    }
}
