<?php

namespace Tests\Browser;

use App\Enums\Meeting\MeetingStatus;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Module;
use App\Models\Project;
use App\Models\Task;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MarkdownEditorTest extends DuskTestCase
{
    public function test_markdown_field_can_be_saved_when_easymde_is_unavailable(): void
    {
        $suffix = Str::lower(Str::random(8));
        $projectName = 'Projeto sem CDN '.$suffix;
        $slug = 'dusk-cdn-indisponivel-'.$suffix;

        $this->browse(function (Browser $browser) use ($projectName, $slug): void {
            $devTools = $this->blockCdnAsset(
                $browser,
                '*easymde@2.20.0/dist/easymde.min.js*'
            );

            try {
                $textarea = 'textarea[data-markdown-profile="full"]';

                $browser->loginAs(self::getUser('admin'))
                    ->visit('/projects/create?project_type=organizacional')
                    ->waitFor($textarea)
                    ->assertVisible($textarea)
                    ->assertMissing($textarea.' + .EasyMDEContainer')
                    ->type($textarea, 'Markdown editável sem CDN')
                    ->assertInputValue($textarea, 'Markdown editável sem CDN')
                    ->type('name', $projectName)
                    ->type('slug', $slug)
                    ->select('status', ProjectStatus::ACTIVE->value)
                    ->assertScript(
                        "window.MarkdownEditors.get(document.querySelector('{$textarea}')) === undefined",
                        true
                    );

                $browser->waitForReload(fn (Browser $browser) => $browser->press('Salvar Projeto'))
                    ->assertSee('Markdown editável sem CDN')
                    ->assertSee($projectName);
            } finally {
                $this->restoreCdnAccess($devTools);
            }
        });

        $this->assertSame(
            'Markdown editável sem CDN',
            Project::query()->where('name', $projectName)->value('description')
        );
    }

    public function test_editor_remains_usable_when_highlight_js_is_unavailable(): void
    {
        $this->browse(function (Browser $browser): void {
            $devTools = $this->blockCdnAsset(
                $browser,
                '*highlightjs/cdn-release@11.11.1/build/highlight.min.js*'
            );

            try {
                $browser->loginAs(self::getUser('admin'))
                    ->visit('/projects/create?project_type=organizacional')
                    ->waitFor('.EasyMDEContainer')
                    ->assertScript("typeof window.EasyMDE === 'function'", true)
                    ->assertScript('window.hljs === undefined', true);

                $browser->script(<<<'JS'
                    const content = document.createElement('div');
                    content.id = 'markdown-without-highlight';
                    content.className = 'markdown-content';
                    content.innerHTML = '<pre><code>echo 1;</code></pre>';
                    document.body.appendChild(content);
                JS);

                $browser->waitForText('echo 1;')
                    ->assertScript(
                        "document.querySelector('#markdown-without-highlight code').classList.contains('hljs')",
                        false
                    );
            } finally {
                $this->restoreCdnAccess($devTools);
            }
        });
    }

    public function test_project_description_uses_the_real_preview_save_and_safe_display_flow(): void
    {
        $administrator = self::getUser('admin');
        $project = Project::query()->firstOrCreate(
            ['slug' => 'dusk-markdown-real'],
            [
                'name' => 'Projeto Dusk Markdown',
                'status' => ProjectStatus::ACTIVE,
            ],
        );
        $project->description = 'Descrição inicial';
        $project->save();

        $textarea = '#project-description-edit-'.$project->id.'-textarea';

        $this->browse(function (Browser $browser) use ($administrator, $project, $textarea): void {
            $browser->loginAs($administrator)
                ->visit(route('projects.show', $project))
                ->click('[aria-label="Editar descrição"]')
                ->waitFor($textarea.' + .EasyMDEContainer');

            $browser->script(<<<JS
                const entry = window.MarkdownEditors.get(document.querySelector('{$textarea}'));
                entry.editor.value('**Descrição validada** <script>alert(1)</script>');
                entry.editor.toolbarElements.preview.click();
            JS);

            $browser
                ->waitUntil(<<<JS
                    (() => {
                        const textarea = document.querySelector('{$textarea}');
                        const entry = textarea && window.MarkdownEditors.get(textarea);
                        const preview = entry && entry.preview.element();

                        return Boolean(
                            preview
                            && preview.textContent.includes('Descrição validada')
                            && !preview.querySelector('script')
                        );
                    })()
                JS)
                ->assertScript(<<<JS
                    (() => {
                        const textarea = document.querySelector('{$textarea}');
                        const preview = window.MarkdownEditors.get(textarea).preview.element();

                        return preview.textContent.includes('Descrição validada')
                            && !preview.querySelector('script');
                    })()
                JS, true);

            $browser->script(<<<JS
                window.MarkdownEditors.get(document.querySelector('{$textarea}')).editor.toolbarElements.preview.click();
            JS);

            $browser
                ->waitForReload(fn (Browser $browser) => $browser->press('Salvar'))
                ->assertSee('Descrição validada')
                ->assertSourceMissing('<script>alert(1)</script>');
        });

        $this->assertSame(
            '**Descrição validada** <script>alert(1)</script>',
            $project->fresh()->description
        );
    }

    public function test_all_markdown_fields_can_be_previewed_saved_reloaded_and_rendered(): void
    {
        $administrator = self::getUser('admin');
        $suffix = Str::lower(Str::random(8));
        $project = Project::query()->create([
            'name' => 'Projeto Dusk cinco campos '.$suffix,
            'slug' => 'dusk-cinco-campos-'.$suffix,
            'status' => ProjectStatus::ACTIVE,
        ]);
        $project->users()->syncWithoutDetaching([
            $administrator->id => ['role' => ProjectUserRole::ADMIN->value],
        ]);
        $meetingsModule = Module::query()->where('slug', 'meetings')->firstOrFail();
        $project->modules()->syncWithoutDetaching([
            $meetingsModule->id => ['enabled' => true],
        ]);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa Dusk cinco campos '.$suffix,
            'priority' => TaskPriority::MEDIUM,
            'status' => TaskStatus::NEW,
        ]);
        $meeting = Meeting::query()->create([
            'title' => 'Reunião Dusk cinco campos '.$suffix,
            'status' => MeetingStatus::DRAFT,
        ]);
        $meeting->projects()->attach($project);
        $meetingItem = MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Item Dusk cinco campos '.$suffix,
            'order' => 1,
        ]);

        $projectMarkdown = '**Projeto cinco campos** <script>alert(1)</script>';
        $taskMarkdown = '**Tarefa cinco campos** <script>alert(2)</script>';
        $commentMarkdown = '**Comentário cinco campos** <script>alert(3)</script>';
        $meetingMarkdown = '**Reunião cinco campos** <script>alert(4)</script>';
        $itemMarkdown = '**Item cinco campos** <script>alert(5)</script>';

        $this->browse(function (Browser $browser) use (
            $administrator,
            $project,
            $task,
            $meeting,
            $meetingItem,
            $projectMarkdown,
            $taskMarkdown,
            $commentMarkdown,
            $meetingMarkdown,
            $itemMarkdown,
        ): void {
            $browser->loginAs($administrator)
                ->visit(route('projects.show', $project))
                ->click('[aria-label="Editar descrição"]')
                ->waitFor('#project-description-edit-'.$project->id.'-textarea + .EasyMDEContainer');

            $this->fillEditorAndPreview(
                $browser,
                '#project-description-edit-'.$project->id.'-textarea',
                $projectMarkdown,
                'Projeto cinco campos',
            );

            $browser
                ->waitForReload(fn (Browser $browser) => $browser->click(
                    '#project-description-edit-'.$project->id.' form button[aria-label="Salvar"]'
                ))
                ->refresh()
                ->assertSee('Projeto cinco campos')
                ->assertSourceMissing('<script>alert(1)</script>');

            $browser
                ->visit(route('tasks.show', $task))
                ->click('[aria-label="Editar descrição"]')
                ->waitFor('#task-description-edit-'.$task->id.'-textarea + .EasyMDEContainer');

            $this->fillEditorAndPreview(
                $browser,
                '#task-description-edit-'.$task->id.'-textarea',
                $taskMarkdown,
                'Tarefa cinco campos',
            );

            $browser
                ->waitForReload(fn (Browser $browser) => $browser->click(
                    '#task-description-edit-'.$task->id.' form button[aria-label="Salvar"]'
                ))
                ->refresh()
                ->assertSee('Tarefa cinco campos')
                ->assertSourceMissing('<script>alert(2)</script>');

            $browser
                ->visit(route('projects.show', $project))
                ->waitFor('#comment-text + .EasyMDEContainer');

            $this->fillEditorAndPreview($browser, '#comment-text', $commentMarkdown, 'Comentário cinco campos');

            $browser
                ->waitForReload(fn (Browser $browser) => $browser->click(
                    'form[action*="/comments"] button[type="submit"]'
                ))
                ->refresh()
                ->assertSee('Comentário cinco campos')
                ->assertSourceMissing('<script>alert(3)</script>');

            $browser
                ->visit(route('projects.meetings.show', [$project, $meeting]))
                ->click('[aria-label="Editar Anotações prévias"]')
                ->waitFor('#meeting-notes-edit-'.$meeting->id.'-textarea + .EasyMDEContainer');

            $this->fillEditorAndPreview(
                $browser,
                '#meeting-notes-edit-'.$meeting->id.'-textarea',
                $meetingMarkdown,
                'Reunião cinco campos',
            );

            $browser
                ->waitForReload(fn (Browser $browser) => $browser->click(
                    '#meeting-notes-edit-'.$meeting->id.' form button[aria-label="Salvar"]'
                ))
                ->refresh()
                ->assertSee('Reunião cinco campos')
                ->assertSourceMissing('<script>alert(4)</script>');

            $itemNotesSelector = '#meeting-item-notes-'.$meetingItem->id;
            $itemEditSelector = '#meeting-item-notes-edit-'.$meetingItem->id;
            $itemTextarea = $itemEditSelector.'-textarea';

            $browser
                ->click('[aria-controls="meeting-item-notes-'.$meetingItem->id.'"]')
                ->waitFor($itemNotesSelector.'.show')
                ->click($itemNotesSelector.' [data-target*="meeting-item-notes-edit-'.$meetingItem->id.'"]')
                ->waitFor($itemEditSelector.'.show')
                ->waitFor($itemTextarea.' + .EasyMDEContainer');

            $this->fillEditorAndPreview($browser, $itemTextarea, $itemMarkdown, 'Item cinco campos');

            $itemSaveSelector = $itemEditSelector.' form button[aria-label="Salvar"]';
            $itemSaveSelectorLiteral = json_encode($itemSaveSelector, JSON_THROW_ON_ERROR);

            $browser->script("document.querySelector({$itemSaveSelectorLiteral}).scrollIntoView({block: 'center'});");

            $browser
                ->waitForReload(fn (Browser $browser) => $browser->script(
                    "document.querySelector({$itemSaveSelectorLiteral}).click();"
                ))
                ->refresh()
                ->click('[aria-controls="meeting-item-notes-'.$meetingItem->id.'"]')
                ->waitFor($itemNotesSelector.'.show')
                ->assertSeeIn($itemNotesSelector, 'Item cinco campos')
                ->assertSourceMissing('<script>alert(5)</script>');
        });

        self::assertSame($projectMarkdown, $project->fresh()->description);
        self::assertSame($taskMarkdown, $task->fresh()->description);
        self::assertSame(
            $commentMarkdown,
            Comment::query()
                ->where('commentable_type', $project->getMorphClass())
                ->where('commentable_id', $project->id)
                ->latest('id')
                ->value('text'),
        );
        self::assertSame($meetingMarkdown, $meeting->fresh()->notes);
        self::assertSame($itemMarkdown, $meetingItem->fresh()->notes);
    }

    public function test_editor_profiles_initialize_on_the_page_and_in_dynamic_containers(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer')
                ->assertScript(
                    "Array.from(document.scripts).some((script) => /\\/js\\/app\\.js\\?id=/.test(script.src))",
                    true
                )
                ->assertScript(
                    "document.querySelector('[data-markdown-profile=\"full\"]') !== null",
                    true
                )
                ->assertScript($this->toolbarHas('full', 'heading'), true)
                ->assertScript($this->toolbarHas('full', 'task-list'), true)
                ->assertScript($this->toolbarHas('full', 'table'), true)
                ->assertScript($this->toolbarHas('full', 'mention'), true)
                ->assertScript($this->toolbarHas('full', 'file-reference'), false)
                ->assertScript($this->toolbarHas('full', 'markdown-help'), false)
                ->assertScript($this->toolbarHas('full', 'fullscreen'), false)
                ->assertScript($this->toolbarHas('full', 'side-by-side'), false)
                ->assertScript($this->toolbarHas('full', 'image'), false)
                ->assertScript($this->fullToolbarLayoutIsCorrect(), true)
                ->assertScript($this->editorOption('spellChecker'), false)
                ->assertScript($this->editorOption('nativeSpellcheck'), true)
                ->assertScript($this->editorOption('uploadImage'), false)
                ->assertScript($this->editorOption('autosave.enabled'), false);

            $browser->script(<<<'JS'
                const fixture = document.createElement('div');
                fixture.innerHTML = `
                    <div class="modal fade" id="markdown-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content p-3">
                                <textarea id="modal-markdown" data-markdown-editor data-markdown-profile="compact" data-markdown-preview-url="/markdown/preview">Valor preservado no modal</textarea>
                                <div class="invalid-feedback d-block">Erro preservado no modal</div>
                            </div>
                        </div>
                    </div>
                    <div class="collapse" id="markdown-collapse">
                        <textarea id="collapse-markdown" data-markdown-editor data-markdown-profile="full" data-markdown-preview-url="/markdown/preview">Valor preservado no colapso</textarea>
                    </div>`;
                document.body.appendChild(fixture);
                window.jQuery('#markdown-modal').modal('show');
                window.jQuery('#markdown-collapse').collapse('show');
            JS);

            $browser->waitFor('#markdown-modal.show')
                ->waitFor('#markdown-collapse.show')
                ->assertScript($this->toolbarHas('compact', 'bold'), true)
                ->assertScript($this->toolbarHas('compact', 'mention'), true)
                ->assertScript($this->toolbarHas('compact', 'file-reference'), false)
                ->assertScript($this->toolbarHas('compact', 'heading'), false)
                ->assertScript($this->toolbarHas('compact', 'task-list'), false)
                ->assertScript($this->toolbarHas('compact', 'table'), false)
                ->assertScript($this->toolbarHas('compact', 'markdown-help'), false)
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#modal-markdown')).editor.codemirror.getScrollerElement().style.minHeight",
                    '60px'
                )
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#collapse-markdown')).editor.codemirror.getScrollerElement().style.minHeight",
                    '300px'
                )
                ->assertScript("window.MarkdownEditors.get(document.querySelector('#modal-markdown')).editor.value()", 'Valor preservado no modal')
                ->assertScript("window.MarkdownEditors.get(document.querySelector('#collapse-markdown')).editor.value()", 'Valor preservado no colapso')
                ->assertSeeIn('#markdown-modal', 'Erro preservado no modal')
                ->assertScript("document.querySelector('#markdown-modal .CodeMirror').offsetHeight > 0", true)
                ->assertScript("document.querySelector('#markdown-collapse .CodeMirror').offsetHeight > 0", true)
                ->assertScript(
                    "document.querySelectorAll('[data-markdown-editor]').length === document.querySelectorAll('.EasyMDEContainer').length",
                    true
                );
        });
    }

    public function test_preview_ignores_stale_responses_and_keeps_the_last_valid_html_on_failure(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.markdownPreviewRequests = [];
                window.fetch = function (url, options) {
                    return new Promise(function (resolve, reject) {
                        window.markdownPreviewRequests.push({
                            url: url,
                            options: options,
                            resolve: resolve,
                            reject: reject,
                        });
                    });
                };
                const textarea = document.querySelector('[data-markdown-profile="full"]');
                const entry = window.MarkdownEditors.get(textarea);
                entry.editor.value('primeira');
                entry.editor.toolbarElements.preview.click();
            JS);

            $browser->pause(600)
                ->assertScript('window.markdownPreviewRequests.length', 1)
                ->assertScript("window.markdownPreviewRequests[0].options.method", 'POST')
                ->assertScript("window.markdownPreviewRequests[0].options.credentials", 'same-origin')
                ->assertScript("window.markdownPreviewRequests[0].options.headers['X-CSRF-TOKEN'] === document.querySelector('meta[name=\"csrf-token\"]').content", true)
                ->assertScript("JSON.parse(window.markdownPreviewRequests[0].options.body).markdown", 'primeira')
                ;

            $browser->script(<<<'JS'
                const textarea = document.querySelector('[data-markdown-profile="full"]');
                window.MarkdownEditors.get(textarea).editor.value('segunda');
            JS);

            $browser->assertScript('window.markdownPreviewRequests[0].options.signal.aborted', true);

            $browser->script("window.markdownPreviewRequests[0].resolve({ ok: true, text: function () { return Promise.resolve('<p>antiga</p>'); } });");

            $browser->pause(100)
                ->assertScript($this->previewScript("innerHTML.includes('antiga')"), false)
                ->pause(500)
                ->assertScript('window.markdownPreviewRequests.length', 2)
                ;

            $browser->script("window.markdownPreviewRequests[1].resolve({ ok: true, text: function () { return Promise.resolve('<pre><code class=\"language-php\">echo 2;</code></pre><p>nova</p>'); } });");

            $browser->pause(100)
                ->assertScript($this->previewScript("innerHTML.includes('nova')"), true)
                ->assertScript($this->previewScript("querySelector('code').classList.contains('hljs')"), true);

            $browser->script(<<<'JS'
                const textarea = document.querySelector('[data-markdown-profile="full"]');
                window.MarkdownEditors.get(textarea).editor.value('falha');
            JS);

            $browser->pause(600)
                ->assertScript('window.markdownPreviewRequests.length', 3)
                ;

            $browser->script("window.markdownPreviewRequests[2].reject(new Error('falha de rede'));");

            $browser->pause(100)
                ->assertScript($this->previewScript("innerHTML.includes('nova')"), true);

            $browser->script(<<<'JS'
                const textarea = document.querySelector('[data-markdown-profile="full"]');
                const entry = window.MarkdownEditors.get(textarea);
                entry.editor.toolbarElements.preview.click();
                entry.editor.value('prévia oculta');
            JS);

            $browser->pause(600)
                ->assertScript('window.markdownPreviewRequests.length', 3);
        });
    }

    public function test_file_reference_selector_inserts_the_file_mention_after_selection(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [{
                                    uuid: '11111111-1111-4111-8111-111111111111',
                                    name: 'Decisão registrada'
                                }],
                                shareable_results: []
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'file-reference-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-file-reference-url', '/files/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#file-reference-editor + .EasyMDEContainer')
                ->assertScript($this->toolbarHasFor('#file-reference-editor', 'mention'), true)
                ->assertScript($this->toolbarHasFor('#file-reference-editor', 'file-reference'), false);

            $this->openFileReferenceSelector($browser, '#file-reference-editor');

            $browser
                ->waitFor('#file-reference-selector')
                ->click('[data-file-reference-uuid="11111111-1111-4111-8111-111111111111"]');

            $browser
                ->assertScript(
                    <<<'JS'
                        (() => {
                            const value = window.MarkdownEditors.get(document.querySelector('#file-reference-editor')).editor.value();
                            return value === '@[Decisão registrada](mention:file:11111111-1111-4111-8111-111111111111)';
                        })()
                    JS,
                    true
                );
        });
    }

    public function test_legacy_file_references_keep_the_application_public_path_when_rendered(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                const content = document.createElement('div');
                content.className = 'markdown-content';
                content.setAttribute('data-file-reference-context-type', 'project');
                content.setAttribute('data-file-reference-context-id', '47');
                content.innerHTML = '<a id="legacy-file-reference" href="/files/11111111-1111-4111-8111-111111111111">Documento</a>';
                document.body.appendChild(content);
            JS);

            $browser->assertScript(<<<'JS'
                (() => {
                    const appBase = window.location.pathname.split('/projects/')[0];
                    return document.querySelector('#legacy-file-reference').getAttribute('href')
                        === `${appBase}/files/11111111-1111-4111-8111-111111111111?context_type=project&context_id=47`;
                })()
            JS, true);
        });
    }

    public function test_file_reference_to_a_visible_card_does_not_leave_the_current_page(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                sessionStorage.setItem('file-reference-original-path', window.location.pathname);
                sessionStorage.setItem('file-reference-original-search', window.location.search);

                const wrapper = document.createElement('div');
                wrapper.setAttribute('data-file-reference-context-type', 'project');
                wrapper.setAttribute('data-file-reference-context-id', '47');
                wrapper.innerHTML = `
                    <article id="file-11111111-1111-4111-8111-111111111111" class="file-list-item" data-file-card>
                        Documento
                    </article>
                    <div class="markdown-content">
                        <a
                            id="local-file-reference"
                            href="/files/11111111-1111-4111-8111-111111111111"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Documento</a>
                    </div>
                `;
                document.body.appendChild(wrapper);
            JS);

            $browser
                ->waitUntil(<<<'JS'
                    document.querySelector('#local-file-reference').dataset.fileReferenceNavigation === 'resolved'
                JS)
                ->assertScript(<<<'JS'
                    (() => {
                        const link = document.querySelector('#local-file-reference');

                        return link.getAttribute('target') === null
                            && link.getAttribute('rel') === null;
                    })()
                JS, true)
                ->script('document.querySelector("#local-file-reference").click()');

            $browser
                ->pause(300)
                ->assertScript(<<<'JS'
                    window.location.pathname === sessionStorage.getItem('file-reference-original-path')
                        && window.location.search === sessionStorage.getItem('file-reference-original-search')
                        && window.location.hash === '#file-11111111-1111-4111-8111-111111111111'
                JS, true);

            $highlightedCard = <<<'JS'
                getComputedStyle(
                    document.querySelector('#file-11111111-1111-4111-8111-111111111111')
                ).backgroundColor === 'rgb(255, 248, 219)'
            JS;

            $browser
                ->assertScript($highlightedCard, true)
                ->pause(5200)
                ->assertScript($highlightedCard, false);
        });
    }

    public function test_same_page_markdown_anchor_ignores_the_theme_base_url(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                sessionStorage.setItem('markdown-anchor-original-path', window.location.pathname);
                sessionStorage.setItem('markdown-anchor-original-search', window.location.search);

                const content = document.createElement('div');
                content.className = 'markdown-content';
                content.innerHTML = `
                    <a id="same-page-markdown-anchor" href="#agenda">Agenda</a>
                    <h2 id="agenda">Agenda</h2>
                `;
                document.body.appendChild(content);
            JS);

            $browser
                ->pause(50)
                ->script('document.querySelector("#same-page-markdown-anchor").click()');

            $browser
                ->pause(300)
                ->assertScript(<<<'JS'
                    window.location.pathname === sessionStorage.getItem('markdown-anchor-original-path')
                        && window.location.search === sessionStorage.getItem('markdown-anchor-original-search')
                        && window.location.hash === '#agenda'
                JS, true);
        });
    }

    public function test_task_file_reference_selector_groups_task_and_project_files(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [{
                                    uuid: '11111111-1111-4111-8111-111111111111',
                                    name: 'Arquivo da tarefa'
                                }, {
                                    uuid: '22222222-2222-4222-8222-222222222222',
                                    name: 'Arquivo do projeto'
                                }],
                                result_groups: [{
                                    label: 'Tarefa atual: Revisar documentação',
                                    results: [{
                                        uuid: '11111111-1111-4111-8111-111111111111',
                                        name: 'Arquivo da tarefa'
                                    }]
                                }, {
                                    label: 'Projeto da tarefa: Gestão Projetos',
                                    results: [{
                                        uuid: '22222222-2222-4222-8222-222222222222',
                                        name: 'Arquivo do projeto'
                                    }]
                                }]
                            });
                        }
                    });
                };

                const fixture = document.createElement('textarea');
                fixture.id = 'task-file-reference-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-file-reference-url', '/files/selectable?context_type=task&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#task-file-reference-editor + .EasyMDEContainer');
            $this->openFileReferenceSelector($browser, '#task-file-reference-editor');

            $browser
                ->waitFor('#file-reference-selector')
                ->assertSeeIn('#file-reference-selector', 'Tarefa atual: Revisar documentação')
                ->assertSeeIn('#file-reference-selector', 'Projeto da tarefa: Gestão Projetos')
                ->assertPresent('[data-file-reference-uuid="11111111-1111-4111-8111-111111111111"]')
                ->assertPresent('[data-file-reference-uuid="22222222-2222-4222-8222-222222222222"]');
        });
    }

    public function test_file_reference_selector_shares_with_the_meeting_before_inserting_the_mention(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fileReferenceRequests = [];
                window.fetch = function (url, options) {
                    window.fileReferenceRequests.push({ url: url, options: options || {} });
                    if (options && options.method === 'POST') {
                        return Promise.resolve({
                            ok: true,
                            json: function () {
                                return Promise.resolve({
                                    markdown: '@[Arquivo da pauta](mention:file:22222222-2222-4222-8222-222222222222)'
                                });
                            }
                        });
                    }

                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [],
                                shareable_results: [{
                                    uuid: '22222222-2222-4222-8222-222222222222',
                                    name: 'Arquivo da pauta'
                                }],
                                shareable_groups: [{
                                    label: 'Projeto na pauta: Gestão Projetos',
                                    results: [{
                                        uuid: '22222222-2222-4222-8222-222222222222',
                                        name: 'Arquivo da pauta'
                                    }]
                                }, {
                                    label: 'Tarefa na pauta: Revisar documentação',
                                    results: [{
                                        uuid: '33333333-3333-4333-8333-333333333333',
                                        name: 'Registro técnico'
                                    }]
                                }]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'meeting-file-reference-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-file-reference-url', '/files/selectable?context_type=meeting&context_id=1');
                fixture.setAttribute('data-file-share-url', '/meetings/1/file-shares');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#meeting-file-reference-editor + .EasyMDEContainer');
            $this->openFileReferenceSelector($browser, '#meeting-file-reference-editor');

            $browser->waitFor('#file-reference-selector')
                ->assertSeeIn('#file-reference-selector', 'Projeto na pauta: Gestão Projetos')
                ->assertSeeIn('#file-reference-selector', 'Tarefa na pauta: Revisar documentação')
                ->click('[data-file-share-uuid="22222222-2222-4222-8222-222222222222"]')
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#meeting-file-reference-editor')).editor.value()",
                    '@[Arquivo da pauta](mention:file:22222222-2222-4222-8222-222222222222)'
                )
                ->assertScript('window.fileReferenceRequests.length', 2)
                ->assertScript("window.fileReferenceRequests[1].url", '/meetings/1/file-shares')
                ->assertScript("JSON.parse(window.fileReferenceRequests[1].options.body).media_uuid", '22222222-2222-4222-8222-222222222222');
        });
    }

    public function test_mention_selector_inserts_only_explicit_mouse_or_keyboard_selections(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [
                                    { id: 9, name: 'Ana' },
                                    { id: 10, name: 'Bruno' }
                                ]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#mention-editor + .EasyMDEContainer')
                ->assertScript($this->toolbarHasFor('#mention-editor', 'mention'), true);

            $browser->script("window.MarkdownEditors.get(document.querySelector('#mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->scrollIntoView('[data-mention-user-id="9"]')
                ->click('[data-mention-user-id="9"]')
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#mention-editor')).editor.value()",
                    '@[Ana](mention:user:9)'
                );

            $browser->script(<<<'JS'
                const entry = window.MarkdownEditors.get(document.querySelector('#mention-editor'));
                entry.editor.value('@br');
                entry.editor.codemirror.setCursor({ line: 0, ch: 3 });
                entry.editor.toolbarElements.mention.click();
            JS)
            ;

            $browser
                ->waitFor('#mention-selector');

            $browser->script(<<<'JS'
                    const wrapper = window.MarkdownEditors.get(document.querySelector('#mention-editor')).editor.codemirror.getWrapperElement();
                    wrapper.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
                    wrapper.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
                JS);

            $browser
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#mention-editor')).editor.value()",
                    '@[Bruno](mention:user:10)'
                );

            $browser->script("window.MarkdownEditors.get(document.querySelector('#mention-editor')).editor.value('texto @Ana');");

            $browser
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#mention-editor')).editor.value()",
                    'texto @Ana'
                );
        });
    }

    public function test_mention_selector_exposes_type_and_active_option_accessibly(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [
                                    { id: 9, name: 'Mesmo nome', type: 'user', type_label: 'Pessoa' },
                                    { id: 42, name: 'Mesmo nome', type: 'project', type_label: 'Projeto' }
                                ]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'accessible-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#accessible-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#accessible-mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const options = [...selector.querySelectorAll('[data-mention-target-type]')];
                        const filters = [...selector.querySelectorAll('[data-mention-filter]')];
                        const input = window.MarkdownEditors
                            .get(document.querySelector('#accessible-mention-editor'))
                            .editor.codemirror.getInputField();

                        return selector.getAttribute('role') === 'listbox'
                            && selector.getAttribute('aria-label') === 'Destinos mencionáveis'
                            && input.getAttribute('role') === 'combobox'
                            && input.getAttribute('aria-controls') === 'mention-selector'
                            && !filters.some((button) => button.dataset.mentionFilter === 'all')
                            && filters.find((button) => button.dataset.mentionFilter === 'user')?.textContent.trim() === 'Usuários'
                            && filters.find((button) => button.dataset.mentionFilter === 'user')?.getAttribute('aria-pressed') === 'true'
                            && options.length === 1
                            && options.every((option) => option.getAttribute('role') === 'option')
                            && options.every((option) => option.textContent.trim() === 'Mesmo nome')
                            && options.every((option) => option.getAttribute('aria-label') === 'Pessoa: Mesmo nome')
                            && options.every((option) => !option.hasAttribute('title'))
                            && options.every((option) => !option.hasAttribute('data-mention-tooltip'))
                            && selector.querySelector('.small.text-muted.font-weight-bold') === null
                            && options.filter((option) => option.getAttribute('aria-selected') === 'true').length === 1
                            && input.getAttribute('aria-activedescendant') !== null;
                    })()
                JS, true);

            $browser->script("document.querySelector('[data-mention-filter=\"project\"]').focus();");
            $browser->keys('[data-mention-filter="project"]', '{ENTER}')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');

                        return selector.querySelectorAll('[data-mention-target-type]').length === 1
                            && selector.querySelector('[data-mention-target-type="project"]') !== null
                            && selector.querySelector('[data-mention-filter="project"]').getAttribute('aria-pressed') === 'true';
                    })()
                JS, true);

            $browser->script(<<<'JS'
                document.querySelector('[data-mention-filter="project"]').dispatchEvent(
                    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true })
                );
            JS);
            $browser->assertScript('document.querySelector("#mention-selector") === null', true);

            $browser->script("window.MarkdownEditors.get(document.querySelector('#accessible-mention-editor')).editor.toolbarElements.mention.click();");
            $browser->waitFor('#mention-selector');
            $browser->click('[data-mention-filter="file"]')
                ->assertScript(<<<'JS'
                    (() => {
                        const input = window.MarkdownEditors
                            .get(document.querySelector('#accessible-mention-editor'))
                            .editor.codemirror.getInputField();

                        return document.querySelectorAll('#mention-selector [data-mention-target-type]').length === 0
                            && input.getAttribute('aria-activedescendant') === null;
                    })()
                JS, true);
        });
    }

    public function test_closing_mention_selector_ignores_an_obsolete_response(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.pendingMentionRequests = [];
                window.fetch = function () {
                    return new Promise((resolve) => {
                        window.pendingMentionRequests.push(resolve);
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'stale-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);
            $browser->waitFor('#stale-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#stale-mention-editor')).editor.toolbarElements.mention.click();");
            $browser->waitUntil('window.pendingMentionRequests.length === 1');
            $browser->script(<<<'JS'
                    const wrapper = window.MarkdownEditors
                        .get(document.querySelector('#stale-mention-editor'))
                        .editor.codemirror.getWrapperElement();
                    wrapper.dispatchEvent(new KeyboardEvent('keydown', {
                        key: 'Escape',
                        bubbles: true,
                    }));
                    window.pendingMentionRequests[0]({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [{ id: 7, name: 'Resposta antiga', type: 'user', type_label: 'Pessoa' }]
                            });
                        }
                    });
                JS);
            $browser
                ->pause(100)
                ->assertScript('document.querySelector("#mention-selector") === null', true);
        });
    }

    public function test_mention_selector_truncates_long_labels_and_keeps_a_fixed_width(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [
                                    {
                                        id: 9,
                                        name: 'Eduardo Oliveira Ferraz de Campos com um nome muito longo',
                                        type: 'user',
                                        type_label: 'Pessoa'
                                    },
                                    {
                                        id: 42,
                                        name: 'Projeto com um nome suficientemente longo',
                                        type: 'project',
                                        type_label: 'Projeto'
                                    }
                                ]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'bounded-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#bounded-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#bounded-mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const option = selector.querySelector('[data-mention-user-id="9"]');

                        window.initialMentionSelectorWidth = selector.getBoundingClientRect().width;
                        window.initialMentionOptionWidth = option.getBoundingClientRect().width;

                        return option.textContent.trim().length === 50
                            && option.textContent.trim().endsWith('...')
                            && option.getAttribute('aria-label') === 'Pessoa: Eduardo Oliveira Ferraz de Campos com um nome muito longo'
                            && !option.textContent.trim().startsWith('Pessoa:');
                    })()
                JS, true)
                ->click('[data-mention-filter="project"]')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const option = selector.querySelector('[data-mention-project-id="42"]');

                        return Math.abs(selector.getBoundingClientRect().width - window.initialMentionSelectorWidth) < 0.1
                            && Math.abs(option.getBoundingClientRect().width - window.initialMentionOptionWidth) < 0.1;
                    })()
                JS, true);
        });
    }

    public function test_mention_selector_filters_projects_and_inserts_the_project_alias(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [
                                    { id: 9, name: 'Ana', type: 'user', type_label: 'Pessoa' },
                                    { id: 42, name: 'Projeto ] atual', type: 'project', type_label: 'Projeto' }
                                ]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'project-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#project-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#project-mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->click('[data-mention-filter="project"]')
                ->assertScript(
                    "document.querySelector('[data-mention-project-id=\"42\"]').textContent.trim()",
                    'Projeto ] atual'
                )
                ->click('[data-mention-project-id="42"]')
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#project-mention-editor')).editor.value()",
                    '@[Projeto \\] atual](mention:project:42)'
                );
        });
    }

    public function test_project_mention_selector_explains_context_and_global_search(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function (url) {
                    const term = new URL(url, window.location.origin).searchParams.get('term');
                    const contextual = {
                        id: 9,
                        name: 'Projeto relacionado',
                        type: 'project',
                        type_label: 'Projeto',
                        scope: 'contextual'
                    };
                    const global = {
                        id: 42,
                        name: 'Outro projeto acessível',
                        type: 'project',
                        type_label: 'Projeto',
                        scope: 'global'
                    };

                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: term === '' ? [] : [contextual, global]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'scoped-project-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#scoped-project-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#scoped-project-mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->click('[data-mention-filter="project"]')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const options = selector.querySelectorAll('[data-mention-target-type="project"]');
                        const hint = selector.querySelector('[data-mention-global-search-hint]');

                        return options.length === 0
                            && hint?.textContent.trim() === 'Digite o nome para buscar outros projetos'
                            && selector.querySelector('[data-mention-scope-label]') === null;
                    })()
                JS, true);

            $browser->script(<<<'JS'
                const entry = window.MarkdownEditors.get(document.querySelector('#scoped-project-mention-editor'));
                entry.editor.value('@outro');
                entry.editor.codemirror.setCursor({ line: 0, ch: 6 });
                entry.editor.toolbarElements.mention.click();
            JS);

            $browser
                ->waitFor('#mention-selector')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const labels = [...selector.querySelectorAll('[data-mention-scope-label]')]
                            .map((label) => label.textContent.trim());
                        const options = [...selector.querySelectorAll('[data-mention-target-type="project"]')];

                        return labels.length === 2
                            && labels[0] === 'Projetos relacionados'
                            && labels[1] === 'Outros projetos acessíveis'
                            && options.map((option) => option.dataset.mentionProjectId).join(',') === '9,42'
                            && selector.querySelector('[data-mention-filter="project"]')
                                ?.getAttribute('aria-pressed') === 'true'
                            && selector.querySelector('[data-mention-global-search-hint]') === null;
                    })()
                JS, true);
        });
    }

    public function test_task_mention_selector_explains_context_and_global_search(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function (url) {
                    const term = new URL(url, window.location.origin).searchParams.get('term');
                    const contextual = {
                        id: 9,
                        name: 'Tarefa relacionada',
                        type: 'task',
                        type_label: 'Tarefa',
                        scope: 'contextual'
                    };
                    const global = {
                        id: 42,
                        name: 'Outra tarefa acessível',
                        type: 'task',
                        type_label: 'Tarefa',
                        scope: 'global'
                    };

                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: term === '' ? [] : [contextual, global]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'scoped-task-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#scoped-task-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#scoped-task-mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->click('[data-mention-filter="task"]')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const options = selector.querySelectorAll('[data-mention-target-type="task"]');
                        const hint = selector.querySelector('[data-mention-global-search-hint]');

                        return options.length === 0
                            && hint?.textContent.trim() === 'Digite o nome para buscar outras tarefas'
                            && selector.querySelector('[data-mention-scope-label]') === null;
                    })()
                JS, true);

            $browser->script(<<<'JS'
                const entry = window.MarkdownEditors.get(document.querySelector('#scoped-task-mention-editor'));
                entry.editor.value('@outra');
                entry.editor.codemirror.setCursor({ line: 0, ch: 6 });
                entry.editor.toolbarElements.mention.click();
            JS);

            $browser
                ->waitFor('#mention-selector')
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const labels = [...selector.querySelectorAll('[data-mention-scope-label]')]
                            .map((label) => label.textContent.trim());
                        const options = [...selector.querySelectorAll('[data-mention-target-type="task"]')];

                        return labels.length === 2
                            && labels[0] === 'Tarefas relacionadas'
                            && labels[1] === 'Outras tarefas acessíveis'
                            && options.map((option) => option.dataset.mentionTaskId).join(',') === '9,42'
                            && selector.querySelector('[data-mention-filter="task"]')
                                ?.getAttribute('aria-pressed') === 'true'
                            && selector.querySelector('[data-mention-global-search-hint]') === null;
                    })()
                JS, true);
        });
    }

    public function test_task_mention_selector_shows_status_indicators_and_limits_the_list_height(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [
                                    {
                                        id: 1,
                                        name: 'Usuário de referência',
                                        type: 'user',
                                        type_label: 'Usuário',
                                        scope: 'contextual'
                                    },
                                    {
                                        id: 9,
                                        name: 'Tarefa em andamento',
                                        type: 'task',
                                        type_label: 'Tarefa',
                                        completed: false,
                                        scope: 'contextual'
                                    },
                                    {
                                        id: 10,
                                        name: 'Tarefa concluída',
                                        type: 'task',
                                        type_label: 'Tarefa',
                                        completed: true,
                                        scope: 'contextual'
                                    }
                                ]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'task-status-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#task-status-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#task-status-mention-editor')).editor.toolbarElements.mention.click();");

            $browser->waitFor('#mention-selector');
            $browser->script(<<<'JS'
                window.__mentionUserOptionHeight = document
                    .querySelector('[data-mention-target-type="user"]')
                    .getBoundingClientRect()
                    .height;
            JS);

            $browser->click('[data-mention-filter="task"]');
            $browser
                ->assertScript(<<<'JS'
                    (() => {
                        const selector = document.querySelector('#mention-selector');
                        const results = selector.querySelector('.mention-selector-results');
                        const options = [...selector.querySelectorAll('[data-mention-target-type="task"]')];
                        const active = selector.querySelector('[data-mention-task-id="9"]');
                        const completed = selector.querySelector('[data-mention-task-id="10"]');
                        const optionHeights = options.map((option) =>
                            option.getBoundingClientRect().height
                        );
                        const indicatorShapes = options.map((option) => {
                            const indicator = option.querySelector('.mention-task-status-indicator');
                            const bounds = indicator?.getBoundingClientRect();

                            return bounds && Math.abs(bounds.width - bounds.height) <= 1;
                        });

                        return getComputedStyle(selector).overflowY === 'auto'
                            && getComputedStyle(selector).maxHeight !== 'none'
                            && getComputedStyle(results).overflowY === 'auto'
                            && getComputedStyle(results).maxHeight !== 'none'
                            && selector.querySelector('[data-mention-task-section="active"]')
                                ?.textContent.trim() === 'Em andamento'
                            && selector.querySelector('[data-mention-task-section="completed"]')
                                ?.textContent.trim() === 'Concluídas'
                            && selector.querySelector('[data-mention-task-toggle]') === null
                            && options.map((option) => option.dataset.mentionTaskId).join(',') === '9,10'
                            && active?.classList.contains('mention-task-option--active')
                            && completed?.classList.contains('mention-task-option--completed')
                            && active?.querySelector('[data-mention-task-status="active"]')?.textContent === '●'
                            && completed?.querySelector('[data-mention-task-status="completed"]')?.textContent === '✓'
                            && optionHeights.every((height) =>
                                Math.abs(height - window.__mentionUserOptionHeight) <= 0.1
                            )
                            && indicatorShapes.every(Boolean);
                    })()
                JS, true);
        });
    }

    public function test_task_mention_selector_loads_all_tasks_and_scrolls_inside_results(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: Array.from({ length: 30 }, function (_, index) {
                                    return {
                                        id: index + 1,
                                        name: `Tarefa para rolagem ${index + 1}`,
                                        type: 'task',
                                        type_label: 'Tarefa',
                                        completed: index >= 20,
                                        scope: 'contextual'
                                    };
                                })
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'task-scroll-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#task-scroll-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#task-scroll-mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->click('[data-mention-filter="task"]')
                ->assertScript(<<<'JS'
                    (() => {
                        const results = document.querySelector('.mention-selector-results');
                        const options = results.querySelectorAll('[data-mention-target-type="task"]');
                        const initialScrollTop = results.scrollTop;

                        results.scrollTop = results.scrollHeight;

                        return options.length === 30
                            && results.scrollHeight > results.clientHeight
                            && results.scrollTop > initialScrollTop
                            && results.scrollTop + results.clientHeight >= results.scrollHeight - 1;
                    })()
                JS, true);
        });
    }

    public function test_mention_selector_filters_files_and_inserts_the_file_uuid_alias(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs(self::getUser('admin'))
                ->visit('/projects/create?project_type=organizacional')
                ->waitFor('.EasyMDEContainer');

            $browser->script(<<<'JS'
                window.fetch = function () {
                    return Promise.resolve({
                        ok: true,
                        json: function () {
                            return Promise.resolve({
                                results: [
                                    {
                                        id: '11111111-1111-4111-8111-111111111111',
                                        name: 'Decisão final',
                                        type: 'file',
                                        type_label: 'Arquivo'
                                    }
                                ]
                            });
                        }
                    });
                };
                const fixture = document.createElement('textarea');
                fixture.id = 'file-mention-editor';
                fixture.setAttribute('data-markdown-editor', '');
                fixture.setAttribute('data-markdown-profile', 'full');
                fixture.setAttribute('data-markdown-preview-url', '/markdown/preview');
                fixture.setAttribute('data-mention-search-url', '/mentions/selectable?context_type=project&context_id=1');
                document.body.appendChild(fixture);
            JS);

            $browser->waitFor('#file-mention-editor + .EasyMDEContainer');
            $browser->script("window.MarkdownEditors.get(document.querySelector('#file-mention-editor')).editor.toolbarElements.mention.click();");

            $browser
                ->waitFor('#mention-selector')
                ->click('[data-mention-filter="file"]')
                ->assertScript(
                    "document.querySelector('[data-mention-file-id=\"11111111-1111-4111-8111-111111111111\"]').textContent.trim()",
                    'Decisão final'
                )
                ->click('[data-mention-file-id="11111111-1111-4111-8111-111111111111"]')
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#file-mention-editor')).editor.value()",
                    '@[Decisão final](mention:file:11111111-1111-4111-8111-111111111111)'
                );
        });
    }

    private function toolbarHas(string $profile, string $button): string
    {
        return <<<JS
            (() => {
                const textarea = document.querySelector('[data-markdown-profile="{$profile}"]');
                const entry = textarea && window.MarkdownEditors.get(textarea);
                return Boolean(entry && entry.editor.toolbarElements['{$button}']);
            })()
        JS;
    }

    private function blockCdnAsset(Browser $browser, string $urlPattern): ChromeDevToolsDriver
    {
        $devTools = new ChromeDevToolsDriver($browser->driver);
        $devTools->execute('Network.enable');
        $devTools->execute('Network.setCacheDisabled', ['cacheDisabled' => true]);
        $devTools->execute('Network.clearBrowserCache');
        $devTools->execute('Network.setBlockedURLs', ['urls' => [$urlPattern]]);

        return $devTools;
    }

    private function restoreCdnAccess(ChromeDevToolsDriver $devTools): void
    {
        $devTools->execute('Network.setBlockedURLs', ['urls' => []]);
        $devTools->execute('Network.setCacheDisabled', ['cacheDisabled' => false]);
    }

    private function toolbarHasFor(string $selector, string $button): string
    {
        return <<<JS
            (() => {
                const textarea = document.querySelector('{$selector}');
                const entry = textarea && window.MarkdownEditors.get(textarea);
                return Boolean(entry && entry.editor.toolbarElements['{$button}']);
            })()
        JS;
    }

    private function openFileReferenceSelector(Browser $browser, string $textareaSelector): void
    {
        $selector = json_encode($textareaSelector, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            const textarea = document.querySelector({$selector});
            textarea.dispatchEvent(new CustomEvent('markdown-editor:file-reference', {
                bubbles: true,
                detail: { editor: window.MarkdownEditors.get(textarea).editor },
            }));
        JS);
    }

    private function previewScript(string $assertion): string
    {
        return <<<JS
            (() => {
                const textarea = document.querySelector('[data-markdown-profile="full"]');
                const preview = window.MarkdownEditors.get(textarea).preview.element();
                return preview.{$assertion};
            })()
        JS;
    }

    private function fillEditorAndPreview(
        Browser $browser,
        string $textareaSelector,
        string $markdown,
        string $expectedText,
    ): void {
        $textarea = json_encode($textareaSelector, JSON_THROW_ON_ERROR);
        $value = json_encode($markdown, JSON_THROW_ON_ERROR);
        $expected = json_encode($expectedText, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            const textarea = document.querySelector({$textarea});
            const entry = window.MarkdownEditors.get(textarea);
            entry.editor.value({$value});
            entry.editor.toolbarElements.preview.click();
        JS);

        $browser->waitUntil(<<<JS
            (() => {
                const textarea = document.querySelector({$textarea});
                const entry = textarea && window.MarkdownEditors.get(textarea);
                const preview = entry && entry.preview.element();

                return Boolean(
                    preview
                    && preview.textContent.includes({$expected})
                    && !preview.querySelector('script')
                );
            })()
        JS);
    }

    private function fullToolbarLayoutIsCorrect(): string
    {
        return <<<'JS'
            (() => {
                const textarea = document.querySelector('[data-markdown-profile="full"]');
                const editor = window.MarkdownEditors.get(textarea).editor;
                const toolbar = editor.toolbar_div;
                const bold = editor.toolbarElements.bold;
                const table = editor.toolbarElements.table;
                const preview = editor.toolbarElements.preview;
                const toolbarBounds = toolbar.getBoundingClientRect();
                const previewBounds = preview.getBoundingClientRect();
                const previewStyle = window.getComputedStyle(preview);
                const previewRightOffset = toolbarBounds.right - previewBounds.right;

                return table.offsetWidth < toolbar.clientWidth / 2
                    && table.offsetTop === bold.offsetTop
                    && previewRightOffset >= 0
                    && previewRightOffset <= 12
                    && previewStyle.backgroundColor !== 'rgba(0, 0, 0, 0)'
                    && previewStyle.color !== 'rgb(0, 0, 0)';
            })()
        JS;
    }

    private function editorOption(string $option): string
    {
        return <<<JS
            (() => {
                const textarea = document.querySelector('[data-markdown-profile="full"]');
                return window.MarkdownEditors.get(textarea).editor.options.{$option};
            })()
        JS;
    }
}
