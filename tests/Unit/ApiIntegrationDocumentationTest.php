<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApiIntegrationDocumentationTest extends TestCase
{
    private const DOCUMENT = __DIR__.'/../../docs/dev-friendly/api/leitura-por-projeto.md';

    private const MANUAL_TEST = __DIR__.'/../../docs/dev-friendly/api/teste-manual-leitura-por-projeto.md';

    public function test_documentation_covers_the_seven_read_routes_and_authentication_contract(): void
    {
        $document = $this->document(self::DOCUMENT);

        foreach ([
            '/api/projects/{project}` | `projects.read`',
            '/api/projects/{project}/meetings` | `meetings.read`',
            '/api/projects/{project}/meetings/{meeting}` | `meetings.read`',
            '/api/projects/{project}/tasks` | `tasks.read`',
            '/api/projects/{project}/tasks/{task}` | `tasks.read`',
            '/api/projects/{project}/files` | `files.read`',
            '/api/projects/{project}/files/{uuid}` | `files.read`',
        ] as $routeContract) {
            self::assertStringContainsString($routeContract, $document, $routeContract);
        }

        foreach ([
            'Authorization: Bearer <CHAVE_DE_API>',
            '`viewer`',
            '`contributor`',
            '`projects.read`',
            '`meetings.read`',
            '`tasks.read`',
            '`files.read`',
            '`purpose` é somente metadado',
            '?api_key=<CHAVE_DE_API>',
            '60 requisições por minuto por endereço IP',
        ] as $authenticationContract) {
            self::assertStringContainsString($authenticationContract, $document, $authenticationContract);
        }
    }

    public function test_documentation_covers_representations_filters_pagination_and_errors(): void
    {
        $document = $this->document(self::DOCUMENT);

        foreach ([
            '`modules.enabled`',
            '`modules.items`',
            '`visibility`',
            '`permission_inheritance`',
            '`incoming_mentions`',
            '`files.owned`',
            '`files.shared`',
            '`links.owned`',
            '`links.shared`',
            '`status[]`',
            '`priority[]`',
            '`due_from`',
            '`due_to`',
            '`tag[]`',
            '`scheduled_from`',
            '`scheduled_to`',
            '`owner_type[]`',
            '`mime_type[]`',
            '`uploaded_from`',
            '`uploaded_to`',
            '`page`',
            '`per_page`',
            '`data`, `links` e `meta`',
            '`updated_at desc`, `id desc`',
            '`scheduled_at desc`, `id desc`',
            '`uploaded_at desc`, `id desc`',
            '`401 Unauthorized`',
            '`403 Forbidden`',
            '`404 Not Found`',
            '`409 Conflict`',
            '`422 Unprocessable Content`',
            '`429 Too Many Requests`',
            '`tasks_module_disabled`',
            '`meetings_module_disabled`',
            '`Content-Disposition: attachment`',
            '`X-Content-Type-Options: nosniff`',
        ] as $resourceContract) {
            self::assertStringContainsString($resourceContract, $document, $resourceContract);
        }
    }

    public function test_documentation_records_read_only_scope_chamados_and_slug_breakage(): void
    {
        $document = $this->document(self::DOCUMENT);

        foreach ([
            'A API de negócio oferece somente operações `GET`',
            'abertura e a triagem de demandas pertencem ao Chamados',
            'não recebe Solicitações nem cria Tarefas pela API',
            'Alterar o slug do Projeto quebra todas as URLs da API',
            'não há redirecionamento nem alias',
            'atualizar o slug configurado em todos os consumidores',
            'inclusive no download de Arquivos',
        ] as $scopeContract) {
            self::assertStringContainsString($scopeContract, $document, $scopeContract);
        }

        foreach ([
            'Sistema cliente',
            '/requests',
            '`requests.create`',
            '`requests.read`',
            'Swagger',
            'OpenAPI',
        ] as $discardedContract) {
            self::assertStringNotContainsString($discardedContract, $document, $discardedContract);
        }
    }

    public function test_manual_test_covers_the_complete_read_flow_and_representative_error(): void
    {
        $manualTest = $this->document(self::MANUAL_TEST);

        foreach ([
            'emitir uma Chave de API',
            'revogar a Chave de API',
            '/api/projects/${PROJECT_SLUG}',
            '/meetings',
            'search=planejamento',
            '/meetings/${MEETING_ID}',
            '/tasks',
            'status[]=IN_PROGRESS',
            '/tasks/${TASK_ID}',
            '/files',
            'owner_type[]=project',
            '/files/${FILE_UUID}',
            '`409 Conflict`',
            '`422 Unprocessable Content`',
            '`404 Not Found`',
            '`401 Unauthorized`',
            '`data`, `links` e `meta`',
            'conteúdo integral',
            '`incoming_mentions`',
            '`files`',
            '`links`',
            'isolamento entre Projetos',
            'módulos desligados',
        ] as $manualContract) {
            self::assertStringContainsString($manualContract, $manualTest, $manualContract);
        }
    }

    private function document(string $path): string
    {
        $document = file_get_contents($path);

        self::assertIsString($document);

        $document = preg_replace('/\s+/', ' ', $document);

        self::assertIsString($document);

        return $document;
    }
}
