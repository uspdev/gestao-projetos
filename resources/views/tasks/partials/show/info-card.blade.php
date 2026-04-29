<div class="card mb-4 shadow-sm border-top-primary">
  <div class="card-header bg-white py-3">
    <span class="text-muted font-weight-bold">Informações</span>
  </div>
  <div class="card-body p-3">
    <ul class="list-unstyled m-0">
      <li class="mb-3 border-bottom pb-2">
        <div class="row no-gutters">
          <div class="col-6 border-right pr-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Prioridade:</span>
            @if ($task->priority instanceof \App\Enums\Task\TaskPriority)
              <span class="badge {{ $task->priority->color() }}">{{ $task->priority->label() }}</span>
            @else
              <span class="text-muted font-italic small">-</span>
            @endif
          </div>
          <div class="col-6 pl-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Tags:</span>
            @php
              $tags = $task->tags;
              $visible = $tags->slice(0, 5);
              $hidden = max(0, $tags->count() - $visible->count());
            @endphp
            <div class="d-flex flex-wrap" style="max-width:100%; gap:6px;">
              @forelse($visible as $tag)
                <span class="badge {{ $tag->color }} small mb-1">
                  <i class="fas fa-tag mr-1"></i>{{ \Illuminate\Support\Str::limit($tag->name, 18) }}
                </span>
              @empty
                <span class="text-muted font-italic small">-</span>
              @endforelse
              @if ($hidden > 0)
                <span class="badge bg-secondary small mb-1">+{{ $hidden }}</span>
              @endif
            </div>
          </div>
        </div>
      </li>

      <li class="mb-0">
        <div class="row no-gutters">
          <div class="col-6 border-right pr-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Início:</span>
            <span class="font-weight-bold">
              @if ($task->start_date)
                <time class="local-date"
                  datetime="{{ $task->start_date->format('Y-m-d') }}">{{ $task->start_date->format('Y-m-d') }}</time>
              @else
                --/--/----
              @endif
            </span>
          </div>
          <div class="col-6 pl-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Prazo:</span>
            <span
              class="font-weight-bold {{ $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status->value !== \App\Enums\Task\TaskStatus::DONE->value ? 'text-danger' : 'text-dark' }}">
              @if ($task->due_date)
                <time class="local-date"
                  datetime="{{ $task->due_date->format('Y-m-d') }}">{{ $task->due_date->format('Y-m-d') }}</time>
              @else
                --/--/----
              @endif
            </span>
          </div>
        </div>
      </li>
    </ul>
  </div>
</div>
