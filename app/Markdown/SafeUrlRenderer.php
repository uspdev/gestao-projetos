<?php

namespace App\Markdown;

use League\CommonMark\Exception\InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class SafeUrlRenderer implements NodeRendererInterface
{
    public function __construct(private UrlPolicy $urlPolicy)
    {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string
    {
        if (! $node instanceof Link && ! $node instanceof Image) {
            throw new InvalidArgumentException('Expected a link or image node.');
        }

        $contents = $childRenderer->renderNodes($node->children());

        if (($node instanceof Image && $node->parent() instanceof Link)
            || ! $this->urlPolicy->allows($node->getUrl())) {
            return $contents;
        }

        $attributes = [
            'href' => $node->getUrl(),
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ];

        if (($title = $node->getTitle()) !== null) {
            $attributes['title'] = $title;
        }

        return new HtmlElement('a', $attributes, $contents);
    }
}
