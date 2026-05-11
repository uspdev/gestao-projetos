<?php

namespace App\Enums\Project;

enum ProjectPhase: string
{
    case PLANNING = 'PLANNING';      
    case DEVELOPMENT = 'DEVELOPMENT'; 
    case PRODUCTION = 'PRODUCTION';   
    case RETIRED = 'RETIRED';         

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Planejamento',
            self::DEVELOPMENT => 'Desenvolvimento',
            self::PRODUCTION => 'Produção',
            self::RETIRED => 'Aposentado',
        };
    }
}