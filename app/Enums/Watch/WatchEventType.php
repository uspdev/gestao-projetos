<?php

namespace App\Enums\Watch;

enum WatchEventType: string
{
    case COMMENT_CREATED = 'comment.created';
    case TASK_COMPLETED = 'task.completed';
    case MEETING_UPDATED = 'meeting.updated';
    case MEETING_REMOVED = 'meeting.removed';
    case SUBPROJECT_LINKED = 'subproject.linked';
    case SUBPROJECT_UNLINKED = 'subproject.unlinked';
}
