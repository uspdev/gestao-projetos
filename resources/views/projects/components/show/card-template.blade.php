@props(['title', 'type' => 'normal', 'surface' => null])

@php
  $headerClass = 'py-2' . ' ';
  $headerClass .= $type === 'main' ? 'h5' : 'text-muted';
  $surface ??= $type === 'main' ? 'content' : 'options';
@endphp

<div {{ $attributes->class(['card', $surface === 'content' ? 'content-surface' : 'options-surface', 'mb-4']) }}>
  <div class="card-header {{ $headerClass }}">
    {{ $header }}
  </div>

  <div class="card-body">
    {{ $slot }}
  </div>
</div>
