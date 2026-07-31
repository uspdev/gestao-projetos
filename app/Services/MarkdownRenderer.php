<?php

namespace App\Services;

use App\Markdown\SafeUrlRenderer;
use App\Markdown\UrlPolicy;
use App\Services\Mentions\MentionManager;
use Closure;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\MarkdownConverter;

class MarkdownRenderer
{
    private MarkdownConverter $converter;

    /**
     * @param Closure(string, string): ?array<string, mixed>|null $mentionResolver
     * @param Closure(string): string|null $urlResolver
     */
    public function __construct(?Closure $mentionResolver = null, ?Closure $urlResolver = null)
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $urlPolicy = new UrlPolicy();
        $safeUrlRenderer = new SafeUrlRenderer(
            $urlPolicy,
            $mentionResolver ?? function (string $type, string $key): ?array {
                return app(MentionManager::class)->present($type, $key, Auth::user());
            },
            $urlResolver
        );
        $environment->addRenderer(Link::class, $safeUrlRenderer, 10);
        $environment->addRenderer(Image::class, $safeUrlRenderer, 10);
        $environment->addRenderer(Text::class, $safeUrlRenderer, 10);

        $this->converter = new MarkdownConverter($environment);
    }

    public function render(?string $markdown): string
    {
        if ($markdown === null || $markdown === '') {
            return '';
        }

        return (string) $this->converter->convert($markdown);
    }
}
