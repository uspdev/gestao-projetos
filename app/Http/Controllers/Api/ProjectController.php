<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\ClientSystem;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function show(Request $request, Project $project): ProjectResource
    {
        $apiKey = $request->attributes->get(
            (string) config('api-keys.middleware.request_attribute', 'apiKey')
        );
        $owner = $apiKey?->owner;

        abort_unless(
            $owner instanceof ClientSystem
                && (int) $owner->project_id === (int) $project->getKey(),
            404,
        );

        $project->load([
            'projectType.modules',
            'phase',
            'parent',
            'tags',
        ]);

        return new ProjectResource($project);
    }
}
