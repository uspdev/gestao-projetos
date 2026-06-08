@props([
    'text' => '',
    'escapeHtml' => false,
    'renderMarkdown' => true,
])

@php
  $content = (string) $text;

  if ($renderMarkdown) {
      $content = $escapeHtml ? e($content) : $content;
      $content = md2html($content);

      // target="_blank" e rel="noopener noreferrer" para links externos
      $content = preg_replace('/<a\s+([^>]*href=)/i', '<a target="_blank" rel="noopener noreferrer" $1', $content);
  } else {
      $content = text2html($content, $escapeHtml);
  }
@endphp

{!! $content !!}
