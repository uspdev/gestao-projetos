<?php

return [
    'discussable' => [
        'project' => App\Models\Project::class,
        'task'    => App\Models\Task::class,
    ],

    'commentable' => [
        'project'      => App\Models\Project::class,
        'task'         => App\Models\Task::class,
        'meeting'      => App\Models\Meeting::class,
        'meeting_item' => App\Models\MeetingItem::class,
    ],
];