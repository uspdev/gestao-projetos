<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('api.guide'))->name('api.guide');

// ==========================================
// PROJETOS
// ==========================================
Route::prefix('projects/{project}')->name('api.projects.')->group(function () {
    Route::get('', [ProjectController::class, 'show'])
        ->middleware('uspdevApiKeys:projects.read')
        ->name('show');

    // ==========================================
    // REUNIÕES
    // ==========================================
    Route::prefix('meetings')->name('meetings.')->middleware('uspdevApiKeys:meetings.read')->group(function () {
        Route::get('', [MeetingController::class, 'index'])->name('index');
        Route::get('{meeting}', [MeetingController::class, 'show'])
            ->whereNumber('meeting')
            ->name('show');
    });

    // ==========================================
    // TAREFAS
    // ==========================================
    Route::prefix('tasks')->name('tasks.')->middleware('uspdevApiKeys:tasks.read')->group(function () {
        Route::get('', [TaskController::class, 'index'])->name('index');
        Route::get('{task}', [TaskController::class, 'show'])
            ->whereNumber('task')
            ->name('show');
    });

    // ==========================================
    // ARQUIVOS
    // ==========================================
    Route::prefix('files')->name('files.')->middleware('uspdevApiKeys:files.read')->group(function () {
        Route::get('', [FileController::class, 'index'])->name('index');
        Route::get('{uuid}', [FileController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
});

Route::fallback(function (Request $request) {
    if ($request->expectsJson()) {
        return response()->json([
            'message' => 'Not Found',
            'documentation_url' => route('api.guide'),
        ], 404);
    }

    return response()->view('api.guide', ['notFound' => true], 404);
});
