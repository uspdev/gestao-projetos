@props(['text' => '', 'ariaLabel' => 'Conteúdo em Markdown'])
@inject('markdownRenderer', 'App\Services\MarkdownRenderer')

<div {{ $attributes->class(['markdown-content', 'overflow-auto', 'pr-2'])->merge([
    'aria-label' => $ariaLabel,
    'role' => 'region',
    'style' => 'max-height: min(60vh, 36rem); overscroll-behavior-y: contain;',
    'tabindex' => '0',
]) }}>{!! $markdownRenderer->render((string) $text) !!}</div>
