<?php

namespace App\Http\Controllers;

use App\Contracts\HasCommentRecipients;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Mail\NewComment;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request)
    {
        $commentable = $request->commentable();
        abort_unless($commentable, 404);

        $comment = DB::transaction(function () use ($request, $commentable) {
            $data = $request->validated();

            return Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => $commentable->getMorphClass(),
                'commentable_id' => $commentable->getKey(),
                'parent_id' => $data['parent_id'] ?? null,
                'text' => $data['text'],
            ]);
        });

        $comment->load('commentable');
        $actor = Auth::user();
        $commentable = $comment->commentable;
        // se o modelo comentado nao tiver destinatarios de comentario, apenas retorna sem enviar emails
        if (! $commentable instanceof HasCommentRecipients) {
            return redirect()->back()
                ->with('alert-success', 'Comentario adicionado com sucesso!');
        }

        // obtem os destinatarios de comentario do modelo comentado,
        // que serao notificados sobre o novo comentario, exceto o proprio autor do comentario
        // espécie de "watchers" do modelo comentado, que recebem notificacoes sobre novos comentarios,
        // mas sem precisar estar necessariamente relacionado ao modelo comentado,
        // como por exemplo os membros de um projeto que recebem notificacoes sobre comentarios em reunioes do projeto
        $recipients = $commentable->commentRecipients();

        // envia notificacao para os destinatarios de comentario do modelo comentado, exceto para o proprio autor do comentario
        $recipients
            ->filter(fn(User $user) => !$actor || $user->id !== $actor->id)
            ->each(function (User $user) use ($actor, $comment, $commentable) {
                Mail::to($user->email)->queue(new NewComment($user, $actor, $comment, $commentable));
            });

        return redirect()->back()
            ->with('alert-success', 'Comentario adicionado com sucesso!');
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);

        DB::transaction(function () use ($comment) {
            $comment->update([
                'is_active' => false,
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Comentario removido com sucesso!');
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        Gate::authorize('update', $comment);

        DB::transaction(function () use ($request, $comment) {
            $data = $request->validated();
            $comment->update([
                'text' => $data['text'],
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Comentario atualizado com sucesso!');
    }
}
