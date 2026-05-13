<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request)
    {
        $commentable = $request->commentable();
        abort_unless($commentable, 404);

        DB::transaction(function () use ($request, $commentable) {
            $data = $request->validated();

            Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => $commentable::class,
                'commentable_id' => $commentable->getKey(),
                'parent_id' => $data['parent_id'] ?? null,
                'text' => $data['text'],
            ]);
        });

        return redirect()->back()
            ->with('alert-success', 'Comentario adicionado com sucesso!');
    }
}
