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
                ->attach(
                    '#file-upload-'.$project->getMorphClass().'-'.$project->id,
                    base_path('tests/Browser/source/arquivo-dusk.txt'),
                )
                ->press('Enviar Arquivo')
                ->waitFor('[data-file-card]')
                ->assertSee('arquivo-dusk')
                ->assertPresent('[data-file-card] a[href*="/download"]');
        });
    }
}
