@props(['title', 'type' => 'normal'])

@php
  $headerClass = 'py-2' . ' ';
  $headerClass .= $type === 'main' ? 'h5' : 'text-muted';
  $headerStyle = $type === 'main' ? 'background-color: lightcyan;' : '';
@endphp

<div {{ $attributes->class(['card', 'mb-4']) }}>
  <div class="card-header {{ $headerClass }}" style="{{ $headerStyle }}">
    {{ $header }}
  </div>

  <div class="card-body">
    {{ $slot }}
  </div>
</div>
