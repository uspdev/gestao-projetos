@props([
    'target',
    'cardTitle' => null,
    'emptyMessage' => 'Nenhuma menção encontrada.',
])

@php
  $mentions = app(\App\Services\Mentions\MentionBacklinks::class)->for($target, auth()->user());
  $mentionGroups = $mentions
    ->groupBy('group_key')
    ->map(function ($group) {
      $first = $group->first();

      return [
        'label' => $first['group_label'],
        'type' => $first['group_type'],
        'url' => $first['group_url'],
        'fields' => $group->pluck('field')->unique()->values(),
      ];
    })
    ->values();
  $locationCount = $mentions->count();
  $itemCount = $mentionGroups->count();
  $locationLabel = $locationCount === 1 ? 'local' : 'locais';
  $itemLabel = $itemCount === 1 ? 'item' : 'itens';
  $summary = $locationCount > 0
    ? "{$locationCount} {$locationLabel} em {$itemCount} {$itemLabel}"
    : 'Nenhum local';
  $resolvedCardTitle = $cardTitle ?? match (true) {
    $target instanceof \App\Models\User => 'Onde você foi mencionado',
    $target instanceof \App\Models\Project => 'Onde este projeto foi mencionado',
    $target instanceof \App\Models\Task => 'Onde esta tarefa foi mencionada',
    $target instanceof \App\Models\Meeting => 'Onde esta reunião foi mencionada',
    default => 'Onde foi mencionado',
  };
@endphp

@pushOnce('styles')
  <style>
    .mentions-card__list {
      max-height: 22rem;
      overflow-y: auto;
    }

    .mentions-card__group + .mentions-card__group {
      border-top: 1px solid #e9ecef;
      margin-top: 1rem;
      padding-top: 1rem;
    }

    .mentions-card__group-link {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      min-width: 0;
    }

    .mentions-card__group-icon {
      color: #6c757d;
      flex: 0 0 1rem;
      margin-top: 0.2rem;
      text-align: center;
    }

    .mentions-card__group-fields {
      list-style: none;
      margin: 0.35rem 0 0 1.5rem;
      padding: 0;
    }

    .mentions-card__group-field {
      color: #6c757d;
      font-size: 0.875rem;
    }

    .mentions-card__group-field i {
      font-size: 0.7rem;
      margin-right: 0.35rem;
    }
  </style>
@endPushOnce

<div {{ $attributes->class(['card', 'mb-4', 'shadow-sm']) }} data-mentions-card="{{ $target->getMorphClass() }}-{{ $target->getKey() }}">
  <div class="card-header py-2 d-flex align-items-center justify-content-between">
    <div>
      <h6 class="m-0 text-muted">
        <i class="fas fa-at mr-1" aria-hidden="true"></i> {{ $resolvedCardTitle }}
      </h6>
      <div class="small text-muted mt-1">{{ $summary }}</div>
    </div>
    <span class="badge badge-pill badge-secondary text-nowrap">{{ $locationCount }} {{ $locationLabel }}</span>
  </div>

  <div class="card-body p-0">
    <div class="mentions-card__list" tabindex="0" aria-label="{{ $resolvedCardTitle }}">
      <div class="p-3">
        @forelse ($mentionGroups as $group)
          @php
            $groupIcon = match ($group['type']) {
              'Projeto' => 'fa-project-diagram',
              'Tarefa' => 'fa-tasks',
              'Reunião' => 'fa-calendar-alt',
              default => 'fa-list-ul',
            };
            $commentContext = match ($group['type']) {
              'Projeto' => 'neste projeto',
              'Tarefa' => 'nesta tarefa',
              'Reunião' => 'nesta reunião',
              default => 'neste item',
            };
          @endphp
          <div class="mentions-card__group">
            <a href="{{ $group['url'] }}" class="mentions-card__group-link text-dark text-decoration-none">
              <i class="fas {{ $groupIcon }} mentions-card__group-icon" aria-hidden="true"></i>
              <span class="font-weight-bold text-break">{{ $group['label'] }}</span>
            </a>
            <div class="small text-muted ml-4">{{ $group['type'] }}</div>
            <ul class="mentions-card__group-fields" aria-label="Locais da Menção">
              @foreach ($group['fields'] as $field)
                <li class="mentions-card__group-field">
                  <i class="fas {{ $field === 'Comentário' ? 'fa-comment' : 'fa-file-alt' }}" aria-hidden="true"></i>
                  {{ $field === 'Comentário' ? 'Comentário ' . $commentContext : $field }}
                </li>
              @endforeach
            </ul>
          </div>
        @empty
          <div class="text-muted">{{ $emptyMessage }}</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
