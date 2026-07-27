<?php

namespace Tests\Browser;

use App\Enums\Project\ProjectUserRole;
use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FileCardsTest extends DuskTestCase
{
    public function test_administrator_can_upload_a_file_and_see_its_card_and_download_link(): void
    {
        $administrator = self::getUser('admin');
        $project = Project::query()->firstOrCreate(
            ['slug' => 'dusk-arquivos'],
            [
                'name' => 'Projeto Dusk de Arquivos',
                'status' => ProjectStatus::ACTIVE,
            ],
        );
        $project->users()->syncWithoutDetaching([
            $administrator->id => ['role' => ProjectUserRole::ADMIN->value],
        ]);

        $this->browse(function (Browser $browser) use ($administrator, $project): void {
            $browser->loginAs($administrator)
                ->visit(route('projects.show', $project))
                ->waitFor('[data-file-upload-form]')
                ->assertDisabled('[data-file-upload-submit]')
                ->attach(
                    '#file-upload-'.$project->getMorphClass().'-'.$project->id,
                    base_path('tests/Fixtures/arquivo-dusk.txt'),
                )
                ->assertEnabled('[data-file-upload-submit]')
                ->assertVisible('[data-file-upload-feedback]')
                ->click('[data-file-upload-clear]')
                ->assertDisabled('[data-file-upload-submit]')
                ->attach(
                    '#file-upload-'.$project->getMorphClass().'-'.$project->id,
                    base_path('tests/Fixtures/arquivo-dusk.txt'),
                )
                ->press('Enviar Arquivo')
                ->waitFor('[data-file-card]')
                ->assertSee('arquivo-dusk')
                ->assertPresent('[data-file-card] a[href*="/download"]')
                ->assertMissing('[data-file-card] .btn[href*="/download"]')
                ->script(<<<'JS'
                    window.fileDownloadResult = null;
                    const link = document.querySelector('[data-file-card] a[href*="/download"]');
                    fetch(link.href, { credentials: 'same-origin' })
                        .then(async function (response) {
                            window.fileDownloadResult = {
                                status: response.status,
                                disposition: response.headers.get('content-disposition'),
                                nosniff: response.headers.get('x-content-type-options'),
                                body: await response.text(),
                            };
                        });
                JS);

            $browser
                ->waitUntil('window.fileDownloadResult !== null')
                ->assertScript('window.fileDownloadResult.status', 200)
                ->assertScript('window.fileDownloadResult.disposition.includes("attachment")', true)
                ->assertScript('window.fileDownloadResult.nosniff', 'nosniff')
                ->assertScript('window.fileDownloadResult.body.trim()', 'Conteúdo de teste para o fluxo Dusk de Arquivos.')
                ->assertPresent('[data-file-rename-toggle]')
                ->assertMissing('[data-file-rename-form]:not([hidden])')
                ->click('[data-file-rename-toggle]')
                ->assertVisible('[data-file-rename-form]')
                ->assertVisible('[data-file-rename-form] button[type="submit"]');
        });
    }
}
