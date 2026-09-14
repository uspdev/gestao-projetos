<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApiIntegrationDocumentationTest extends TestCase
{
    private const DOCUMENT = __DIR__.'/../../docs/dev-friendly/api/integracoes-e-solicitacoes.md';

    public function test_documentation_covers_the_public_routes_and_abilities(): void
    {
        $document = $this->document();

        foreach ([
            '/api/projects/{project}` | `projects.read`',
            '/api/projects/{project}/tasks` | `tasks.read`',
            '/api/projects/{project}/tasks/{task}` | `tasks.read`',
            '/api/projects/{project}/requests` | `requests.create`',
            '/api/projects/{project}/requests` | `requests.read`',
            '/api/projects/{project}/requests/{request}` | `requests.read`',
        ] as $routeContract) {
            self::assertStringContainsString($routeContract, $document, $routeContract);
        }

        foreach ([
            'Authorization: Bearer <CHAVE_DE_API>',
            '`viewer`',
            '`contributor`',
            '`purpose` é somente metadado',
            '`requests.create`',
            '`api_key` está desabilitado',
            '60 requisições por minuto por endereço IP',
        ] as $authenticationContract) {
            self::assertStringContainsString(
                $authenticationContract,
                $document,
                $authenticationContract,
            );
        }
    }

    public function test_documentation_covers_resources_filters_and_errors(): void
    {
        $document = $this->document();

        foreach ([
            '"tasks_enabled": true',
            '"assignees"',
            '"source_url"',
            '"evaluated_at"',
            '"task"',
            '`per_page`',
            '`updated_at` decrescente e depois por `id` decrescente',
            '`created_at` decrescente e depois por `id` decrescente',
            '`NEW` (Nova)',
            '`ASSIGNED` (Atribuída)',
            '`IN_PROGRESS` (Em Andamento)',
            '`pending`, `accepted`',
            '`201 Created`',
            '`Location`',
            '`401 Unauthorized`',
            '`403 Forbidden`',
            '`404 Not Found`',
            '`409 Conflict`',
            '`422 Unprocessable Content`',
            '`429 Too Many Requests`',
            '`tasks_module_disabled`',
        ] as $resourceContract) {
            self::assertStringContainsString($resourceContract, $document, $resourceContract);
        }
    }

    public function test_documentation_records_installation_and_operational_constraints(): void
    {
        $document = $this->document();

        foreach ([
            'PHP 8.3',
            'composer require uspdev/api-keys:^0.1',
            'php artisan vendor:publish --tag=api-keys-config',
            'php artisan vendor:publish --tag=api-keys-migrations',
            'composer install --no-dev --optimize-autoloader',
            'php artisan migrate --force',
            'não autoriza nem executa uma implantação em produção',
            'Alterar o slug do Projeto quebra deliberadamente',
            'não oferece chave de idempotência',
            'retry depois de timeout pode criar Solicitações duplicadas',
        ] as $operationalContract) {
            self::assertStringContainsString(
                $operationalContract,
                $document,
                $operationalContract,
            );
        }
    }

    public function test_documentation_uses_the_canonical_domain_vocabulary(): void
    {
        $document = $this->document();

        foreach ([
            'Sistema cliente',
            'Solicitação',
            'Avaliador da Solicitação',
            'Projeto de escopo da Chave',
        ] as $term) {
            self::assertStringContainsString($term, $document, $term);
        }

        self::assertStringNotContainsString('usuário da API', $document);
        self::assertStringNotContainsString('token global', $document);
        self::assertStringNotContainsString('Tarefa pendente', $document);
    }

    private function document(): string
    {
        $document = file_get_contents(self::DOCUMENT);

        self::assertIsString($document);

        $document = preg_replace('/\s+/', ' ', $document);

        self::assertIsString($document);

        return $document;
    }
}
