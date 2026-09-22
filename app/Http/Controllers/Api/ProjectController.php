<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\Api\ProjectDetailLoader;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function show(
        Request $request,
        Project $project,
        ProjectDetailLoader $details,
    ): ProjectResource
    {
        $apiKey = $request->attributes->get(
            (string) config('api-keys.middleware.request_attribute', 'apiKey')
        );
        $owner = $apiKey?->owner;

        abort_unless(
            $owner instanceof Project
                && (int) $owner->getKey() === (int) $project->getKey(),
            404,
        );

        return new ProjectResource($details->loadProject($project));
    }
}
