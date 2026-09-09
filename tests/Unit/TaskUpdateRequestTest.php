<?php

namespace Tests\Unit;

use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Routing\Route;
use Tests\TestCase;

class TaskUpdateRequestTest extends TestCase
{
    public function test_update_rules_do_not_require_the_project_route_parameter(): void
    {
        $request = new UpdateTaskRequest;
        $request->initialize([], [], [], [], [], [
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/tasks/1/info',
        ]);

        $route = new Route('PATCH', 'tasks/{task}/info', []);
        $route->bind($request);
        $route->setParameter('task', (new Task)->setAttribute('id', 1));
        $request->setRouteResolver(fn () => $route);

        $rules = $request->rules();

        $this->assertArrayNotHasKey('assignee_id', $rules);
    }
}
