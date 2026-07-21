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
                    "document.querySelector('[data-markdown-profile=\"full\"]') !== null",
                    true
                )
                ->assertScript($this->toolbarHas('full', 'heading'), true)
                ->assertScript($this->toolbarHas('full', 'task-list'), true)
                ->assertScript($this->toolbarHas('full', 'table'), true)
                ->assertScript($this->toolbarHas('full', 'mention'), true)
                ->assertScript($this->toolbarHas('full', 'file-reference'), true)
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
                ->assertScript($this->toolbarHas('compact', 'file-reference'), true)
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
