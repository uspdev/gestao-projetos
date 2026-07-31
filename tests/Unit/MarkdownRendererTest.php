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

    public function test_it_keeps_same_page_anchors_in_the_current_tab(): void
    {
        $renderer = new MarkdownRenderer();

        $this->assertSame(
            "<p><a href=\"#agenda\">Agenda</a></p>\n",
            $renderer->render('[Agenda](#agenda)')
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

    public function test_it_hides_an_unavailable_mentioned_user_without_a_link(): void
    {
        $renderer = new MarkdownRenderer(fn (string $type, string $key): array => [
            'status' => 'missing',
            'type' => 'usuário',
            'message' => 'Menção a usuário: destino não encontrado',
        ]);

        $this->assertSame(
            "<p>Menção a usuário: destino não encontrado</p>\n",
            $renderer->render('@[Nome histórico](mention:user:42)')
        );
    }

    public function test_it_uses_the_current_name_and_profile_link_for_an_available_mentioned_user(): void
    {
        $renderer = new MarkdownRenderer(fn (string $type, string $key): array => [
            'status' => 'available',
            'type' => 'usuário',
            'label' => 'Nome atual',
            'url' => '/users/42',
            'accessible_name' => 'usuário: Nome atual',
        ]);

        $this->assertSame(
            "<p>@<a href=\"/users/42\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"markdown-mention\" aria-label=\"usuário: Nome atual\" title=\"usuário: Nome atual\">Nome atual</a></p>\n",
            $renderer->render('@[Nome histórico](mention:user:42)')
        );
    }

    public function test_it_exposes_the_current_user_type_in_the_accessible_name_and_tooltip(): void
    {
        $renderer = new MarkdownRenderer(fn (string $type, string $key): array => [
            'status' => 'available',
            'type' => 'usuário',
            'label' => 'Nome atual',
            'url' => '/users/42',
            'accessible_name' => 'usuário: Nome atual',
        ]);

        $this->assertSame(
            '<p>@<a href="/users/42" target="_blank" rel="noopener noreferrer" class="markdown-mention" aria-label="usuário: Nome atual" title="usuário: Nome atual">Nome atual</a></p>' . "\n",
            $renderer->render('@[Nome histórico](mention:user:42)')
        );
    }

    public function test_it_renders_an_available_project_with_its_current_name_and_type_metadata(): void
    {
        $renderer = new MarkdownRenderer(fn (string $type, string $key): array => [
            'status' => 'available',
            'type' => 'projeto',
            'label' => 'Projeto atual',
            'url' => '/projects/projeto-atual',
            'accessible_name' => 'projeto: Projeto atual',
        ]);

        $this->assertSame(
            '<p>@<a href="/projects/projeto-atual" target="_blank" rel="noopener noreferrer" class="markdown-mention" aria-label="projeto: Projeto atual" title="projeto: Projeto atual">Projeto atual</a></p>' . "\n",
            $renderer->render('@[Rótulo histórico](mention:project:42)')
        );
    }

    public function test_it_renders_the_missing_message_without_a_link(): void
    {
        $renderer = new MarkdownRenderer(fn (string $type, string $key): array => [
            'status' => 'missing',
            'type' => 'usuário',
            'message' => 'Menção a usuário: destino não encontrado',
        ]);

        $this->assertSame(
            "<p>Menção a usuário: destino não encontrado</p>\n",
            $renderer->render('@[Nome histórico](mention:user:42)')
        );
    }
}
