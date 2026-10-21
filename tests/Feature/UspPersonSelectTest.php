<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class UspPersonSelectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag());
    }

    public function test_member_and_requester_have_independent_modes_and_load_one_script(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-form.usp-person-select name="codpes" label="Usuário" url="/members/selectable"
                :required="true" :auto-open="true" value="1234567" value-label="1234567 Maria" />
            <x-form.usp-person-select name="requester" label="Solicitante" url="/requesters/selectable"
                :allow-free-text="true" value-type="text" value="Secretaria & apoio"
                :query-fields="['task_id' => 'task_id']" />
            @stack('scripts')
            BLADE);
        $xpath = $this->document($html);
        $member = $xpath->query('//select[@name="codpes"]')->item(0);
        $requester = $xpath->query('//select[@name="requester"]')->item(0);

        $this->assertSame('id', $member->getAttribute('data-value-type'));
        $this->assertSame('0', $member->getAttribute('data-allow-free-text'));
        $this->assertSame('1', $member->getAttribute('data-auto-open'));
        $this->assertTrue($member->hasAttribute('required'));
        $this->assertSame('1234567', $xpath->query('//select[@name="codpes"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('1234567 Maria', $xpath->query('//select[@name="codpes"]/option[@selected]')->item(0)->textContent);

        $this->assertSame('text', $requester->getAttribute('data-value-type'));
        $this->assertSame('1', $requester->getAttribute('data-allow-free-text'));
        $this->assertFalse($requester->hasAttribute('required'));
        $this->assertSame('/requesters/selectable', $requester->getAttribute('data-search-url'));
        $this->assertSame(['task_id' => 'task_id'], json_decode($requester->getAttribute('data-query-fields'), true));
        $this->assertSame('Secretaria & apoio', $xpath->query('//select[@name="requester"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertNotSame($member->getAttribute('id'), $requester->getAttribute('id'));
        $scripts = $xpath->query('//script[@data-usp-person-select-script and not(@src)]');
        $this->assertSame(1, $scripts->length);
        $this->assertStringContainsString('window.UspPersonSelect =', $scripts->item(0)->textContent);
        $this->assertStringNotContainsString('/js/components/usp-person-select.js', $html);
    }

    public function test_validation_preserves_old_value_and_escapes_the_selected_label(): void
    {
        request()->setLaravelSession(app('session')->driver());
        session()->flash('_old_input', ['requester' => 'Equipe <externa> & apoio']);
        View::share('errors', (new ViewErrorBag())->put('default', new MessageBag([
            'requester' => 'Solicitante inválido.',
        ])));

        $html = Blade::render(<<<'BLADE'
            <x-form.usp-person-select name="requester" id="task-requester" label="Solicitante"
                url="/people" :allow-free-text="true" value-type="text" value="Texto anterior"
                value-label="Rótulo anterior" help="Nome, número USP ou texto livre." />
            BLADE);
        $xpath = $this->document($html);
        $select = $xpath->query('//select[@name="requester"]')->item(0);
        $selected = $xpath->query('//select[@name="requester"]/option[@selected]')->item(0);

        $this->assertSame('Equipe <externa> & apoio', $selected->getAttribute('value'));
        $this->assertSame('Equipe <externa> & apoio', $selected->textContent);
        $this->assertSame(0, $xpath->query('//externa')->length);
        $this->assertSame('true', $select->getAttribute('aria-invalid'));
        $this->assertStringContainsString('is-invalid', $select->getAttribute('class'));
        $this->assertSame('task-requester-help', $select->getAttribute('aria-describedby'));
        $this->assertStringContainsString('Solicitante inválido.', $html);
        $this->assertSame('task-requester', $xpath->query('//label')->item(0)->getAttribute('for'));
    }

    public function test_empty_field_does_not_submit_a_placeholder_as_its_value(): void
    {
        $html = Blade::render('<x-form.usp-person-select name="codpes" label="Usuário" url="/people" />');
        $xpath = $this->document($html);

        $this->assertSame(1, $xpath->query('//select[@name="codpes"]/option')->length);
        $this->assertSame('', $xpath->query('//select/option')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//select/option')->item(0)->textContent);
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }
}
