<?php

namespace App\Contracts;

use Illuminate\Support\Collection;
// Contrato para modelos comentaveis que expõem quem deve receber notificacoes de comentarios.
// Evita regras espalhadas no controller e garante um metodo unico para resolver destinatarios.

// Cada modelo tem sua propria logica para determinar os destinatarios,
// Project devolve os usuarios ligados ao projeto.
// Task devolve os usuarios ligados a tarefa.
// Meeting junta usuarios de todos os projetos da reuniao e remove duplicados.
interface HasCommentRecipients
{
    public function commentRecipients(): Collection;
}
