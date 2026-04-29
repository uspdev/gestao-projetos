<div class="card mb-4 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
      <h4 class="m-0 text-dark font-weight-bold">{{ $task->title }}</h4>
      <div>
        @includeWhen(auth()->user()->can('update', $task), 'tasks.partials.edit', [
            'task' => $task,
        ])
        @can('delete', $task)
          <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="d-inline-block ml-1"
            onsubmit="return confirm('Deseja realmente excluir esta tarefa? Esta ação não pode ser desfeita.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
              <i class="fas fa-trash"></i>
            </button>
          </form>
        @endcan
      </div>
    </div>

    <div class="text-dark text-justify" style="font-size: 1.1rem; line-height: 1.6;">
      @if ($task->description)
        {!! nl2br(e($task->description)) !!}
      @else
        <div class="text-center text-muted p-5 bg-light rounded">
          <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
          <h5>Sem descrição</h5>
          <p class="mb-0">Nenhuma descrição foi fornecida para esta tarefa.</p>
        </div>
      @endif
    </div>
  </div>
</div>
