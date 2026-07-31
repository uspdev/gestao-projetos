<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Services\Mentions\MentionManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MentionController extends Controller
{
    public function selectable(Request $request, MentionManager $mentionManager): JsonResponse
    {
        $source = $this->sourceFromRequest($request);
        abort_unless($source, 404);

        $this->authorizeMentioning($request, $source);

        $results = $mentionManager->search(
            $source,
            (string) $request->query('term', ''),
            $request->user(),
            (string) $request->query('filter', $request->query('type', 'all')),
        );
        $groups = $results
            ->groupBy('type')
            ->map(fn ($group): array => [
                'type' => $group->first()['type'],
                'label' => $group->first()['type_label'],
                'results' => $group->values(),
            ])
            ->values();

        return response()->json([
            'results' => $results,
            'result_groups' => $groups,
            'filters' => [
                ['value' => 'all', 'label' => 'Todos'],
                ['value' => 'user', 'label' => 'Pessoas'],
                ['value' => 'project', 'label' => 'Projetos'],
                ['value' => 'task', 'label' => 'Tarefas'],
            ],
        ]);
    }

    private function sourceFromRequest(Request $request): ?Model
    {
        $contextId = (int) $request->query('context_id');

        return match ($request->query('context_type')) {
            'project' => Project::query()->find($contextId),
            'task' => Task::query()->find($contextId),
            'meeting' => Meeting::query()->find($contextId),
            'meeting_item' => MeetingItem::query()->find($contextId),
            'comment' => $this->commentFromRequest($request),
            default => null,
        };
    }

    private function commentFromRequest(Request $request): ?Comment
    {
        $type = (string) $request->query('commentable_type', '');
        $class = \App\Morphs\CommentableMap::resolveClass($type);
        $commentable = $class ? $class::query()->find((int) $request->query('commentable_id')) : null;

        if (! $commentable) {
            return null;
        }

        $comment = new Comment([
            'commentable_type' => $type,
            'commentable_id' => $commentable->getKey(),
            'is_active' => true,
        ]);
        $comment->setRelation('commentable', $commentable);

        return $comment;
    }

    private function authorizeMentioning(Request $request, Model $source): void
    {
        $user = $request->user();

        match (true) {
            $source instanceof Project, $source instanceof Task => Gate::authorize('update', $source),
            $source instanceof Meeting => Gate::authorize('manageFileShares', $source),
            $source instanceof MeetingItem => Gate::authorize('manageFileShares', $source->meeting),
            $source instanceof Comment => Gate::authorize('comment', $source->commentable),
            default => Gate::authorize('comment', $source),
        };
    }
}
