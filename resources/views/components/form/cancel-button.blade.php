@props([
    'label' => 'Cancelar',
])

<button
  {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-link text-danger mr-1 border hover-bg-danger hover-text-white', 'aria-label' => $label]) }}>
  <i class="fas fa-times" aria-hidden="true"></i>
  <span class="sr-only">{{ $label }}</span>
</button>
