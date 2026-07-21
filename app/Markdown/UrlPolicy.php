<?php

namespace App\Markdown;

class UrlPolicy
{
    public function allows(string $url): bool
    {
        if (! preg_match('/^[a-z][a-z0-9+.-]*:/i', $url, $matches)) {
            return true;
        }

        return in_array(strtolower(rtrim($matches[0], ':')), ['http', 'https'], true);
    }
}
