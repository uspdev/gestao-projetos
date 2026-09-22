<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiReadOnlyContractTest extends TestCase
{
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
