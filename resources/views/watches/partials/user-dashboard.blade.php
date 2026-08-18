@php
  $watchGroups = [
      'project' => [
          'label' => 'Projetos',
          'empty' => 'Nenhum projeto acompanhado.',
          'icon' => 'fas fa-folder-open',
          'surface' => 'blue',
      ],
      'task' => [
          'label' => 'Tarefas',
          'empty' => 'Nenhuma tarefa acompanhada.',
          'icon' => 'fas fa-tasks',
          'surface' => 'gray',
      ],
      'meeting' => [
          'label' => 'Reuniões',
          'empty' => 'Nenhuma reunião acompanhada.',
          'icon' => 'fas fa-calendar-alt',
          'surface' => 'steel',
      ],
  ];
@endphp

@pushOnce('styles')
  <style>
    .watch-dashboard-list {
      max-height: 22rem;
      overflow-y: auto;
    }
  </style>
@endPushOnce

<section id="user-watches" class="mb-4" aria-labelledby="user-watches-title">
  <div class="mb-3">
    <h4 id="user-watches-title" class="mb-1">
      <i class="fas fa-bell text-secondary" aria-hidden="true"></i>
      Acompanhamentos
    </h4>
    <p class="text-muted mb-0">
      Projetos, tarefas e reuniões cujas atividades podem gerar notificações para você.
    </p>
  </div>

  @if ($watchedResources->isEmpty())
    <div class="alert alert-light border mb-0">
      Você não está acompanhando nenhum projeto, tarefa ou reunião.
    </div>
  @else
    <div class="row">
      @foreach ($watchGroups as $type => $group)
        @php
          $items = $watchedResources->where('type', $type)->values();
        @endphp

        <div class="col-md-6 col-xl-4 mb-3">
          <div class="card app-card app-card--{{ $group['surface'] }} h-100">
            <div class="card-header d-flex align-items-center justify-content-between py-2">
              <h6 class="mb-0">
                <i class="{{ $group['icon'] }} text-secondary mr-1" aria-hidden="true"></i>
                {{ $group['label'] }}
              </h6>
              <span class="badge badge-light border" aria-label="{{ $items->count() }} itens">
                {{ $items->count() }}
              </span>
            </div>

            @if ($items->isEmpty())
              <div class="card-body py-3 text-muted small">
                {{ $group['empty'] }}
              </div>
            @else
              <div class="list-group list-group-flush watch-dashboard-list" tabindex="0"
                aria-label="Acompanhamentos: {{ $group['label'] }}">
                @foreach ($items as $item)
                  <div class="list-group-item d-flex align-items-center justify-content-between py-2">
                    <div class="text-truncate mr-2" style="min-width: 0;">
                      <a href="{{ $item['url'] }}" class="d-block font-weight-bold text-dark text-truncate">
                        {{ $item['label'] }}
                      </a>
                      @if ($item['context'])
                        <span class="d-block small text-muted text-truncate">
                          <i class="fas fa-folder-open mr-1" aria-hidden="true"></i>
                          {{ $item['context'] }}
                        </span>
                      @endif
                    </div>

                    <form method="POST"
                      action="{{ route('watches.destroy', [$item['resource']->getMorphClass(), $item['resource']->getKey()]) }}"
                      class="flex-shrink-0">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-secondary"
                        title="Deixar de acompanhar {{ $item['label'] }}"
                        aria-label="Deixar de acompanhar {{ $item['label'] }}">
                        <i class="fas fa-bell-slash" aria-hidden="true"></i>
                      </button>
                    </form>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif
</section>
