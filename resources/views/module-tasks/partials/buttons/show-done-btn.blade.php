@php
  $tasksDone = session('tasks_done');
  $href = $tasksDone
      ? request()->fullUrlWithQuery(['tasks_done' => 0])
      : request()->fullUrlWithQuery(['tasks_done' => 1]);
@endphp
<a href="{{ $href }}" class="btn btn-sm py-0 {{ $tasksDone ? 'btn-secondary' : 'btn-outline-secondary' }}"
  title="{{ $tasksDone ? 'Ocultar tarefas concluídas' : 'Mostrar tarefas concluídas' }}">
  <i class="fas {{ $tasksDone ? 'fa-eye-slash' : 'fa-eye' }}"></i>
  <span class="ml-1">{{ $tasksDone ? 'Ocultar concluídas' : 'Mostrar concluídas' }}</span>
</a>
