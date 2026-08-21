<?php

namespace App\Http\Controllers;

use App\Http\Requests\Duplicate\StoreDuplicateRequest;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Morphs\Duplicable;
use Illuminate\Support\Facades\DB;

class DuplicateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            \UspTheme::activeUrl('meus-projetos');

            return $next($request);
        });
    }

    public function store(
        StoreDuplicateRequest $request,
        Project $project,
        string $duplicable_type,
        string $duplicable_id
    ) {
        $duplicable = $request->duplicable();
        abort_unless($duplicable instanceof Duplicable, 404);

        if ($reason = $duplicable->duplicationBlockReason()) {
            abort(403, $reason);
        }

        $copy = DB::transaction(
            fn() => $duplicable->duplicate($request->duplicationOptions())
        );

        return match (true) {
            $copy instanceof Task => redirect()->route('tasks.show', $copy)
                ->withFragment(deep_link_fragment($copy))
                ->with('alert-success', 'Tarefa duplicada com sucesso!'),
            $copy instanceof Meeting => redirect()->route('projects.meetings.show', [$project, $copy])
                ->withFragment(deep_link_fragment($copy))
                ->with('alert-success', 'Reunião duplicada com sucesso!'),
            $copy instanceof Project => redirect()->route('projects.show', $copy)
                ->withFragment(deep_link_fragment($copy))
                ->with('alert-success', 'Projeto duplicado com sucesso!'),
            default => abort(500),
        };
    }
}
