@php
  $showDone = $showDone ?? request()->boolean('show_done');
  $href = $showDone ? request()->fullUrlWithoutQuery('show_done') : request()->fullUrlWithQuery(['show_done' => 1]);
@endphp

<a href="{{ $href }}" class="btn btn-sm py-0 {{ $showDone ? 'btn-secondary' : 'btn-outline-secondary' }}"
  title="{{ $showDone ? 'Ocultar tarefas concluídas' : 'Mostrar tarefas concluídas' }}">
  <i class="fas {{ $showDone ? 'fa-eye-slash' : 'fa-eye' }}"></i>
  <span class="ml-1">{{ $showDone ? 'Ocultar concluídas' : 'Mostrar concluídas' }}</span>
</a>
