<?php

use App\Support\Navigation\DeepLink;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('deep_link')) {
    /**
     * Gera a URL de uma rota com o fragmento canônico da entidade de destino.
     *
     * Use apenas quando o destino for um elemento interno diferente do recurso
     * identificado pela rota, como um Comentário ou Item de pauta.
     */
    function deep_link(
        \BackedEnum|string $name,
        mixed $parameters,
        Model $target,
        bool $absolute = true,
    ): string {
        return DeepLink::route($name, $parameters, $target, $absolute);
    }
}

if (! function_exists('deep_link_fragment')) {
    /**
     * Retorna o fragmento canônico usado para localizar uma entidade na tela.
     */
    function deep_link_fragment(Model $model): string
    {
        return DeepLink::fragment($model);
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
