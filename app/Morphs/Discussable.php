<?php

namespace App\Morphs;

interface Discussable
{
    public function parentProjectId(): ?int;
}
