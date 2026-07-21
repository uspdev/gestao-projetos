@props(['text' => ''])
@inject('markdownRenderer', 'App\Services\MarkdownRenderer')

<div class="markdown-content">{!! $markdownRenderer->render((string) $text) !!}</div>
