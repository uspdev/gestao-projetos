<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeWalker;
use League\CommonMark\Parser\MarkdownParser;

class MentionExtractor
{
    private MarkdownParser $parser;

    public function __construct()
    {
        $environment = new Environment([
            'max_nesting_level' => 20,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @return list<int>
     */
    public function extract(?string $markdown): array
    {
        if ($markdown === null || $markdown === '') {
            return [];
        }

        $mentionedUserIds = [];
        $walker = new NodeWalker($this->parser->parse($markdown));

        while ($event = $walker->next()) {
            $node = $event->getNode();

            if (! $event->isEntering() || ! $node instanceof Link) {
                continue;
            }

            if (preg_match('/^mention:user:([1-9][0-9]*)$/', $node->getUrl(), $matches) !== 1
                || ! $node->previous() instanceof Text
                || ! str_ends_with($node->previous()->getLiteral(), '@')) {
                continue;
            }

            $mentionedUserIds[(int) $matches[1]] = true;
        }

        return array_map('intval', array_keys($mentionedUserIds));
    }
}
