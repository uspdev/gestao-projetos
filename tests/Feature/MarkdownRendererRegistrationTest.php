<?php

namespace Tests\Feature;

use App\Services\MarkdownRenderer;
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
}
