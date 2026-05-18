@props([
    'text' => '',
    'escapeHtml' => true,
])

@php
  $content = (string) $text;

  if ($escapeHtml) {
      $content = e($content);
  }
@endphp

{!! md2html($content) !!}
