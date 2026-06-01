@if (! empty($url))
  <p>
    <a href="{{ $url }}">{{ $label ?? 'Ver detalhes' }}</a>
  </p>
@endif
