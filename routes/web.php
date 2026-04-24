<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\UserProjectController;
use App\Http\Controllers\UserTaskController;
use App\Http\Controllers\UserController;

Route::view('/', 'landing')->name('landing');

Route::middleware('auth')->group(function () {

    // ==========================================
    // PROJECT
    // ==========================================
    Route::resource('projects', ProjectController::class)
        ->only(['create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::controller(ProjectController::class)
        ->prefix('projects/{project}')
        ->name('projects.')
        ->group(function () {
            Route::patch('status', 'updateProjectStatus')->name('updateStatus');
        });

    // PROJECT MEMBERS
    Route::controller(ProjectController::class)
        ->prefix('projects/{project}/members')
        ->name('projects.members.')
        ->group(function () {
            Route::get('selectable', 'selectableMembers')->name('selectable');
            Route::post('/', 'storeMember')->name('store');
            Route::patch('{user}/role', 'updateMemberRole')->name('updateRole');
            Route::delete('{user}', 'destroyMember')->name('destroy');
        });

    // PROJECT TASKS
    Route::resource('projects.tasks', ProjectTaskController::class)
        ->only(['index', 'create', 'store']);

    // ==========================================
    // TASKS
    // ==========================================
    Route::resource('tasks', TaskController::class)
        ->only(['show', 'edit', 'update', 'destroy']);

    Route::controller(TaskController::class)
        ->prefix('tasks/{task}')
        ->name('tasks.')
        ->group(function () {
            Route::patch('status', 'updateTaskStatus')->name('updateTaskStatus');

            Route::prefix('assignees')->name('assignees.')->group(function () {
                Route::get('selectable', 'selectableAssignees')->name('selectable');
                Route::post('/', 'storeAssignee')->name('store');
                Route::delete('{user}', 'destroyAssignee')->name('destroy');
            });
        });


    // ==========================================
    // USER
    // ==========================================
    Route::resource('users', UserController::class)->only(['show']);
    Route::resource('users.projects', UserProjectController::class)->only(['index']);
    Route::resource('users.tasks', UserTaskController::class)->only(['index']);


    // ==========================================
    // MENU
    // ==========================================
    Route::get('/meus-projetos', function () {
        return redirect()->route('users.projects.index', Auth::id());
    });

    Route::get('/minhas-tasks', function () {
        return redirect()->route('users.tasks.index', Auth::id());
    });

    Route::get('/meu-perfil', function () {
        return redirect()->route('users.show', Auth::id());
    });
});

Route::fallback(function () {
    abort(404);
});
