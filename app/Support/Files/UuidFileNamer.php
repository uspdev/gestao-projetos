<?php

namespace App\Support\Files;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\FileNamer\FileNamer;

class UuidFileNamer extends FileNamer
{
   
/**
 * Gancho da Media Library para definir o nome-base do binário principal.
 *
 * Não representa o Nome original do arquivo do domínio, preservado em
 * media.original_name. O retorno compõe somente o nome físico privado.
 */
public function originalFileName(string $fileName): string
{
    return (string) Str::uuid();
}

    public function conversionFileName(string $fileName, Conversion $conversion): string
    {
        return pathinfo($fileName, PATHINFO_FILENAME).'-'.$conversion->getName();
    }

    public function responsiveFileName(string $fileName): string
    {
        return pathinfo($fileName, PATHINFO_FILENAME);
    }
}
