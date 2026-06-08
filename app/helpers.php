<?php

//coloque o seu helper aqui

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;

if (!function_exists('md2html')) {
    /**
     * Converte markdown para html (github flavored)
     *
     * @param String $markdown
     * @param String $style Estido do CSS (default=default.css)
     * @return String
     * @author Masakik, em 16/11/2022
     */
    function md2html($markdown, $style = 'default.css')
    {
        $environment = new Environment();
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addRenderer(FencedCode::class, new FencedCodeRenderer());
        $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer());

        $markdownConverter = new MarkdownConverter($environment);

        $html = '<style>' . file_get_contents(base_path('vendor/scrivo/highlight.php/styles/' . $style)) . '</style>';
        $html .= $markdownConverter->convertToHtml($markdown);
        return $html;
    }
}

if (!function_exists('text2html')) {
    /**
     * Converte texto puro em HTML, preservando quebras de linha e tornando URLs clicáveis.
     *
     * @param string|null $text
     * @param bool $escapeHtml
     * @return string
     */
    function text2html($text, $escapeHtml = true)
    {
        $content = (string) $text;
        $content = $escapeHtml ? e($content) : $content;

        $content = preg_replace_callback(
            '/(?<![\w@])(https?:\/\/[^\s<>"\']+)/i',
            function (array $matches) use ($escapeHtml) {
                $url = $matches[1];
                $trailing = '';

                while ($url !== '' && preg_match('/[.,;:!?)]$/', $url)) {
                    $trailing = substr($url, -1) . $trailing;
                    $url = substr($url, 0, -1);
                }

                $safeUrl = $escapeHtml ? $url : e($url);

                return '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer">' . $safeUrl . '</a>' . $trailing;
            },
            $content
        );

        return nl2br($content);
    }
}
