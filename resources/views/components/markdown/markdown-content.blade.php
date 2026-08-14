@props(['text' => '', 'ariaLabel' => 'Conteúdo em Markdown'])
@inject('markdownRenderer', 'App\Services\MarkdownRenderer')

@include('components.markdown.markdown-css')

<div {{ $attributes->class(['markdown-content'])->merge([
    'aria-label' => $ariaLabel,
    'role' => 'region',
    'tabindex' => '0',
]) }}>{!! $markdownRenderer->render((string) $text) !!}</div>
