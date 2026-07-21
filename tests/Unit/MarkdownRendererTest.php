<?php

namespace Tests\Unit;

use App\Services\MarkdownRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MarkdownRendererTest extends TestCase
{
    public function test_it_returns_an_empty_string_for_empty_content(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame('', $renderer->render(null));
    }

    public function test_it_renders_github_flavored_markdown(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame(
            "<table>\n<thead>\n<tr>\n<th>Nome</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>Projeto</td>\n</tr>\n</tbody>\n</table>\n",
            $renderer->render("| Nome |\n| --- |\n| Projeto |")
        );
    }

    public function test_it_opens_allowed_links_in_a_new_tab_safely(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame(
            "<p><a href=\"https://example.test/path\" target=\"_blank\" rel=\"noopener noreferrer\">Exemplo</a></p>\n",
            $renderer->render('[Exemplo](https://example.test/path)')
        );
    }

    #[DataProvider('unsafeUrlProvider')]
    public function test_it_does_not_render_links_for_disallowed_url_schemes(string $url): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame("<p>Risco</p>\n", $renderer->render("[Risco]({$url})"));
    }

    public static function unsafeUrlProvider(): array
    {
        return [
            'mailto' => ['mailto:user@example.test'],
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html;base64,PHNjcmlwdD4='],
            'outro esquema' => ['ftp://example.test/file'],
        ];
    }

    public function test_it_degrades_markdown_images_to_safe_links(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame(
            "<p><a href=\"https://example.test/image.png\" target=\"_blank\" rel=\"noopener noreferrer\">Diagrama</a></p>\n",
            $renderer->render('![Diagrama](https://example.test/image.png)')
        );
    }

    public function test_it_does_not_create_nested_links_when_an_image_is_already_linked(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame(
            "<p><a href=\"https://outer.test\" target=\"_blank\" rel=\"noopener noreferrer\">Diagrama</a></p>\n",
            $renderer->render('[![Diagrama](https://image.test/image.png)](https://outer.test)')
        );
    }

    public function test_it_escapes_raw_html_and_event_handlers(): void
    {
        $renderer = new MarkdownRenderer();

        $html = $renderer->render('<img src=x onerror="alert(1)"><script>alert(2)</script>');

        $this->assertSame(
            "<p>&lt;img src=x onerror=\"alert(1)\"&gt;&lt;script&gt;alert(2)&lt;/script&gt;</p>\n",
            $html
        );
    }

    #[DataProvider('allowedUrlProvider')]
    public function test_it_allows_relative_anchor_http_and_https_urls(string $url): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertStringContainsString('href="' . $url . '"', $renderer->render("[Destino]({$url})"));
    }

    public static function allowedUrlProvider(): array
    {
        return [
            'relativa' => ['/projects/1'],
            'âncora' => ['#agenda'],
            'http' => ['http://example.test'],
            'https' => ['https://example.test'],
        ];
    }

    public function test_it_does_not_make_an_unsafe_image_clickable(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame("<p>Risco</p>\n", $renderer->render('![Risco](data:image/svg+xml;base64,PHN2Zz4=)'));
    }

    public function test_it_emits_language_classes_without_server_side_highlighting(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame(
            "<pre><code class=\"language-php\">echo '&lt;seguro&gt;';\n</code></pre>\n",
            $renderer->render("```php\necho '<seguro>';\n```")
        );
    }

    public function test_it_limits_block_nesting_to_twenty_levels(): void
    {
        $renderer = new MarkdownRenderer();
        $markdown = str_repeat('> ', 25) . 'conteúdo';

        $html = $renderer->render($markdown);

        $this->assertSame(20, substr_count($html, '<blockquote>'));
    }
}
