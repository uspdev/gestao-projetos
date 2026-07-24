<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Services\MentionIndexer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MentionController extends Controller
{
    public function selectable(Request $request, MentionIndexer $mentionIndexer): JsonResponse
    {
        $source = $this->sourceFromRequest($request);
        abort_unless($source, 404);

        $this->authorizeMentioning($request, $source);

        $term = trim((string) $request->query('term', ''));
        $users = $mentionIndexer->eligibleUserIds($source)
            ->when($term !== '', function ($userIds) use ($term) {
                return \App\Models\User::query()
                    ->whereIn('id', $userIds)
                    ->where('name', 'like', '%' . $term . '%')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($user) => $user->only(['id', 'name']));
            }, function ($userIds) {
                return \App\Models\User::query()
                    ->whereIn('id', $userIds)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($user) => $user->only(['id', 'name']));
            })
            ->values();

        return response()->json(['results' => $users]);
    }

    private function sourceFromRequest(Request $request): ?Model
    {
        $contextId = (int) $request->query('context_id');

        return match ($request->query('context_type')) {
            'project' => Project::query()->find($contextId),
            'task' => Task::query()->find($contextId),
            'meeting' => Meeting::query()->find($contextId),
            'meeting_item' => MeetingItem::query()->find($contextId),
            'comment' => $this->commentableFromRequest($request),
            default => null,
        };
    }

    private function commentableFromRequest(Request $request): ?Model
    {
        $type = (string) $request->query('commentable_type', '');
        $class = \App\Morphs\CommentableMap::resolveClass($type);

        return $class ? $class::query()->find((int) $request->query('commentable_id')) : null;
    }

    private function authorizeMentioning(Request $request, Model $source): void
    {
        $user = $request->user();

        match (true) {
            $source instanceof Project, $source instanceof Task => Gate::authorize('update', $source),
            $source instanceof Meeting => Gate::authorize('manageFileShares', $source),
            $source instanceof MeetingItem => Gate::authorize('manageFileShares', $source->meeting),
            default => Gate::authorize('comment', $source),
        };
    }
}
