<?php

namespace App\Contracts;

interface Discussable
{
    public function parentProjectId(): ?int;
}
