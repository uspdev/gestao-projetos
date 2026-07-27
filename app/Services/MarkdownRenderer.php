<?php

namespace App\Services;

use App\Models\User;
use App\Markdown\SafeUrlRenderer;
use App\Markdown\UrlPolicy;
use Closure;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownRenderer
{
    private MarkdownConverter $converter;

    /**
     * @param Closure(int): ?array{name: string, url: string}|null $mentionResolver
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
            $mentionResolver ?? function (int $userId): ?array {
                $mentionedUser = User::query()->find($userId);
                $reader = Auth::user();

                if (! $mentionedUser || ! $reader || ! $reader->can('view', $mentionedUser)) {
                    return null;
                }

                return [
                    'name' => $mentionedUser->name,
                    'url' => route('users.show', $mentionedUser),
                ];
            },
            $urlResolver
        );
        $environment->addRenderer(Link::class, $safeUrlRenderer, 10);
        $environment->addRenderer(Image::class, $safeUrlRenderer, 10);

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
