<?php

namespace App\Http\Controllers;

use App\Contracts\Watchable;
use App\Morphs\CommentableMap;
use App\Models\User;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    public function update(
        Request $request,
        string $watchableType,
        int $watchableId,
    ) {
        $watchable = $this->resolve($watchableType, $watchableId);
        $this->authorizeViewing($request->user(), $watchable);

        Watch::enableFor($request->user()->id, $watchable);

        return back()->with('alert-success', 'Notificações ativadas com sucesso!');
    }

    public function destroy(
        Request $request,
        string $watchableType,
        int $watchableId,
    ) {
        $watchable = $this->resolve($watchableType, $watchableId);
        $this->authorizeViewing($request->user(), $watchable);
        Watch::disableFor($request->user()->id, $watchable);

        return back()->with('alert-success', 'Notificações desativadas com sucesso!');
    }

    private function resolve(string $type, int $id): Watchable&Model
    {
        $class = CommentableMap::resolveClass($type);
        abort_unless($class, 404);

        $model = $class::query()->findOrFail($id);
        abort_unless($model instanceof Watchable, 404);

        return $model;
    }

    private function authorizeViewing(User $user, Watchable $watchable): void
    {
        abort_unless($watchable->watchCanBeViewedBy($user), 403);
    }
}
