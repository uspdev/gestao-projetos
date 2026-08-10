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

    public function test_it_copies_sources_and_builds_browser_entries_without_npm(): void
    {
        $resourcesPath = $this->temporaryPath.'/resources';
        $publicPath = $this->temporaryPath.'/public';

        $this->write($resourcesPath.'/js/app.js', "const feature = require('./feature');\nwindow.result = feature;\n");
        $this->write($resourcesPath.'/js/feature.js', "module.exports = require('./nested/value');\n");
        $this->write($resourcesPath.'/js/nested/value.js', "module.exports = 'publicado';\n");
        $this->write($resourcesPath.'/js/standalone.js', "window.standalone = true;\n");
        $this->write($resourcesPath.'/css/app.css', "@import \"./base.css\";\n.app { color: blue; }\n");
        $this->write($resourcesPath.'/css/base.css', ".base { color: red; }\n");

        $result = (new AssetPublisher($this->files))->publish($resourcesPath, $publicPath);

        self::assertSame(6, $result['files']);
        self::assertSame(
            "window.standalone = true;\n",
            $this->files->get($publicPath.'/js/standalone.js'),
        );
        self::assertSame(
            "module.exports = 'publicado';\n",
            $this->files->get($publicPath.'/js/nested/value.js'),
        );

        $javascript = $this->files->get($publicPath.'/js/app.js');
        self::assertStringContainsString('"feature.js": function', $javascript);
        self::assertStringContainsString('"nested/value.js": function', $javascript);
        self::assertStringContainsString('require("feature.js")', $javascript);
        self::assertStringNotContainsString("require('./feature')", $javascript);

        $css = $this->files->get($publicPath.'/css/app.css');
        self::assertStringContainsString('.base { color: red; }', $css);
        self::assertStringContainsString('.app { color: blue; }', $css);
        self::assertStringNotContainsString('@import', $css);

        $manifest = json_decode(
            $this->files->get($publicPath.'/mix-manifest.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame(
            '/js/app.js?id='.md5_file($publicPath.'/js/app.js'),
            $manifest['/js/app.js'],
        );
        self::assertSame(
            '/css/app.css?id='.md5_file($publicPath.'/css/app.css'),
            $manifest['/css/app.css'],
        );
    }

    public function test_it_rejects_modules_outside_the_javascript_source_directory(): void
    {
        $resourcesPath = $this->temporaryPath.'/resources';
        $publicPath = $this->temporaryPath.'/public';

        $this->write($resourcesPath.'/js/app.js', "require('../../outside');\n");
        $this->write($resourcesPath.'/css/app.css', ".app { color: blue; }\n");
        $this->write($this->temporaryPath.'/outside.js', "module.exports = {};\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('fora do diretório permitido');

        (new AssetPublisher($this->files))->publish($resourcesPath, $publicPath);
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
            '@php artisan assets:publish --ansi',
            $scripts[array_key_last($scripts)],
        );
    }

    private function write(string $path, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
    }
}
