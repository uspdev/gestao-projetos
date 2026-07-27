<?php

namespace Tests\Feature;

use App\Services\MarkdownRenderer;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MarkdownRendererRegistrationTest extends TestCase
{
    public function test_markdown_renderer_is_registered_as_a_singleton(): void
    {
        $this->assertSame(
            $this->app->make(MarkdownRenderer::class),
            $this->app->make(MarkdownRenderer::class)
        );
    }

    public function test_it_resolves_root_relative_internal_links_from_the_named_application_route(): void
    {
        URL::forceRootUrl('https://example.test/gestao-projetos/public');

        $html = $this->app->make(MarkdownRenderer::class)
            ->render('[Projeto de Id 1](/projects/1)');

        $this->assertSame(
            '<p><a href="https://example.test/gestao-projetos/public/projects/1" target="_blank" rel="noopener noreferrer">Projeto de Id 1</a></p>' . "\n",
            $html
        );
    }
}
