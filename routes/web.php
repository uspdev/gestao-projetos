<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\UserProjectController;
use App\Http\Controllers\UserTaskController;
use App\Http\Controllers\UserController;

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return redirect()->route('users.projects.index', Auth::id());
    });

    Route::resource('projects', ProjectController::class)
        ->only(['create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::post('projects/{project}/members', [ProjectController::class, 'storeMember'])
        ->name('projects.members.store');

    Route::delete('projects/{project}/members/{user}', [ProjectController::class, 'destroyMember'])
        ->name('projects.members.destroy');

    Route::get('projects/{project}/members/selectable', [ProjectController::class, 'selectableMembers'])
        ->name('projects.members.selectable');

    Route::resource('tasks', TaskController::class)
        ->only(['show', 'edit', 'update', 'destroy']);

    Route::patch('tasks/{task}/status', [TaskController::class, 'updateTaskStatus'])
        ->name('tasks.updateTaskStatus');

    Route::resource('projects.tasks', ProjectTaskController::class)
        ->only(['index', 'create', 'store']);

    Route::resource('users', UserController::class)
        ->only(['show']);

    Route::resource('users.projects', UserProjectController::class)
        ->only(['index']);

    Route::resource('users.tasks', UserTaskController::class)
        ->only(['index']);

});

Route::fallback(function () {
    abort(404);
});
