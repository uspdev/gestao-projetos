<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommentPolicy
{
    public function viewAny(User $user, Model $commentable): bool
    {
        return $this->canView($user, $commentable);
    }

    public function view(User $user, Comment $comment): bool
    {
        return $this->canView($user, $comment->commentable);
    }

    public function create(User $user, Model $commentable): bool
    {
        return $this->canInteract($user, $commentable);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->user_id !== $user->id) {
            return false;
        }

        return $this->canInteract($user, $comment->commentable);
    }

    public function update(User $user, Comment $comment): bool
    {
        if (! $comment->is_active) {
            return false;
        }

        if ($comment->user_id !== $user->id) {
            return false;
        }

        return $this->canInteract($user, $comment->commentable);
    }

    private function canInteract(User $user, ?Model $commentable): bool
    {
        if (!$commentable) {
            return false;
        }

        return $user->can('comment', $commentable);
    }

    private function canView(User $user, ?Model $commentable): bool
    {
        if (!$commentable) {
            return false;
        }

        return $user->can('view', $commentable);
    }
}
