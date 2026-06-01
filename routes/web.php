<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('users.show', Auth::id());
    }
    return redirect()->route('about');
})->name('dashboard');

Route::get('about', function () {
    \UspTheme::activeUrl('about');
    return view('landing');
})->name('about');

Route::middleware('auth')->group(function () {

    // ==========================================
    // BLOCO 1: PROJETOS
    // ==========================================

    // Rotas customizadas devem vir antes do resource para evitar conflitos de url.
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateProjectStatus'])->name('projects.updateStatus');
    Route::patch('projects/{project}/phase', [PhaseController::class, 'update'])->name('projects.updatePhase');
    Route::patch('projects/{project}/visibility', [ProjectController::class, 'updateProjectVisibility'])->name('projects.updateVisibility');
    Route::patch('projects/{project}/permission-inheritance', [ProjectController::class, 'updateProjectPermissionInheritance'])
        ->name('projects.updatePermissionInheritance');
    Route::patch('projects/{project}/modules/{module}', [ProjectController::class, 'updateModule'])
        ->name('projects.modules.update');
    Route::patch('projects/{project}/pin', [ProjectController::class, 'togglePin'])->name('projects.togglePin');
    Route::patch('projects/{project}/name', [ProjectController::class, 'updateName'])->name('projects.updateName');
    Route::patch('projects/{project}/slug', [ProjectController::class, 'updateSlug'])->name('projects.updateSlug');
    Route::patch('projects/{project}/description', [ProjectController::class, 'updateDescription'])->name('projects.updateDescription');
    Route::patch('projects/{project}/tags', [ProjectController::class, 'updateTags'])->name('projects.updateTags');
    Route::get('projects/{project}/settings', [ProjectController::class, 'settings'])->name('projects.settings');
    Route::get('projects/{project}/subprojects/selectable', [ProjectController::class, 'selectableSubprojects'])
        ->name('projects.subprojects.selectable');
    Route::post('projects/{project}/subprojects/link', [ProjectController::class, 'linkSubproject'])
        ->name('projects.subprojects.link');
    Route::delete('projects/{project}/subprojects/unlink', [ProjectController::class, 'unlinkSubproject'])
        ->name('projects.subprojects.unlink');

    Route::resource('projects', ProjectController::class)->except(['edit', 'update']);

    // Sub-recurso: Membros
    Route::controller(ProjectMemberController::class)
        ->prefix('projects/{project}/members')
        ->name('projects.members.')
        ->group(function () {
            Route::get('selectable', 'selectable')->name('selectable');
            Route::post('/', 'store')->name('store');
            Route::patch('{user}/role', 'updateRole')->name('updateRole');
            Route::delete('{user}', 'destroy')->name('destroy');
            Route::post('join-inherited', 'joinInherited')->name('joinInherited');
        });



    // ==========================================
    // BLOCO 2: TAREFAS
    // ==========================================

    // Tarefas do projeto
    Route::get('projects/{project}/tasks', [TaskController::class, 'indexProject'])->name('projects.tasks.index');
    Route::resource('projects.tasks', TaskController::class)->except([
        'index',
        'show',
        'edit',
        'update',
        'destroy'
    ]);

    // Tarefas (Ações Diretas)
    Route::get('tasks', [TaskController::class, 'indexUser'])->name('tasks.index');
    // Rotas customizadas devem vir antes do resource para evitar conflitos de url.
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateTaskStatus'])->name('tasks.updateTaskStatus');

    // Update split: description and other info
    Route::patch('tasks/{task}/description', [TaskController::class, 'updateDescription'])->name('tasks.updateDescription');
    Route::patch('tasks/{task}/info', [TaskController::class, 'updateInfo'])->name('tasks.updateInfo');

    Route::resource('tasks', TaskController::class)->except([
        'index',
        'create',
        'store',
        'edit',
        'update'
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
    // BLOCO 3: REUNIOES
    // ==========================================
    Route::patch('projects/{project}/meetings/{meeting}/status', [MeetingController::class, 'updateStatus'])
        ->name('meetings.updateMeetingStatus');
    Route::patch('projects/{project}/meetings/{meeting}/notes', [MeetingController::class, 'updateMeetingNotes'])
        ->name('projects.meetings.updateNotes');
    Route::post('projects/{project}/meetings/{meeting}/items', [MeetingController::class, 'storeItem'])
        ->name('projects.meetings.items.store');
    Route::delete('projects/{project}/meetings/{meeting}/items/{meetingItem}', [MeetingController::class, 'destroyItem'])
        ->name('projects.meetings.items.destroy');
    Route::patch('projects/{project}/meetings/{meeting}/items/{meetingItem}/notes', [MeetingController::class, 'updateNotes'])
        ->name('projects.meetings.items.updateNotes');

    Route::resource('projects.meetings', MeetingController::class);
    Route::post('projects/{project}/meetings/{meeting}/items', [MeetingController::class, 'storeItem'])
        ->name('projects.meetings.items.store');



    // ==========================================
    // USUÁRIOS
    // ==========================================

    Route::resource('users', UserController::class)->except([
        'index',
        'create',
        'store',
        'edit',
        'update',
        'destroy'
    ]);

    // ==========================================
    // COMMENTS
    // ==========================================
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::patch('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // ==========================================
    // MENU / REDIRECTS
    // ==========================================
    Route::get('/meus-projetos', fn() => redirect()->route('projects.index'));
    Route::get('/minhas-tasks', fn() => redirect()->route('tasks.index'));
    Route::get('/meu-perfil', fn() => redirect()->route('users.show', Auth::id()));
});

// ==========================================
// ADMIN
// ==========================================
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::resource('admin', AdminController::class);
});

Route::fallback(fn() => abort(404));
