<?php

// Este arquivo existe apenas como ponte de compatibilidade.
// O pacote spatie/laravel-activitylog sempre busca suas opcoes em config('activitylog'). Como centralizamos todas as configs em
// config/projetos.php, este stub redireciona para la e evita que o pacote caia nos defaults ou falhe por falta de configuracao.
// Nao remover sem substituir por outra ponte equivalente.

$projetosConfig = require __DIR__ . '/projetos.php';
$activityConfig = $projetosConfig['activitylog'] ?? null;

if (is_null($activityConfig)) {
    throw new \RuntimeException('Chave [activitylog] ausente em config/projetos.php');
}

return $activityConfig;
