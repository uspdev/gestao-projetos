<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingFileShare;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MeetingFileShareController extends Controller
{
    public function store(Request $request, Meeting $meeting): JsonResponse|RedirectResponse
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

        $response = [
            'uuid' => $media->uuid,
            'name' => $media->display_name,
            'markdown' => '@[' . $this->mentionMarkdownLabel((string) $media->display_name)
                . '](mention:file:' . $media->uuid . ')',
        ];

        if ($request->expectsJson()) {
            return response()->json($response, $share->wasRecentlyCreated ? 201 : 200);
        }

        return back()
            ->withFragment(deep_link_fragment($media))
            ->with('alert-success', 'Arquivo compartilhado com a reunião com sucesso.');
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

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back()
            ->withFragment('files-'.$meeting->getMorphClass().'-'.$meeting->getKey())
            ->with('alert-success', 'Arquivo removido da reunião com sucesso.');
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

    private function mentionMarkdownLabel(string $label): string
    {
        return str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $label);
    }
}
