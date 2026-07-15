<?php

namespace App\Policies;

use App\Models\MeetingItem;
use App\Models\User;

class MeetingItemPolicy
{
    public function view(User $user, MeetingItem $meetingItem): bool
    {
        $meetingItem->loadMissing('meeting.projects');

        return $meetingItem->meeting?->projects->contains(
            fn ($project) => $user->can('view', [$meetingItem->meeting, $project])
        ) ?? false;
    }

    public function comment(User $user, MeetingItem $meetingItem): bool
    {
        $meetingItem->loadMissing('meeting');

        return $meetingItem->meeting
            ? $user->can('comment', $meetingItem->meeting)
            : false;
    }
}
