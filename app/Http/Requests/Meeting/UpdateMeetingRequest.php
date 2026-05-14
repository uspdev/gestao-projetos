<?php

namespace App\Http\Requests\Meeting;

use App\Models\Meeting;
use App\Models\Project;

class UpdateMeetingRequest extends StoreMeetingRequest
{
    public function authorize(): bool
    {
        $meeting = $this->route('meeting');
        $project = $this->route('project');

        if (!$meeting instanceof Meeting || !$project instanceof Project) {
            return false;
        }

        return $this->user()->can('update', [$meeting, $project]);
    }
}
