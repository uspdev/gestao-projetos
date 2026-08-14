<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarkdownCdnAssetsTest extends TestCase
{
    private const EASYMDE_CSS = 'https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.css';
    private const EASYMDE_JS = 'https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.js';
    private const HIGHLIGHT_CSS = 'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/styles/github.min.css';
    private const HIGHLIGHT_JS = 'https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/highlight.min.js';

    public function test_layout_loads_approved_markdown_cdn_assets_before_application_javascript(): void
    {
        $html = preg_replace(
            '/\s+/',
            ' ',
            $this->get(route('about'))
                ->assertOk()
                ->getContent()
        );

        $this->assertStringContainsString(
            '<link rel="stylesheet" href="'.self::EASYMDE_CSS.'" integrity="sha384-3AvV7152TgYAMYdGZPqG9BpmSH2ZW6ewTDL0QV5PyNkl19KMI+yLMdJz183N8A2d" crossorigin="anonymous">',
            $html
        );
        $this->assertStringContainsString(
            '<link rel="stylesheet" href="'.self::HIGHLIGHT_CSS.'" integrity="sha384-eFTL69TLRZTkNfYZOLM+G04821K1qZao/4QLJbet1pP4tcF+fdXq/9CdqAbWRl/L" crossorigin="anonymous">',
            $html
        );
        $this->assertStringContainsString(
            '<script src="'.self::EASYMDE_JS.'" integrity="sha384-YDXeUfPZ4SP6vJpnF+ZMmf4B1bax6yd4Q/aNbkvLidRD843hPG5RE67M0IYT4LOq" crossorigin="anonymous"></script>',
            $html
        );
        $this->assertStringContainsString(
            '<script src="'.self::HIGHLIGHT_JS.'" integrity="sha384-RH2xi4eIQ/gjtbs9fUXM68sLSi99C7ZWBRX1vDrVv6GQXRibxXLbwO2NGZB74MbU" crossorigin="anonymous"></script>',
            $html
        );

        $this->assertStringContainsString(
            '<script type="module" src="'.asset('js/app.js').'"></script>',
            $html,
        );
        $this->assertStringNotContainsString(asset('css/markdown.css'), $html);
        $this->assertStringNotContainsString(asset('css/files.css'), $html);
        $this->assertLessThan(strpos($html, asset('js/app.js')), strpos($html, self::EASYMDE_JS));
        $this->assertLessThan(strpos($html, asset('js/app.js')), strpos($html, self::HIGHLIGHT_JS));
    }
}
