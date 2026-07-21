<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MarkdownRenderer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MarkdownPreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');
    }

    public function test_authenticated_user_can_preview_markdown_with_the_official_renderer(): void
    {
        $user = new User();
        $user->id = 123;

        $markdown = '**Prévia segura** [Destino](https://example.test)';
        $expectedHtml = $this->app->make(MarkdownRenderer::class)->render($markdown);

        $this->actingAs($user)
            ->post(route('markdown.preview'), ['markdown' => $markdown])
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertContent($expectedHtml);
    }

    public function test_preview_requires_authentication(): void
    {
        $this->post(route('markdown.preview'), ['markdown' => '# Restrito'])
            ->assertRedirect();
    }

    public function test_preview_rejects_content_above_the_markdown_limit(): void
    {
        $user = new User();
        $user->id = 123;

        $this->actingAs($user)
            ->postJson(route('markdown.preview'), ['markdown' => str_repeat('a', 10001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('markdown');
    }

    public function test_preview_does_not_persist_content_or_audit_events(): void
    {
        $user = new User();
        $user->id = 123;
        $writeQueries = [];

        DB::listen(function (QueryExecuted $query) use (&$writeQueries): void {
            if (preg_match('/^\s*(insert|update|delete)\b/i', $query->sql)) {
                $writeQueries[] = $query->sql;
            }
        });

        $this->actingAs($user)
            ->post(route('markdown.preview'), ['markdown' => '# Sem efeitos colaterais'])
            ->assertOk();

        $this->assertSame([], $writeQueries);
    }
}
