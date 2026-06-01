@php
  $tasksMine = session('tasks_mine', '0');
  $href = $tasksMine
      ? request()->fullUrlWithQuery(['tasks_mine' => 0])
      : request()->fullUrlWithQuery(['tasks_mine' => 1]);
@endphp
<a href="{{ $href }}" class="btn btn-sm py-0 {{ $tasksMine ? 'btn-secondary' : 'btn-outline-secondary' }}"
  title="{{ $tasksMine ? 'Mostrar todas as tarefas' : 'Mostrar apenas minhas tarefas' }}">
  <i class="fas {{ $tasksMine ? 'fa-users' : 'fa-user' }}"></i>
</a>
