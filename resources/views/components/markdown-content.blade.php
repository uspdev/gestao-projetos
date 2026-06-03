@props([
    'text' => '',
    'escapeHtml' => false,
])

@php
  $content = (string) $text;
  $content = $escapeHtml ? e($content) : $content;
  $content = md2html($content);

  // target="_blank" e rel="noopener noreferrer" para links externos
  $content = preg_replace('/<a\s+([^>]*href=)/i', '<a target="_blank" rel="noopener noreferrer" $1', $content);
@endphp

{!! $content !!}
