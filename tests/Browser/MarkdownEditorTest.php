<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MarkdownEditorTest extends DuskTestCase
{
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
                ->assertScript($this->toolbarHas('full', 'markdown-help'), true)
                ->assertScript($this->toolbarHas('full', 'fullscreen'), false)
                ->assertScript($this->toolbarHas('full', 'side-by-side'), false)
                ->assertScript($this->toolbarHas('full', 'image'), false)
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

    public function test_file_reference_selector_inserts_the_historical_markdown_link_after_selection(): void
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
                ->assertScript($this->toolbarHasFor('#file-reference-editor', 'file-reference'), true);

            $browser->script("window.MarkdownEditors.get(document.querySelector('#file-reference-editor')).editor.toolbarElements['file-reference'].click();");

            $browser
                ->waitFor('#file-reference-selector')
                ->click('[data-file-reference-uuid="11111111-1111-4111-8111-111111111111"]')
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#file-reference-editor')).editor.value()",
                    '[Decisão registrada](/files/11111111-1111-4111-8111-111111111111)'
                );
        });
    }

    public function test_file_reference_selector_shares_with_the_meeting_before_inserting_the_link(): void
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
                                    markdown: '[Arquivo da pauta](/files/22222222-2222-4222-8222-222222222222)'
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
            $browser->script("window.MarkdownEditors.get(document.querySelector('#meeting-file-reference-editor')).editor.toolbarElements['file-reference'].click();");

            $browser->waitFor('#file-reference-selector')
                ->assertSeeIn('#file-reference-selector', 'Projeto na pauta: Gestão Projetos')
                ->assertSeeIn('#file-reference-selector', 'Tarefa na pauta: Revisar documentação')
                ->click('[data-file-share-uuid="22222222-2222-4222-8222-222222222222"]')
                ->assertScript(
                    "window.MarkdownEditors.get(document.querySelector('#meeting-file-reference-editor')).editor.value()",
                    '[Arquivo da pauta](/files/22222222-2222-4222-8222-222222222222)'
                )
                ->assertScript('window.fileReferenceRequests.length', 2)
                ->assertScript("window.fileReferenceRequests[1].url", '/meetings/1/file-shares')
                ->assertScript("JSON.parse(window.fileReferenceRequests[1].options.body).media_uuid", '22222222-2222-4222-8222-222222222222');
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
