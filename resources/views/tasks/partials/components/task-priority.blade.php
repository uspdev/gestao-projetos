@if ($priority = $task->priority)
  <span @class(['badge', $priority->color()])>{{ $priority->label() }}</span>
@else
  <span class="text-muted fst-italic small">-</span>
@endif
