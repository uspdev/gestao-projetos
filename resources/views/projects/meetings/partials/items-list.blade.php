@php
  $meetingItems = $meetingItems ?? collect();
  $meeting = $meeting ?? null;
  $project = $project ?? null;
  $canRemove = $meeting && $project && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 d-flex align-items-center justify-content-between">
    <span><i class="fas fa-list-ul mr-1"></i> Itens de pauta</span>
    <span class="badge badge-secondary">{{ $meetingItems->count() }}</span>
  </div>
  <div class="card-body">
    @if ($meetingItems->isEmpty())
      <div class="text-center text-muted p-4 bg-light rounded border">
        <i class="fas fa-clipboard-list fa-2x mb-3 text-secondary"></i>
        <div class="font-weight-bold mb-1">Nenhum item cadastrado</div>
        <div>Adicione projetos ou tarefas para montar a pauta da reunião.</div>
      </div>
    @else
      <ul class="list-group list-group-flush">
        @foreach ($meetingItems as $item)
          @php
            $discussable = $item->discussable;
            $title = 'Item removido';
            $link = null;
            $typeLabel = 'Outro';

            if ($discussable) {
                $morphClass = $discussable->getMorphClass();

                $typeLabel = match ($morphClass) {
                    'project' => 'Projeto',
                    'task'    => 'Tarefa',
                    default   => ucfirst($morphClass),
                };

                $title = $discussable->title ?? $discussable->name ?? "Item #{$discussable->id}";

                $link = match ($morphClass) {
                    'task'    => route('tasks.show', $discussable),
                    'project' => route('projects.show', $discussable),
                    default   => null,
                };
            }
          @endphp
          <li class="list-group-item px-0 py-3">
            <div class="d-flex align-items-start justify-content-between gap-3">
              <div class="d-flex align-items-start" style="gap: 0.75rem;">
                <div class="badge badge-light border text-muted px-2 py-2">#{{ $item->order }}</div>

                <div>
                  <div class="d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
                    <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>

                    @if ($link)
                      <a href="{{ $link }}" class="text-decoration-none font-weight-bold text-dark">
                        {{ $title }}
                      </a>
                    @else
                      <span class="font-weight-bold text-muted">{{ $title }}</span>
                    @endif
                  </div>

                  <div class="text-muted small mt-1">
                    {{ $subtitle }}
                  </div>
                </div>
              </div>

              @if ($canRemove)
                @can('update', [$meeting, $project])
                  <form method="POST"
                    action="{{ route('projects.meetings.items.destroy', [$project, $meeting, $item]) }}"
                    class="d-inline-block" onsubmit="return confirm('Deseja remover este item de pauta?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                @endcan
              @endif
            </div>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
