<?php

namespace App\Services\Mentions;

use App\Morphs\MentionMap;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
        $mentionedUserIds = [];

        foreach ($this->references($markdown) as $reference) {
            if ($reference->type !== 'user') {
                continue;
            }

            $mentionedUserIds[(int) $reference->key] = true;
        }

        return array_map('intval', array_keys($mentionedUserIds));
    }

    /**
     * @return list<MentionReference>
     */
    public function references(?string $markdown, bool $strict = true): array
    {
        if ($markdown === null || $markdown === '') {
            return [];
        }

        $references = [];
        $walker = new NodeWalker($this->parser->parse($markdown));

        while ($event = $walker->next()) {
            $node = $event->getNode();

            if (! $event->isEntering() || ! $node instanceof Link) {
                continue;
            }

            $previous = $node->previous();

            if (! $previous instanceof Text || ! str_ends_with($previous->getLiteral(), '@')) {
                continue;
            }

            $url = $node->getUrl();

            if (! str_starts_with($url, 'mention:')) {
                continue;
            }

            $parts = explode(':', $url, 3);

            $validKey = count($parts) === 3 && ($parts[1] === 'file'
                ? Str::isUuid($parts[2])
                : preg_match('/^[1-9][0-9]*$/', $parts[2]) === 1);

            if (count($parts) !== 3
                || $parts[0] !== 'mention'
                || MentionMap::resolveTargetClass($parts[1]) === null
                || ! $validKey) {
                if (! $strict) {
                    continue;
                }

                throw new InvalidArgumentException('Sintaxe de Menção inválida.');
            }

            $reference = new MentionReference($parts[1], $parts[2]);
            $references[$reference->identity()] = $reference;
        }

        return array_values($references);
    }
}
