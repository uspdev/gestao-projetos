<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectTaskController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::resource('projects', ProjectController::class)
    ->middleware('auth');

Route::resource('tasks', TaskController::class)
    ->except('index','create','store')
    ->middleware('auth');

Route::resource('projects.tasks', ProjectTaskController::class)
    ->only('index','create','store')
    ->middleware('auth');

Route::get('/', function () {
    return ('welcome');
});

Route::fallback(function () {
    abort(404);
});
