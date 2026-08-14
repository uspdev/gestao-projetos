<?php

namespace App\Console\Commands;

use App\Support\AssetPublisher;
use Illuminate\Console\Command;
use RuntimeException;

class PublishAssets extends Command
{
    protected $signature = 'projetos:assets-publish';

    protected $description = 'Publica os JavaScripts sem depender de npm';

    public function handle(AssetPublisher $publisher): int
    {
        try {
            $result = $publisher->publish(resource_path(), public_path());
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Ativos publicados: %d arquivos copiados para public/js.',
            $result['files'],
        ));

        return self::SUCCESS;
    }
}
