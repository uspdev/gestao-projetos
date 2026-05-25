@php
  $discussable = $item->discussable;
  $title = 'Item removido';
  $link = null;
  $typeLabel = 'Outro';

  if ($discussable) {
      $morphClass = $discussable->getMorphClass();

      $typeLabel = match ($morphClass) {
          'project' => 'Projeto',
          'task' => 'Tarefa',
          default => ucfirst($morphClass),
      };

      $title = $discussable->title ?? ($discussable->name ?? "Item #{$discussable->id}");

      $link = match ($morphClass) {
          'task' => route('tasks.show', $discussable),
          'project' => route('projects.show', $discussable),
          default => null,
      };
  }
@endphp

<li class="list-group-item px-0 py-1">
  <div class="d-flex align-items-start justify-content-between gap-3">
    <div class="d-flex align-items-start" style="gap: 0.75rem;">
      <div class="badge badge-light border text-muted px-2 py-2">#{{ $item->order }}</div>

      <div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
          <span class="badge badge-light border text-dark">{{ $typeLabel }}</span>

          @if ($link)
            <a href="{{ $link }}" class="text-decoration-none font-weight-bold text-dark">
              {{ $title }}
            </a>
          @else
            <span class="font-weight-bold text-muted">{{ $title }}</span>
          @endif
        </div>
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
  </div>
</li>
