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

class SafeUrlRenderer implements NodeRendererInterface
{
    //Closure é uma função que pode ser guardada em uma variável, passada como argumento e executada depois.
    /** @var Closure(int): ?array{name: string, url: string} */
    private Closure $mentionResolver;

    /** @var Closure(string): string */
    private Closure $urlResolver;

    /**
     * @param Closure(int): ?array{name: string, url: string} $mentionResolver
     * @param Closure(string): string $urlResolver
     */
    public function __construct(
        private UrlPolicy $urlPolicy,
        ?Closure $mentionResolver = null,
        ?Closure $urlResolver = null
    ) {
        $this->mentionResolver = $mentionResolver ?? fn (): ?array => null;
        $this->urlResolver = $urlResolver ?? fn (string $url): string => $url;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string
    {
        if (! $node instanceof Link && ! $node instanceof Image) {
            throw new InvalidArgumentException('Expected a link or image node.');
        }

        if ($node instanceof Link && ($mentionedUserId = $this->mentionedUserId($node)) !== null) {
            return $this->renderMention($mentionedUserId);
        }

        $contents = $childRenderer->renderNodes($node->children());

        if (($node instanceof Image && $node->parent() instanceof Link)
            || ! $this->urlPolicy->allows($node->getUrl())) {
            return $contents;
        }

        $attributes = [
            'href' => ($this->urlResolver)($node->getUrl()),
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ];

        if (($title = $node->getTitle()) !== null) {
            $attributes['title'] = $title;
        }

        return new HtmlElement('a', $attributes, $contents);
    }

    private function mentionedUserId(Link $link): ?int
    {
        if (preg_match('/^mention:user:([1-9][0-9]*)$/', $link->getUrl(), $matches) !== 1
            || ! $link->previous() instanceof Text
            || ! str_ends_with($link->previous()->getLiteral(), '@')) {
            return null;
        }

        return (int) $matches[1];
    }

    private function renderMention(int $userId): \Stringable|string
    {
        $mention = ($this->mentionResolver)($userId);

        if ($mention === null) {
            return 'Usuário indisponível';
        }

        return new HtmlElement('a', [
            'href' => $mention['url'],
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
            'class' => 'markdown-mention',
        ], htmlspecialchars($mention['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
}
