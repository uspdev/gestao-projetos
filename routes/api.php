<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectRequestController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('projects/{project}', [ProjectController::class, 'show'])
    ->middleware('uspdevApiKeys:projects.read')
    ->name('api.projects.show');

Route::get('projects/{project}/tasks', [TaskController::class, 'index'])
    ->middleware('uspdevApiKeys:tasks.read')
    ->name('api.projects.tasks.index');

Route::get('projects/{project}/tasks/{task}', [TaskController::class, 'show'])
    ->whereNumber('task')
    ->middleware('uspdevApiKeys:tasks.read')
    ->name('api.projects.tasks.show');

Route::post('projects/{project}/requests', [ProjectRequestController::class, 'store'])
    ->middleware('uspdevApiKeys:requests.create')
    ->name('api.projects.requests.store');

Route::get('projects/{project}/requests', [ProjectRequestController::class, 'index'])
    ->middleware('uspdevApiKeys:requests.read')
    ->name('api.projects.requests.index');

Route::get('projects/{project}/requests/{projectRequest}', [ProjectRequestController::class, 'show'])
    ->whereNumber('projectRequest')
    ->middleware('uspdevApiKeys:requests.read')
    ->name('api.projects.requests.show');
