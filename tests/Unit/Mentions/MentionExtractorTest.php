<?php

namespace Tests\Unit\Mentions;

use App\Services\Mentions\MentionExtractor;
use InvalidArgumentException;
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

    public function test_it_accepts_markdown_escaped_project_labels(): void
    {
        $extractor = new MentionExtractor();

        $references = $extractor->references('@[Projeto \\] atual](mention:project:42)');

        $this->assertCount(1, $references);
        $this->assertSame('project:42', $references[0]->identity());
    }

    public function test_it_accepts_a_file_mention_with_a_public_uuid(): void
    {
        $extractor = new MentionExtractor();

        $references = $extractor->references(
            '@[Arquivo](mention:file:11111111-1111-4111-8111-111111111111)',
        );

        $this->assertCount(1, $references);
        $this->assertSame(
            'file:11111111-1111-4111-8111-111111111111',
            $references[0]->identity(),
        );
    }

    public function test_it_rejects_a_malformed_file_uuid_as_mention_syntax(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MentionExtractor())->references('@[Arquivo](mention:file:nao-e-uuid)');
    }
}
