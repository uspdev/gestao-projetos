<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::creating(function ($model) {
            $model->created_by = self::getCurrentUserId();
            $model->updated_by = self::getCurrentUserId();
        });

        static::updating(function ($model) {
            $model->updated_by = self::getCurrentUserId();
        });

        static::deleting(function ($model) {
            $model->deleted_by = self::getCurrentUserId();
            $model->saveQuietly(); 
        });
    }

    protected static function getCurrentUserId()
    {
        // Se senha-unica-socialite se integra com o Auth Guard do Laravel:
        if (Auth::check()) {
            return Auth::id();
        }

        // Se a biblioteca usa um header específico ou outro método de injeção na Request:
        // return request()->header('X-User-ID') ?? null;
        
        return null; // Retorna null para ações feitas pelo sistema (ex: CRON jobs)
    }
}