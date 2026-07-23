<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingFileShare;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MeetingFileShareController extends Controller
{
    public function store(Request $request, Meeting $meeting): JsonResponse
    {
        Gate::forUser($request->user())->authorize('manageFileShares', $meeting);

        $validated = $request->validate([
            'media_uuid' => ['required', 'uuid'],
        ]);

        $media = Media::query()->where('uuid', $validated['media_uuid'])->firstOrFail();
        Gate::forUser($request->user())->authorize('view', $media);

        abort_unless($this->isAllowedSource($meeting, $media), 404);

        $share = DB::transaction(fn (): MeetingFileShare => MeetingFileShare::query()->firstOrCreate(
            ['meeting_id' => $meeting->id, 'media_id' => $media->id],
            ['shared_by' => $request->user()->id],
        ));

        return response()->json([
            'uuid' => $media->uuid,
            'name' => $media->display_name,
            'markdown' => "[{$media->display_name}](/files/{$media->uuid})",
        ], $share->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Meeting $meeting, string $uuid)
    {
        Gate::forUser($request->user())->authorize('manageFileShares', $meeting);

        $media = Media::query()->where('uuid', $uuid)->firstOrFail();

        $share = MeetingFileShare::query()
            ->where('meeting_id', $meeting->id)
            ->where('media_id', $media->id)
            ->firstOrFail();

        $share->delete();

        return response()->noContent();
    }

    private function isAllowedSource(Meeting $meeting, Media $media): bool
    {
        $owner = $media->model;

        if ($owner instanceof Project) {
            return $meeting->projects()->whereKey($owner->getKey())->exists()
                || $this->isAgendaOwner($meeting, $owner);
        }

        if (! $owner instanceof Task) {
            return false;
        }

        return $this->isAgendaOwner($meeting, $owner);
    }

    private function isAgendaOwner(Meeting $meeting, Project|Task $owner): bool
    {
        return $meeting->meetingItems()
            ->where('discussable_type', $owner->getMorphClass())
            ->where('discussable_id', $owner->getKey())
            ->exists();
    }
}
