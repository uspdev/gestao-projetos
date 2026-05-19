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
      <div class="alert alert-light border text-muted mb-0">Nenhum item cadastrado.</div>
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
            @if ($canRemove)
              @can('update', [$meeting, $project])
                <form method="POST" action="{{ route('projects.meetings.items.destroy', [$project, $meeting, $item]) }}"
                  class="d-inline-block" onsubmit="return confirm('Deseja remover este item de pauta?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-outline-danger btn-sm py-0">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              @endcan
            @endif
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</div>