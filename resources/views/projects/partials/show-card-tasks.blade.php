{{-- Index: Lista de Tasks --}}
<div class="card mb-4 shadow-sm">


  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <a href="{{ route('projects.tasks.index', $project) }}" class="ms-3">
      <i class="fas fa-tasks mr-1"></i> Tarefas
    </a>
    @include('tasks.partials.create-task-btn', [
        'availableTags' => \App\Models\Tag::withType('tasks')->orderBy('name')->get(),
    ])
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered datatable-simples">
        <thead>
          <tr>
            <th></th>
            <th>Título</th>
            <th>Status</th>
            <th>Prioridade</th>
            <th>Tags</th>
            <th>Criado em</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($project->tasks as $task)
            <tr>
              <td>
                @include('tasks.partials.edit')
              </td>
              <td>
                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none">
                  {{ $task->title }}
                </a>
              </td>
              <td>
                <span class="badge {{ $task->status->color() }}">
                  {{ $task->status->label() }}
                </span>
              </td>
              <td>
                <span class="badge {{ $task->priority?->color() }}">
                  {{ $task->priority?->label() }}
                </span>
              </td>
              <td>
                @foreach ($task->tags as $tag)
                  <span class="badge badge-secondary">
                    {{ $tag->name }}
                  </span>
                @endforeach
              </td>
              <td data-order="{{ $task->created_at?->format('Y-m-d H:i:s') }}">
                {{ $task->created_at?->format('d/m/Y') ?? '-' }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
