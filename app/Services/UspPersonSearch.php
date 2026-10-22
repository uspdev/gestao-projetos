<?php

namespace App\Services;

use Uspdev\Replicado\Pessoa;

/**
 * Serviço responsável por buscar pessoas no Replicado da USP.
 * Permite pesquisar por número USP (codpes) ou nome,
 * ignorando pessoas previamente selecionadas.
 */
class UspPersonSearch
{
    /**
     * Busca pessoas pelo número USP ou nome.
     *
     * @param string $term Termo utilizado na pesquisa.
     * @param array $excludedCodpes Números USP que devem ser ignorados.
     * @return array Lista de pessoas no formato ['id' => codpes, 'text' => identificação].
     */
    public function search(string $term, array $excludedCodpes = []): array
    {
        $term = trim($term);

        // Interrompe a busca se o termo estiver vazio ou o Replicado indisponível.
        if ($term === '' || !function_exists('hasReplicado') || !hasReplicado()) {
            return [];
        }

        $results = [];

        // Busca por código ou nome, remove duplicatas, ordena e limita a 50 registros.
        try {
            $pessoas = Pessoa::procurarPorCodigoOuNome($term) ?? [];
            $pessoas = collect($pessoas)
                ->unique('codpes')
                ->sortBy('nompesttd')
                ->take(50);

            // Desconsidera os códigos excluídos e formata os resultados.
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
