<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::middleware('auth')->group(function () {

    // ==========================================
    // BLOCO 1: PROJETOS
    // ==========================================
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateProjectStatus'])->name('projects.updateStatus');
    Route::get('projects/{project}/settings', [ProjectController::class, 'settings'])->name('projects.settings');

    Route::resource('projects', ProjectController::class)->only([
        'index', 'create', 'store', 'show', 'update', 'destroy'
]);

    // Sub-recurso: Membros
    Route::controller(ProjectMemberController::class)
        ->prefix('projects/{project}/members')
        ->name('projects.members.')
        ->group(function () {
            Route::get('selectable', 'selectable')->name('selectable');
            Route::post('/', 'store')->name('store');
            Route::patch('{user}/role', 'updateRole')->name('updateRole');
            Route::delete('{user}', 'destroy')->name('destroy');
        });



    // ==========================================
    // BLOCO 2: TAREFAS DO PROJETO 
    // ==========================================
    Route::resource('projects.tasks', ProjectTaskController::class)->only([
        'index', 'create', 'store'
    ]);



    // ==========================================
    // BLOCO 3: TAREFAS (Ações Diretas)
    // ==========================================
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateTaskStatus'])->name('tasks.updateTaskStatus');

    Route::resource('tasks', TaskController::class)->only([
        'index', 'show', 'update', 'destroy'
    ]);

    // Sub-recurso: Atribuições
    Route::controller(TaskController::class)
        ->prefix('tasks/{task}/assignees')
        ->name('tasks.assignees.')
        ->group(function () {
            Route::get('selectable', 'selectableAssignees')->name('selectable');
            Route::post('/', 'storeAssignee')->name('store');
            Route::delete('{user}', 'destroyAssignee')->name('destroy');
        });



    // ==========================================
    // USUÁRIOS
    // ==========================================
    Route::resource('users', UserController::class)->only(['show']);

    // ==========================================
    // MENU / REDIRECTS 
    // ==========================================
    Route::get('/meus-projetos', fn() => redirect()->route('projects.index'));
    Route::get('/minhas-tasks', fn() => redirect()->route('tasks.index'));
    Route::get('/meu-perfil', fn() => redirect()->route('users.show', Auth::id()));
});

Route::fallback(fn() => abort(404));