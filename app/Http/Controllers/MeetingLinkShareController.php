<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Meeting;
use App\Models\MeetingLinkShare;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MeetingLinkShareController extends Controller
{
    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('manageLinkShares', $meeting);
        $validated = $request->validate(['link_uuid' => ['required', 'uuid']]);

        $link = Link::query()->where('uuid', $validated['link_uuid'])->firstOrFail();
        Gate::forUser($request->user())->authorize('view', $link);
        abort_unless($this->isAllowedSource($meeting, $link), 404);

        $share = DB::transaction(fn (): MeetingLinkShare => MeetingLinkShare::query()->firstOrCreate(
            ['meeting_id' => $meeting->id, 'link_id' => $link->id],
            ['shared_by' => $request->user()->id],
        ));

        if ($share->wasRecentlyCreated) {
            activity()
                ->useLog('link')
                ->event('shared')
                ->performedOn($link)
                ->causedBy($request->user())
                ->withProperties(['meeting_id' => $meeting->id])
                ->log('shared with meeting');
        }

        return back()
            ->withFragment(deep_link_fragment($link))
            ->with('alert-success', 'Link compartilhado com a reunião com sucesso.');
    }

    public function destroy(Request $request, Meeting $meeting, string $uuid): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('manageLinkShares', $meeting);
        $link = Link::query()->where('uuid', $uuid)->firstOrFail();
        $share = MeetingLinkShare::query()
            ->where('meeting_id', $meeting->id)
            ->where('link_id', $link->id)
            ->firstOrFail();

        DB::transaction(function () use ($share, $link, $meeting, $request): void {
            activity()
                ->useLog('link')
                ->event('unshared')
                ->performedOn($link)
                ->causedBy($request->user())
                ->withProperties(['meeting_id' => $meeting->id])
                ->log('removed from meeting');
            $share->delete();
        });

        return back()
            ->withFragment('files-'.$meeting->getMorphClass().'-'.$meeting->getKey())
            ->with('alert-success', 'Link removido da reunião com sucesso.');
    }

    private function isAllowedSource(Meeting $meeting, Link $link): bool
    {
        $owner = $link->linkable;

        if ($owner instanceof Project) {
            return $meeting->projects()->whereKey($owner->getKey())->exists()
                || $this->isAgendaOwner($meeting, $owner);
        }

        return $owner instanceof Task && $this->isAgendaOwner($meeting, $owner);
    }

    private function isAgendaOwner(Meeting $meeting, Project|Task $owner): bool
    {
        return $meeting->meetingItems()
            ->where('discussable_type', $owner->getMorphClass())
            ->where('discussable_id', $owner->getKey())
            ->exists();
    }
}
