<?php

namespace Tests\Unit;

use App\Support\AssetPublisher;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AssetPublisherTest extends TestCase
{
    private Filesystem $files;

    private string $temporaryPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->temporaryPath = sys_get_temp_dir().'/gestao-projetos-assets-'.bin2hex(random_bytes(8));
        $this->files->makeDirectory($this->temporaryPath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->temporaryPath);

        parent::tearDown();
    }

    public function test_it_copies_javascript_and_css_without_transforming_the_sources(): void
    {
        $resourcesPath = $this->temporaryPath.'/resources';
        $publicPath = $this->temporaryPath.'/public';
        $javascript = "import { feature } from './nested/feature.js';\nwindow.result = feature;\n";
        $feature = "export const feature = 'publicado';\n";
        $css = ".app { color: blue; }\n";

        $this->write($resourcesPath.'/js/app.js', $javascript);
        $this->write($resourcesPath.'/js/nested/feature.js', $feature);
        $this->write($resourcesPath.'/css/app.css', $css);

        $result = (new AssetPublisher($this->files))->publish($resourcesPath, $publicPath);

        self::assertSame(['files' => 3], $result);
        self::assertSame($javascript, $this->files->get($publicPath.'/js/app.js'));
        self::assertSame($feature, $this->files->get($publicPath.'/js/nested/feature.js'));
        self::assertSame($css, $this->files->get($publicPath.'/css/app.css'));
        self::assertFileDoesNotExist($publicPath.'/mix-manifest.json');
    }

    public function test_it_requires_both_asset_directories(): void
    {
        $resourcesPath = $this->temporaryPath.'/resources';
        $publicPath = $this->temporaryPath.'/public';

        $this->write($resourcesPath.'/js/app.js', "window.app = true;\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Diretório de ativos não encontrado');

        try {
            (new AssetPublisher($this->files))->publish($resourcesPath, $publicPath);
        } finally {
            self::assertDirectoryDoesNotExist($publicPath.'/js');
        }
    }

    public function test_application_assets_do_not_depend_on_commonjs_or_css_imports(): void
    {
        foreach ($this->files->allFiles(__DIR__.'/../../resources/js') as $file) {
            $source = $file->getContents();

            self::assertDoesNotMatchRegularExpression('/\\brequire\\s*\\(/', $source, $file->getPathname());
            self::assertStringNotContainsString('module.exports', $source, $file->getPathname());
        }

        foreach ($this->files->allFiles(__DIR__.'/../../resources/css') as $file) {
            self::assertDoesNotMatchRegularExpression(
                '/^\\s*@import\\b/m',
                $file->getContents(),
                $file->getPathname(),
            );
        }
    }

    public function test_composer_publishes_assets_after_refreshing_the_package_assets(): void
    {
        $composer = json_decode(
            $this->files->get(__DIR__.'/../../composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $scripts = $composer['scripts']['post-autoload-dump'];

        self::assertSame(
            '@php artisan projetos:assets-publish --ansi',
            $scripts[array_key_last($scripts)],
        );
    }

    private function write(string $path, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
    }
}
