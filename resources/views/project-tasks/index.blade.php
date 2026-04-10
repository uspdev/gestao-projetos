@php
    $taskCardColumnClass = $taskCardColumnClass ?? 'col-md-6 col-lg-4';
@endphp

<div class="row">
    @forelse($tasks as $task)
        <div class="{{ $taskCardColumnClass }}">
            @include('partials.tasks.preview', ['task' => $task])
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-secondary text-center p-4 shadow-sm" role="alert">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h5 class="text-muted m-0">Nenhuma tarefa encontrada.</h5>
                <p class="text-muted mb-0 mt-2">
                    Clique em <strong>"Nova Task"</strong> acima para criar a primeira tarefa deste projeto.
                </p>
            </div>
        </div>
    @endforelse
</div>
