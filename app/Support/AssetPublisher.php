<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class AssetPublisher
{
    private const REQUIRE_PATTERN = '~\brequire\(\s*(["\'])(\.\.?/[^"\']+)\1\s*\)~';

    private const CSS_IMPORT_PATTERN = '~@import\s+(?:url\(\s*)?(["\'])(\.\.?/[^"\']+\.css)\1\s*\)?\s*;~';

    public function __construct(private Filesystem $files)
    {
    }

    /**
     * @return array{files: int, javascript: string, css: string, manifest: string}
     */
    public function publish(string $resourcesPath, string $publicPath): array
    {
        $javascriptSource = $resourcesPath.'/js';
        $cssSource = $resourcesPath.'/css';

        $this->assertDirectoryExists($javascriptSource);
        $this->assertDirectoryExists($cssSource);

        $javascript = $this->bundleJavascript($javascriptSource.'/app.js', $javascriptSource);
        $css = $this->bundleCss($cssSource.'/app.css', $cssSource);
        $publishedFiles = 0;

        foreach (['js', 'css'] as $directory) {
            $source = $resourcesPath.'/'.$directory;
            $destination = $publicPath.'/'.$directory;
            $publishedFiles += count($this->files->allFiles($source));

            $this->files->ensureDirectoryExists($destination);

            if (! $this->files->copyDirectory($source, $destination)) {
                throw new RuntimeException(sprintf(
                    'Não foi possível copiar os ativos de %s para %s.',
                    $source,
                    $destination,
                ));
            }
        }

        $javascriptDestination = $publicPath.'/js/app.js';
        $cssDestination = $publicPath.'/css/app.css';
        $manifestDestination = $publicPath.'/mix-manifest.json';

        $this->write($javascriptDestination, $javascript);
        $this->write($cssDestination, $css);
        $this->writeManifest($manifestDestination, $javascriptDestination, $cssDestination);

        return [
            'files' => $publishedFiles,
            'javascript' => $javascriptDestination,
            'css' => $cssDestination,
            'manifest' => $manifestDestination,
        ];
    }

    private function bundleJavascript(string $entryPath, string $rootPath): string
    {
        $modules = [];
        $rootPath = $this->canonicalPath($rootPath, $rootPath);
        $entryPath = $this->canonicalPath($entryPath, $rootPath);

        $this->collectJavascriptModule($entryPath, $rootPath, $modules);

        $definitions = [];

        foreach ($modules as $id => $source) {
            $definitions[] = sprintf(
                "    %s: function (module, exports, require) {\n%s\n    }",
                json_encode($id, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                $source,
            );
        }

        $entryId = $this->relativePath($entryPath, $rootPath);

        return sprintf(
            <<<'JAVASCRIPT'
/* Gerado por `php artisan assets:publish`. Não edite este arquivo diretamente. */
(function (modules) {
    "use strict";

    var cache = {};

    function require(id) {
        if (Object.prototype.hasOwnProperty.call(cache, id)) {
            return cache[id].exports;
        }

        if (!Object.prototype.hasOwnProperty.call(modules, id)) {
            throw new Error("Módulo não encontrado: " + id);
        }

        var module = { exports: {} };
        cache[id] = module;
        modules[id](module, module.exports, require);

        return module.exports;
    }

    require(%s);
})({
%s
});

JAVASCRIPT,
            json_encode($entryId, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            implode(",\n", $definitions),
        );
    }

    /**
     * @param  array<string, string|null>  $modules
     */
    private function collectJavascriptModule(string $path, string $rootPath, array &$modules): void
    {
        $id = $this->relativePath($path, $rootPath);

        if (array_key_exists($id, $modules)) {
            return;
        }

        $source = $this->read($path);
        $modules[$id] = null;
        $directory = dirname($path);

        $source = preg_replace_callback(
            self::REQUIRE_PATTERN,
            function (array $matches) use ($directory, $rootPath, &$modules): string {
                $dependencyPath = $directory.'/'.$matches[2];

                if (pathinfo($dependencyPath, PATHINFO_EXTENSION) === '') {
                    $dependencyPath .= '.js';
                }

                $dependencyPath = $this->canonicalPath($dependencyPath, $rootPath);
                $this->collectJavascriptModule($dependencyPath, $rootPath, $modules);

                return sprintf(
                    'require(%s)',
                    json_encode(
                        $this->relativePath($dependencyPath, $rootPath),
                        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                    ),
                );
            },
            $source,
        );

        if (! is_string($source)) {
            throw new RuntimeException('Não foi possível processar os módulos JavaScript.');
        }

        $modules[$id] = $source;
    }

    private function bundleCss(string $entryPath, string $rootPath): string
    {
        $rootPath = $this->canonicalPath($rootPath, $rootPath);
        $entryPath = $this->canonicalPath($entryPath, $rootPath);
        $stack = [];

        return "/* Gerado por `php artisan assets:publish`. Não edite este arquivo diretamente. */\n"
            .$this->inlineCssImports($entryPath, $rootPath, $stack);
    }

    /**
     * @param  array<string, true>  $stack
     */
    private function inlineCssImports(string $path, string $rootPath, array &$stack): string
    {
        $id = $this->relativePath($path, $rootPath);

        if (isset($stack[$id])) {
            throw new RuntimeException('Importação CSS circular detectada em '.$id.'.');
        }

        $stack[$id] = true;
        $directory = dirname($path);
        $source = $this->read($path);

        $source = preg_replace_callback(
            self::CSS_IMPORT_PATTERN,
            function (array $matches) use ($directory, $rootPath, &$stack): string {
                $dependencyPath = $this->canonicalPath($directory.'/'.$matches[2], $rootPath);
                $dependencyId = $this->relativePath($dependencyPath, $rootPath);

                return sprintf(
                    "/* Início de %s */\n%s\n/* Fim de %s */",
                    $dependencyId,
                    rtrim($this->inlineCssImports($dependencyPath, $rootPath, $stack)),
                    $dependencyId,
                );
            },
            $source,
        );

        unset($stack[$id]);

        if (! is_string($source)) {
            throw new RuntimeException('Não foi possível processar as importações CSS.');
        }

        return rtrim($source)."\n";
    }

    private function writeManifest(
        string $manifestPath,
        string $javascriptPath,
        string $cssPath,
    ): void {
        $manifest = [
            '/js/app.js' => '/js/app.js?id='.$this->hash($javascriptPath),
            '/css/app.css' => '/css/app.css?id='.$this->hash($cssPath),
        ];

        $this->write(
            $manifestPath,
            json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            )."\n",
        );
    }

    private function hash(string $path): string
    {
        $hash = md5_file($path);

        if ($hash === false) {
            throw new RuntimeException('Não foi possível versionar o ativo '.$path.'.');
        }

        return $hash;
    }

    private function read(string $path): string
    {
        $contents = $this->files->get($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Não foi possível ler o ativo '.$path.'.');
        }

        return $contents;
    }

    private function write(string $path, string $contents): void
    {
        if ($this->files->put($path, $contents) === false) {
            throw new RuntimeException('Não foi possível publicar o ativo '.$path.'.');
        }
    }

    private function canonicalPath(string $path, string $rootPath): string
    {
        $canonicalPath = realpath($path);
        $canonicalRoot = realpath($rootPath);

        if ($canonicalPath === false || $canonicalRoot === false) {
            throw new RuntimeException('Ativo não encontrado: '.$path.'.');
        }

        if ($canonicalPath !== $canonicalRoot
            && ! str_starts_with($canonicalPath, $canonicalRoot.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('O ativo está fora do diretório permitido: '.$path.'.');
        }

        return $canonicalPath;
    }

    private function relativePath(string $path, string $rootPath): string
    {
        return str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            ltrim(substr($path, strlen($rootPath)), DIRECTORY_SEPARATOR),
        );
    }

    private function assertDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            throw new RuntimeException('Diretório de ativos não encontrado: '.$path.'.');
        }
    }
}
