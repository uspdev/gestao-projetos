<?php

namespace App\Http\Controllers;

use App\Contracts\Watchable;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\PendingWatchNotification;
use App\Services\MentionIndexer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, MentionIndexer $mentionIndexer)
    {
        $commentable = $request->commentable();
        abort_unless($commentable, 404);

        $comment = DB::transaction(function () use ($request, $commentable, $mentionIndexer) {
            $data = $request->validated();

            $comment = Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => $commentable->getMorphClass(),
                'commentable_id' => $commentable->getKey(),
                'parent_id' => $data['parent_id'] ?? null,
                'text' => $data['text'],
            ]);

            $mentionIndexer->validateAllMentions($comment, 'text', $data['text']);
            $mentionIndexer->synchronize($comment, 'text', $data['text'], Auth::id());

            return $comment;
        });

        $actor = Auth::user();

        if ($actor && $commentable instanceof Watchable) {
            PendingWatchNotification::addForWatchers(
                $commentable,
                PendingWatchNotification::COMMENT_CREATED,
                $actor,
                'Novo comentário.',
                $comment->text,
                $commentable->watchUrl(),
            );
        }

        return redirect()->back()
            ->with('alert-success', 'Comentario adicionado com sucesso!');
    }

    public function destroy(Comment $comment, MentionIndexer $mentionIndexer)
    {
        Gate::authorize('delete', $comment);

        DB::transaction(function () use ($comment, $mentionIndexer) {
            $comment->update([
                'is_active' => false,
            ]);
            $mentionIndexer->clear($comment);
        });

        return redirect()->back()
            ->with('alert-success', 'Comentario removido com sucesso!');
    }

    public function update(UpdateCommentRequest $request, Comment $comment, MentionIndexer $mentionIndexer)
    {
        Gate::authorize('update', $comment);

        DB::transaction(function () use ($request, $comment, $mentionIndexer) {
            $data = $request->validated();
            $mentionIndexer->validateNewMentions($comment, 'text', $data['text']);
            $comment->update([
                'text' => $data['text'],
            ]);
            $mentionIndexer->synchronize($comment, 'text', $data['text'], Auth::id());
        });

        return redirect()->back()
            ->with('alert-success', 'Comentario atualizado com sucesso!');
    }
}
