<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\UserController;

Route::view('/', 'landing')->name('landing');

Route::middleware('auth')->group(function () {

    // ==========================================
    // PROJECT
    // ==========================================
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

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
    Route::prefix('projects/{project}')
        ->name('projects.tasks.')
        ->group(function () {
            Route::get('tasks', [ProjectTaskController::class, 'index'])->name('index');
            Route::get('tasks/create', [ProjectTaskController::class, 'create'])->name('create');
            Route::post('tasks', [ProjectTaskController::class, 'store'])->name('store');
        });

    // ==========================================
    // TASKS
    // ==========================================
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');

    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

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
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    // ==========================================
    // MENU
    // ==========================================
    Route::get('/meus-projetos', function () {
        return redirect()->route('projects.index');
    });

    Route::get('/minhas-tasks', function () {
        return redirect()->route('tasks.index');
    });

    Route::get('/meu-perfil', function () {
        return redirect()->route('users.show', Auth::id());
    });
});

Route::fallback(function () {
    abort(404);
});
