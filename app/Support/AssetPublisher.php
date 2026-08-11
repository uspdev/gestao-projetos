<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class AssetPublisher
{
    public function __construct(private Filesystem $files)
    {
    }

    /**
     * @return array{files: int}
     */
    public function publish(string $resourcesPath, string $publicPath): array
    {
        $directories = ['js', 'css'];
        $publishedFiles = 0;

        // Verifica se os diretórios de recursos existem
        foreach ($directories as $directory) {
            $this->assertDirectoryExists($resourcesPath.'/'.$directory);
        }

        // Cria os diretórios de destino, se não existirem, e copia os arquivos
        foreach ($directories as $directory) {
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

        return ['files' => $publishedFiles];
    }
    
    private function assertDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            throw new RuntimeException('Diretório de ativos não encontrado: '.$path.'.');
        }
    }
}
