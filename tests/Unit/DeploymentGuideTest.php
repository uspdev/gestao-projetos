<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeploymentGuideTest extends TestCase
{
    private const GUIDE = __DIR__.'/../../docs/user-friendly/markdown/implantacao.md';

    public function test_deployment_guide_declares_the_runtime_contract(): void
    {
        $guide = $this->guide();

        foreach ([
            'MEDIA_DISK=files',
            'MEDIA_CONVERSIONS_DISK=files',
            'QUEUE_CONNECTION=database',
            'IMAGE_DRIVER=gd',
            'storage/app/files',
            '100 MiB',
            'upload_max_filesize=100M',
            'post_max_size=110M',
            'php --ri gd',
            'php artisan queue:work database --queue=default --sleep=3 --tries=4 --timeout=60',
            'composer install --no-dev',
            'php artisan projetos:assets-publish',
            'php artisan migrate --path=database/migrations/2026_07_21_090000_create_media_table.php --force',
            'php artisan migrate --path=database/migrations/2026_07_22_090000_create_meeting_file_shares_table.php --force',
            'php artisan migrate --path=database/migrations/2026_07_23_090000_create_mentions_table.php --force',
            'php artisan optimize:clear',
            'php artisan queue:restart',
            'php artisan migrate --path=database/migrations/2026_07_20_120000_convert_organizational_project_type_description_to_markdown.php --force',
            'php artisan migrate:status',
            'php artisan test',
            'php artisan dusk',
            'php artisan mentions:rebuild',
            'cdn.jsdelivr.net',
        ] as $requirement) {
            self::assertStringContainsString($requirement, $guide, $requirement);
        }
    }

    public function test_deployment_guide_preserves_the_safe_release_order_and_recovery_plan(): void
    {
        $guide = $this->guide();

        $sections = [
            '## Preparação',
            '## Ordem da publicação',
            '## Verificação',
            '## Recuperação',
        ];
        $previousPosition = -1;

        foreach ($sections as $section) {
            $position = strpos($guide, $section);

            self::assertNotFalse($position, $section);
            self::assertGreaterThan($previousPosition, $position, $section);
            $previousPosition = $position;
        }

        $commands = [
            'composer install --no-dev',
            'php artisan projetos:assets-publish',
            'php artisan migrate --path=database/migrations/2026_07_21_090000_create_media_table.php --force',
            'php artisan migrate --path=database/migrations/2026_07_22_090000_create_meeting_file_shares_table.php --force',
            'php artisan migrate --path=database/migrations/2026_07_23_090000_create_mentions_table.php --force',
            'php artisan optimize:clear',
            'php artisan queue:restart',
            'php artisan migrate --path=database/migrations/2026_07_20_120000_convert_organizational_project_type_description_to_markdown.php --force',
            'php artisan test',
            'php artisan dusk',
            'php artisan mentions:rebuild',
        ];
        $releasePosition = strpos($guide, '## Ordem da publicação');

        self::assertNotFalse($releasePosition);
        $previousPosition = -1;

        foreach ($commands as $command) {
            $position = strpos($guide, $command, $releasePosition);

            self::assertNotFalse($position, $command);
            self::assertGreaterThan($previousPosition, $position, $command);
            $previousPosition = $position;
        }

        $normalizedGuide = preg_replace('/\s+/', ' ', $guide);

        self::assertIsString($normalizedGuide);

        foreach ([
            'não executa mudanças de infraestrutura',
            'configura S3',
            'altera e-mails',
        ] as $restriction) {
            self::assertStringContainsString($restriction, $normalizedGuide, $restriction);
        }

        self::assertStringNotContainsString('npm ci', $guide);
        self::assertStringNotContainsString('npm run production', $guide);

        $rollbackLines = array_values(array_filter(
            explode("\n", $guide),
            static fn (string $line): bool => str_contains($line, 'php artisan migrate:rollback'),
        ));

        self::assertCount(1, $rollbackLines);
        self::assertStringStartsWith('Não execute', trim($rollbackLines[0]));

        self::assertStringContainsString('Não execute `php artisan migrate:rollback`', $guide);
        self::assertStringContainsString('nem remova as novas tabelas', $guide);
        foreach ([
            'não há antivírus nem cotas',
            'formatos gerais podem ser enviados',
            'Menções a arquivo podem quebrar',
            'fila indisponível',
            'armazenamento e backups',
        ] as $risk) {
            self::assertStringContainsString($risk, $normalizedGuide, $risk);
        }
        self::assertStringNotContainsString('nginx', strtolower($guide));
        self::assertStringNotContainsString('client_max_body_size', $guide);
    }

    private function guide(): string
    {
        $guide = file_get_contents(self::GUIDE);

        self::assertIsString($guide);

        return $guide;
    }
}
