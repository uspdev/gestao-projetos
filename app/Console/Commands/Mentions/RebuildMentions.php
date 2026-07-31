<?php

namespace App\Console\Commands\Mentions;

use App\Services\Mentions\MentionManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Command;

class RebuildMentions extends Command
{
    protected $signature = 'mentions:rebuild';

    protected $description = 'Reconstrói o índice derivado de Menções a partir dos campos Markdown';

    public function handle(MentionManager $mentionManager): int
    {
        if (! Schema::hasTable('mentions')) {
            $this->error('A tabela mentions não existe. Execute as migrações antes da reconstrução.');

            return self::FAILURE;
        }

        $result = $mentionManager->rebuild();

        foreach ($result['errors'] as $error) {
            $this->warn($error['source'] . ': ' . $error['message']);
        }

        $this->info(sprintf(
            'Reconstrução concluída: %d fontes, %d relações e %d erros.',
            $result['sources'],
            $result['mentions'],
            count($result['errors'])
        ));

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
