<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiReadOnlyContractTest extends TestCase
{
    public function test_api_home_documents_all_read_endpoints(): void
    {
        $response = $this->get('/api')->assertOk();

        foreach ([
            '/api/projects/{project}',
            '/api/projects/{project}/meetings',
            '/api/projects/{project}/meetings/{meeting}',
            '/api/projects/{project}/tasks',
            '/api/projects/{project}/tasks/{task}',
            '/api/projects/{project}/files',
            '/api/projects/{project}/files/{uuid}',
        ] as $path) {
            $response->assertSee($path, false);
        }

        $response->assertSee('Authorization: Bearer', false)
            ->assertSee('status[]', false)
            ->assertSee('description', false)
            ->assertSee('download_url', false)
            ->assertDontSee('curl')
            ->assertDontSee('Paginação')
            ->assertDontSee('per_page')
            ->assertDontSee('Como começar');

        $html = $response->getContent();

        self::assertSame(7, substr_count($html, '<details class="endpoint"'));
        self::assertSame(0, preg_match('/<details\b[^>]*\bopen\b/', $html));
        self::assertSame(7, preg_match_all('/<summary class="endpoint-summary">(.*?)<\/summary>/s', $html, $summaries));

        foreach ($summaries[1] as $summary) {
            self::assertStringContainsString('<span class="method">GET</span>', $summary);
            self::assertStringContainsString('<span class="endpoint-title">', $summary);
            self::assertStringContainsString('<code class="path">', $summary);
            self::assertStringNotContainsString('Ability exigida', $summary);
            self::assertStringNotContainsString('Retorno', $summary);
        }

        self::assertSame(7, substr_count($html, '<dt>Retorno</dt>'));
        self::assertSame(3, substr_count($html, '<dt>Filtros</dt>'));
    }

    public function test_unknown_api_route_returns_not_found(): void
    {
        $this->get('/api/rota-inexistente')
            ->assertNotFound()
            ->assertSee('Endpoints disponíveis');

        $this->getJson('/api/rota-inexistente')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Not Found',
                'documentation_url' => route('api.guide'),
            ]);
    }

    #[DataProvider('businessApiUrls')]
    public function test_business_api_does_not_expose_write_methods(string $url): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->json($method, $url)->assertMethodNotAllowed();
        }
    }

    /** @return array<string, array{string}> */
    public static function businessApiUrls(): array
    {
        return [
            'project' => ['/api/projects/projeto-contrato'],
            'meeting collection' => ['/api/projects/projeto-contrato/meetings'],
            'meeting detail' => ['/api/projects/projeto-contrato/meetings/1'],
            'task collection' => ['/api/projects/projeto-contrato/tasks'],
            'task detail' => ['/api/projects/projeto-contrato/tasks/1'],
            'file collection' => ['/api/projects/projeto-contrato/files'],
            'file download' => ['/api/projects/projeto-contrato/files/00000000-0000-4000-8000-000000000001'],
        ];
    }
}
