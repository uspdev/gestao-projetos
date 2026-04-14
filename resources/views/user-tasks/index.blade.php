@extends('layouts.app')

@section('title', 'Minhas Tarefas')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Minhas Tarefas</h2>
        </div>

        <div class="row">
            @forelse($tasks as $task)
                <div class="col-md-6 col-lg-4 mb-2">
                    @include('partials.tasks.preview', ['task' => $task])
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">Você ainda não possui tarefas atribuídas.</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
