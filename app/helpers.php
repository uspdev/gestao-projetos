<?php

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
