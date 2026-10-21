<?php

namespace App\Services;

use Uspdev\Replicado\Pessoa;

class UspPersonSearch
{
    public function search(string $term, array $excludedCodpes = []): array
    {
        $term = trim($term);
        if ($term === '' || !function_exists('hasReplicado') || !hasReplicado()) {
            return [];
        }

        $results = [];

        if (is_numeric($term) && strlen($term) >= 4) {
            try {
                $pessoa = Pessoa::dump((int) $term);

                if ($pessoa && !in_array((string) $pessoa['codpes'], $excludedCodpes, true)) {
                    $results[] = [
                        'id' => $pessoa['codpes'],
                        'text' => $pessoa['codpes'] . ' ' . $pessoa['nompesttd'],
                    ];
                }
            } catch (\Exception $e) {
            }

            if (!empty($results)) {
                return $results;
            }
        }

        try {
            $pessoas = Pessoa::procurarPorNome($term) ?? [];
            $pessoas = collect($pessoas)
                ->unique('codpes')
                ->sortBy('nompesttd')
                ->take(50);

            foreach ($pessoas as $pessoa) {
                if (in_array((string) $pessoa['codpes'], $excludedCodpes, true)) {
                    continue;
                }

                $results[] = [
                    'id' => $pessoa['codpes'],
                    'text' => $pessoa['codpes'] . ' ' . $pessoa['nompesttd'],
                ];
            }
        } catch (\Exception $e) {
        }

        return $results;
    }
}
