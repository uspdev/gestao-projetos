<?php

namespace App\Markdown;

use Closure;
use League\CommonMark\Exception\InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

class SafeUrlRenderer implements NodeRendererInterface
{
    //Closure é uma função que pode ser guardada em uma variável, passada como argumento e executada depois.
    /** @var Closure(string, string): ?array<string, mixed> */
    private Closure $mentionResolver;

    /** @var Closure(string): string */
    private Closure $urlResolver;

    /**
     * @param Closure(string, string): ?array<string, mixed> $mentionResolver
     * @param Closure(string): string $urlResolver
     */
    public function __construct(
        private UrlPolicy $urlPolicy,
        ?Closure $mentionResolver = null,
        ?Closure $urlResolver = null
    ) {
        $this->mentionResolver = $mentionResolver ?? fn (string $type, string $key): ?array => null;
        $this->urlResolver = $urlResolver ?? fn (string $url): string => $url;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string
    {
        if ($node instanceof Text) {
            return $this->renderText($node);
        }

        if (! $node instanceof Link && ! $node instanceof Image) {
            throw new InvalidArgumentException('Expected a link or image node.');
        }

        if ($node instanceof Link && ($mention = $this->mentionReference($node)) !== null) {
            return $this->renderMention($mention['type'], $mention['key']);
        }

        $contents = $childRenderer->renderNodes($node->children());

        if (($node instanceof Image && $node->parent() instanceof Link)
            || ! $this->urlPolicy->allows($node->getUrl())) {
            return $contents;
        }

        $attributes = ['href' => ($this->urlResolver)($node->getUrl())];

        if (! str_starts_with($node->getUrl(), '#')) {
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'noopener noreferrer';
        }

        if (($title = $node->getTitle()) !== null) {
            $attributes['title'] = $title;
        }

        return new HtmlElement('a', $attributes, $contents);
    }

    /**
     * @return array{type: string, key: string}|null
     */
    private function mentionReference(Link $link): ?array
    {
        if (preg_match('/^mention:([^:]+):([^:]+)$/', $link->getUrl(), $matches) !== 1
            || ! $link->previous() instanceof Text
            || ! str_ends_with($link->previous()->getLiteral(), '@')) {
            return null;
        }

        return [
            'type' => $matches[1],
            'key' => $matches[2],
        ];
    }

    private function renderMention(string $type, string $key): \Stringable|string
    {
        $mention = $this->resolveMention($type, $key);

        if ($mention === null) {
            return 'Menção indisponível';
        }

        $status = $mention['status'] ?? 'available';

        if ($status === 'missing') {
            return (string) ($mention['message'] ?? 'Menção: destino não encontrado');
        }

        if ($status === 'forbidden') {
            return (string) ($mention['message'] ?? 'Menção: você não tem permissão para visualizar');
        }

        $label = (string) ($mention['label'] ?? $mention['name'] ?? '');
        $accessibleName = (string) ($mention['accessible_name'] ?? ('destino: ' . $label));

        return new HtmlElement('a', [
            'href' => $mention['url'],
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'class' => 'markdown-mention',
            'aria-label' => $accessibleName,
            'title' => $accessibleName,
        ], htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    private function renderText(Text $text): string
    {
        $next = $text->next();

        if ($next instanceof Link && ($mention = $this->mentionReference($next)) !== null) {
            $resolved = $this->resolveMention($mention['type'], $mention['key']);
            $status = $resolved['status'] ?? ($resolved === null ? 'missing' : 'available');

            if ($status === 'missing' || $status === 'forbidden') {
                return Xml::escape(substr($text->getLiteral(), 0, -1));
            }
        }

        return Xml::escape($text->getLiteral());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMention(string $type, string $key): ?array
    {
        return ($this->mentionResolver)($type, $key);
    }
}
