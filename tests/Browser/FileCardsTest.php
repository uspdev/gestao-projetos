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
                ->assertSee('Imagens')
                ->assertSee('Documentos')
                ->assertSee('Links')
                ->attach(
                    '#file-upload-'.$project->getMorphClass().'-'.$project->id,
                    base_path('tests/Fixtures/arquivo-dusk.txt'),
                )
                ->waitFor('[data-file-card]')
                ->assertSee('arquivo-dusk')
                ->assertPresent('[data-file-card][id^="file-"]')
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
                ->click('[data-file-action] > button')
                ->waitFor('[data-file-action] .dropdown-menu.show')
                ->click('[data-file-action] .dropdown-menu.show [data-file-rename-toggle]')
                ->assertVisible('[data-file-rename-form]:not([hidden])')
                ->assertVisible('[data-file-rename-form]:not([hidden]) button[type="submit"]')
                ->assertScript(<<<'JS'
                    (function () {
                        const form = document.querySelector('[data-file-rename-form]:not([hidden])');
                        const region = form && form.closest('[data-file-edit-region]');
                        const card = form && form.closest('[data-file-card]');

                        return Boolean(form && region && card && region.closest('[data-file-card]') === card);
                    })()
                JS, true)
                ->script(<<<'JS'
                    document.querySelector('[data-file-browser-scroll]').style.maxHeight = 'none';
                JS);

            $browser
                ->waitUntil(<<<'JS'
                    const region = document.querySelector('[data-file-browser-scroll]');

                    return region.scrollHeight <= region.clientHeight
                        && !region.classList.contains('has-vertical-overflow');
                JS)
                ->assertScript(
                    "getComputedStyle(document.querySelector('[data-file-browser-scroll]')).overscrollBehaviorY",
                    'auto',
                )
                ->script(<<<'JS'
                    document.querySelector('[data-file-browser-scroll]').style.maxHeight = '1px';
                JS);

            $browser
                ->waitUntil(<<<'JS'
                    const region = document.querySelector('[data-file-browser-scroll]');

                    return region.scrollHeight > region.clientHeight
                        && region.classList.contains('has-vertical-overflow');
                JS)
                ->assertScript(
                    "getComputedStyle(document.querySelector('[data-file-browser-scroll]')).overscrollBehaviorY",
                    'contain',
                );
        });
    }
}
