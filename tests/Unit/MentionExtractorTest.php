<?php

namespace Tests\Unit;

use App\Services\MentionExtractor;
use PHPUnit\Framework\TestCase;

class MentionExtractorTest extends TestCase
{
    public function test_it_extracts_valid_user_mentions_from_markdown_nodes_and_normalizes_duplicates(): void
    {
        $extractor = new MentionExtractor();

        $this->assertSame(
            [42, 7],
            $extractor->extract(
                "@[Ana histórica](mention:user:42) e @[Bruno](mention:user:7) e @[Ana](mention:user:42).\n\n`@[Código](mention:user:99)`"
            )
        );
    }

    public function test_it_ignores_links_that_do_not_use_the_exact_mention_syntax(): void
    {
        $extractor = new MentionExtractor();

        $this->assertSame(
            [],
            $extractor->extract(
                '[Sem id](mention:user:zero) [Outro tipo](mention:project:7) [Espaço](mention:user: 7)'
            )
        );
    }
}
