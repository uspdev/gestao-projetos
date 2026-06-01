@props([
    'label' => 'Salvar',
])

<button {{ $attributes->merge(['type' => 'submit', 'aria-label' => $label]) }}>
  <i class="fas fa-save" aria-hidden="true"></i>
  <span class="sr-only">{{ $label }}</span>
</button>
