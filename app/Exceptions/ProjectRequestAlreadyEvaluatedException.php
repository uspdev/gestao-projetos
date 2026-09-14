<?php

namespace App\Exceptions;

use DomainException;

class ProjectRequestAlreadyEvaluatedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Esta Solicitação já foi avaliada.');
    }
}
