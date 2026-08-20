@php
  $generalWatchPreferences ??= collect();
  $watchGroups = [
      'project' => [
          'label' => 'Projetos',
          'empty' => 'Nenhum projeto acompanhado.',
          'icon' => 'fas fa-folder-open',
      ],
      'task' => [
          'label' => 'Tarefas',
          'empty' => 'Nenhuma tarefa acompanhada.',
          'icon' => 'fas fa-tasks',
      ],
      'meeting' => [
          'label' => 'Reuniões',
          'empty' => 'Nenhuma reunião acompanhada.',
          'icon' => 'fas fa-calendar-alt',
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
      Projetos, tarefas, reuniões e preferências gerais que podem gerar notificações para você.
    </p>
  </div>

  <div class="row">
    @if ($watchedResources->isEmpty())
      <div class="col-12 mb-3">
        <div class="alert alert-light border mb-0">
          Você não está acompanhando nenhum projeto, tarefa ou reunião.
        </div>
      </div>
    @else
      @foreach ($watchGroups as $type => $group)
        @php
          $items = $watchedResources->where('type', $type)->values();
        @endphp

        <div class="col-md-6 col-xl-4 mb-3">
          <div class="card h-100 watch-card watch-card--{{ $type }}">
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
    @endif

    <div class="col-md-6 col-xl-4 mb-3">
      <div class="card h-100 watch-card watch-card--general">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <h6 class="mb-0">
            <i class="fas fa-sliders-h text-secondary mr-1" aria-hidden="true"></i>
            Preferências gerais
          </h6>
        </div>

        <div class="card-body py-3">
          <p class="text-muted small mb-3">
            Escolha outros tipos de acompanhamento que poderão ser adicionados aqui.
          </p>

          @forelse ($generalWatchPreferences as $preference)
            <div class="d-flex align-items-start justify-content-between mb-{{ $loop->last ? '0' : '3' }}">
              <div class="mr-2">
                <strong class="d-block">{{ $preference['label'] }}</strong>
                <span class="small text-muted">{{ $preference['description'] }}</span>
              </div>

              <form method="POST"
                action="{{ $preference['active']
                    ? route('watches.destroy', [$preference['type'], $preference['watchable_id']])
                    : route('watches.update', [$preference['type'], $preference['watchable_id']]) }}"
                class="flex-shrink-0">
                @csrf
                @method($preference['active'] ? 'DELETE' : 'PUT')
                <button type="submit"
                  class="btn btn-sm {{ $preference['active'] ? 'btn-outline-secondary' : 'btn-outline-primary' }}"
                  title="{{ $preference['active'] ? 'Desativar' : 'Ativar' }} {{ $preference['label'] }}"
                  aria-label="{{ $preference['active'] ? 'Desativar' : 'Ativar' }} {{ $preference['label'] }}"
                  aria-pressed="{{ $preference['active'] ? 'true' : 'false' }}">
                  <i class="fas fa-bell{{ $preference['active'] ? '' : '-slash' }}" aria-hidden="true"></i>
                </button>
              </form>
            </div>
          @empty
            <span class="small text-muted">Nenhuma preferência geral disponível.</span>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</section>
