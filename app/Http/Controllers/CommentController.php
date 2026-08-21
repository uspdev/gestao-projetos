<?php

namespace App\Http\Controllers;

use App\Contracts\Watchable;
use App\Enums\Watch\WatchEventType;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\PendingWatchNotification;
use App\Services\Mentions\MentionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, MentionManager $mentionManager)
    {
        $commentable = $request->commentable();
        abort_unless($commentable, 404);

        $comment = DB::transaction(function () use ($request, $commentable, $mentionManager) {
            $data = $request->validated();

            $comment = Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => $commentable->getMorphClass(),
                'commentable_id' => $commentable->getKey(),
                'parent_id' => $data['parent_id'] ?? null,
                'text' => $data['text'],
                'is_active' => true,
            ]);

            $mentionManager->validateAllMentions($comment, 'text', $data['text']);
            $mentionManager->synchronize($comment, 'text', $data['text']);

            return $comment;
        });

        $actor = Auth::user();

        if ($actor && $commentable instanceof Watchable) {
            PendingWatchNotification::addForWatchers(
                $commentable,
                WatchEventType::COMMENT_CREATED,
                $actor,
                'Novo comentário.',
                $comment->text,
                $commentable->watchUrl(),
            );
        }

        return redirect()->back()
            ->withFragment(deep_link_fragment($comment))
            ->with('alert-success', 'Comentario adicionado com sucesso!');
    }

    public function destroy(Comment $comment, MentionManager $mentionManager)
    {
        Gate::authorize('delete', $comment);

        $threadFragment = 'comments-'.$comment->commentable->getMorphClass().'-'.$comment->commentable->getKey();

        DB::transaction(function () use ($comment, $mentionManager) {
            $comment->update([
                'is_active' => false,
            ]);
            $mentionManager->clear($comment);
        });

        return redirect()->back()
            ->withFragment($threadFragment)
            ->with('alert-success', 'Comentario removido com sucesso!');
    }

    public function update(UpdateCommentRequest $request, Comment $comment, MentionManager $mentionManager)
    {
        Gate::authorize('update', $comment);

        DB::transaction(function () use ($request, $comment, $mentionManager) {
            $data = $request->validated();
            $mentionManager->validateNewMentions($comment, 'text', $data['text']);
            $comment->update([
                'text' => $data['text'],
            ]);
            $mentionManager->synchronize($comment, 'text', $data['text']);
        });

        return redirect()->back()
            ->withFragment(deep_link_fragment($comment))
            ->with('alert-success', 'Comentario atualizado com sucesso!');
    }
}
