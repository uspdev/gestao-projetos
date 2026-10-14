<?php

namespace App\Http\Resources\Meeting;

use App\Http\Resources\Shared\ApiDetailResource;
use App\Models\MeetingItem;
use Illuminate\Http\Request;

class MeetingDetailResource extends ApiDetailResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $meeting = $this->resource;

        return array_merge((new MeetingResource($meeting))->resolve($request), [
            'notes' => $meeting->notes,
            'ata' => $meeting->ata,
            'transcription' => $meeting->transcription,
            'agenda' => $meeting->meetingItems
                ->map(fn (MeetingItem $item): array => (new MeetingItemResource($item))->resolve($request))
                ->values()
                ->all(),
        ], $this->visibleDetail($request));
    }
}
