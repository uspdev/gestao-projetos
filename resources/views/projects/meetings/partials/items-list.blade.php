@php
  $meetingItems = $meetingItems ?? collect();
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 d-flex align-items-center justify-content-between">
    <span><i class="fas fa-list-ul mr-1"></i> Itens de pauta</span>
    <span class="badge badge-secondary">{{ $meetingItems->count() }}</span>
  </div>
  <div class="card-body">
    @if ($meetingItems->isEmpty())
      <div class="alert alert-light border text-muted mb-0">Nenhum item cadastrado.</div>
    @else
      <ul class="list-group list-group-flush">
        @foreach ($meetingItems as $item)
          @php
            $discussable = $item->discussable;
            $isTask = $discussable instanceof \App\Models\Task;
            $isProject = $discussable instanceof \App\Models\Project;
            $title = $isTask ? $discussable->title : ($isProject ? $discussable->name : 'Item removido');
            $link = $isTask
                ? route('tasks.show', $discussable)
                : ($isProject
                    ? route('projects.show', $discussable)
                    : null);
            $typeLabel = $isTask ? 'Tarefa' : ($isProject ? 'Projeto' : 'Outro');
          @endphp
          <li class="list-group-item d-flex align-items-start justify-content-between">
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge badge-light border text-muted">#{{ $item->order }}</span>
                <span class="badge badge-secondary">{{ $typeLabel }}</span>
              </div>
              <div class="mt-2">
                @if ($link)
                  <a href="{{ $link }}" class="text-decoration-none font-weight-bold">{{ $title }}</a>
                @else
                  <span class="text-muted">{{ $title }}</span>
                @endif
              </div>
            </div>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
