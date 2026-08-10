<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublishAssetsCommandTest extends TestCase
{
    public function test_command_publishes_the_application_assets(): void
    {
        $this->artisan('assets:publish')
            ->expectsOutputToContain('Ativos publicados:')
            ->assertSuccessful();

        self::assertFileExists(public_path('js/app.js'));
        self::assertFileExists(public_path('css/app.css'));
        self::assertFileExists(public_path('mix-manifest.json'));
        self::assertStringContainsString(
            'Gerado por `php artisan assets:publish`',
            file_get_contents(public_path('js/app.js')),
        );
        self::assertStringContainsString(
            '.markdown-content',
            file_get_contents(public_path('css/app.css')),
        );
    }
}
