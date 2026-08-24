<?php

namespace Tests\Browser;

use App\Enums\Project\ProjectUserRole;
use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FileCardsTest extends DuskTestCase
{
    public function test_images_share_one_preview_modal_and_replace_its_content(): void
    {
        $administrator = self::getUser('admin');
        $project = Project::query()->firstOrCreate(
            ['slug' => 'dusk-preview-imagens'],
            [
                'name' => 'Projeto Dusk de Preview',
                'status' => ProjectStatus::ACTIVE,
            ],
        );
        $project->users()->syncWithoutDetaching([
            $administrator->id => ['role' => ProjectUserRole::ADMIN->value],
        ]);
        $project->clearMediaCollection();

        $media = collect(['primeiro-preview.png', 'segundo-preview.png'])
            ->map(function (string $name) use ($project) {
                $image = $project
                    ->addMedia(UploadedFile::fake()->image($name, 20, 20))
                    ->toMediaCollection();
                $image->setCustomProperty('thumbnail_status', 'ready');
                $image->save();

                return $image;
            });
        $first = $media->first();
        $second = $media->last();
        $modalSelector = '#files-project-'.$project->id.'-image-preview-modal';
        $previewSelector = $modalSelector.' [data-file-image-preview-image]';

        $this->browse(function (Browser $browser) use (
            $administrator,
            $project,
            $first,
            $second,
            $modalSelector,
            $previewSelector,
        ): void {
            $browser->loginAs($administrator)
                ->visit(route('projects.show', $project))
                ->waitFor('[data-file-image-preview]')
                ->assertScript('document.querySelectorAll("[data-file-image-preview]").length', 2)
                ->assertScript('document.querySelectorAll("[data-file-image-preview-modal]").length', 1)
                ->assertScript('document.querySelector("'.$previewSelector.'").hasAttribute("src")', false)
                ->click('#file-'.$first->uuid.' [data-file-image-preview]')
                ->waitFor($modalSelector.'.show')
                ->assertScript(
                    'document.querySelector("'.$previewSelector.'").getAttribute("src")',
                    route('files.original', ['uuid' => $first->uuid]),
                )
                ->assertScript(
                    'document.querySelector("'.$previewSelector.'").getAttribute("alt")',
                    $first->display_name,
                )
                ->assertSeeIn($modalSelector, $first->display_name)
                ->click($modalSelector.' [data-dismiss="modal"]')
                ->waitUntil('!document.querySelector("'.$modalSelector.'").classList.contains("show")')
                ->assertScript('document.querySelector("'.$previewSelector.'").hasAttribute("src")', false)
                ->assertScript('document.querySelector("'.$previewSelector.'").hidden', true)
                ->click('#file-'.$second->uuid.' [data-file-image-preview]')
                ->waitFor($modalSelector.'.show')
                ->assertScript(
                    'document.querySelector("'.$previewSelector.'").getAttribute("src")',
                    route('files.original', ['uuid' => $second->uuid]),
                )
                ->assertScript(
                    'document.querySelector("'.$previewSelector.'").getAttribute("alt")',
                    $second->display_name,
                )
                ->assertSeeIn($modalSelector, $second->display_name);
        });
    }

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
