<?php

namespace App\Morphs;

use Illuminate\Database\Eloquent\Model;

interface Duplicable
{
    /**
     * Cria uma nova entidade a partir desta, respeitando as opções informadas.
     */
    public function duplicate(array $options = []): Model;

    /**
     * Retorna o motivo que impede a duplicação, quando houver.
     */
    public function duplicationBlockReason(): ?string;
}
