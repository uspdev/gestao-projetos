<?php

namespace App\Contracts;

use App\Models\User;

interface Watchable
{
    // Retorna o rótulo do recurso observável, que será exibido nas notificações e na interface do usuário.
    public function watchLabel(): string;

    // Retorna a URL do recurso observável, que será usada para redirecionar o usuário quando ele clicar na notificação.
    public function watchUrl(): ?string;

    // Determina se um recurso observável pode ser visualizado por um usuário específico.
    public function watchCanBeViewedBy(User $user): bool;

    // Determina se o estado atual do recurso permite exibi-lo e enviar notificações de acompanhamento.
    public function watchCanReceiveNotifications(): bool;
}
