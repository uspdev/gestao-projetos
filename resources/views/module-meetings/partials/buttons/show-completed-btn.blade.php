@php
  $showCompleted = $showCompleted ?? request()->boolean('show_completed');
  $href = $showCompleted
      ? request()->fullUrlWithoutQuery('show_completed')
      : request()->fullUrlWithQuery(['show_completed' => 1]);
@endphp

<a href="{{ $href }}" class="btn btn-sm py-0 {{ $showCompleted ? 'btn-secondary' : 'btn-outline-secondary' }}"
  title="{{ $showCompleted ? 'Ocultar reunioes concluidas' : 'Mostrar reunioes concluidas' }}">
  <i class="fas {{ $showCompleted ? 'fa-eye-slash' : 'fa-eye' }}"></i>
  <span class="ml-1">{{ $showCompleted ? 'Ocultar concluidas' : 'Mostrar concluidas' }}</span>
</a>
