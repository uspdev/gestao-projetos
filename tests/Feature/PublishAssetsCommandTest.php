<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublishAssetsCommandTest extends TestCase
{
    public function test_command_publishes_the_application_javascript(): void
    {
        $this->artisan('projetos:assets-publish')
            ->expectsOutputToContain('Ativos publicados:')
            ->assertSuccessful();

        self::assertFileExists(public_path('js/app.js'));
        self::assertSame(
            file_get_contents(resource_path('js/app.js')),
            file_get_contents(public_path('js/app.js')),
        );
    }
}
