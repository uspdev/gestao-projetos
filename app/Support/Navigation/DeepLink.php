<?php

namespace App\Support\Navigation;

use App\Models\Comment;
use App\Models\Link;
use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Centraliza os fragmentos usados para localizar entidades nas telas.
 *
 * IDs numéricos são usados para entidades do sistema. Arquivos e Links usam
 * seus UUIDs públicos, sem expor a chave sequencial interna.
 */
final class DeepLink
{
    /**
     * Gera a URL de uma rota e aponta para a entidade correspondente na tela.
     *
     * Quando o destino não é informado, usa a última entidade Eloquent presente
     * nos parâmetros da rota. Isso cobre chamadas como tasks.show e reuniões,
     * sem esconder os parâmetros necessários para gerar a rota.
     */
    public static function route(
        BackedEnum|string $name,
        mixed $parameters = [],
        ?Model $target = null,
        bool $absolute = true,
    ): string {
        $target ??= self::lastModelParameter($parameters);

        if (! $target) {
            throw new InvalidArgumentException(
                'Informe a entidade que será o destino do link de navegação.',
            );
        }

        return self::url(route($name, $parameters, $absolute), $target);
    }

    public static function fragment(Model $model): string
    {
        // Faz um match para determinar o tipo de entidade e o identificador a ser usado no fragmento.
        // Serve para centralizar a lógica de como cada entidade é representada no fragmento de navegação.
        [$type, $identity] = match (true) {
            $model instanceof Project => ['project', $model->getKey()],
            $model instanceof Task => ['task', $model->getKey()],
            $model instanceof Meeting => ['meeting', $model->getKey()],
            $model instanceof MeetingItem => ['meeting-item', $model->getKey()],
            $model instanceof Comment => ['comment', $model->getKey()],
            $model instanceof User => ['user', $model->getKey()],
            $model instanceof Media => ['file', $model->uuid],
            $model instanceof Link => ['link', $model->uuid],
            default => throw new InvalidArgumentException(
                'A entidade '.get_class($model).' não possui fragmento de navegação registrado.',
            ),
        };

        return $type.'-'.$identity;
    }

    public static function url(string $url, Model $model): string
    {
        return self::withFragment($url, self::fragment($model));
    }

    public static function withFragment(string $url, string $fragment): string
    {
        return str($url)->before('#').'#'.ltrim($fragment, '#');
    }

    private static function lastModelParameter(mixed $parameters): ?Model
    {
        if ($parameters instanceof Model) {
            return $parameters;
        }

        if (! is_array($parameters)) {
            return null;
        }

        foreach (array_reverse($parameters) as $parameter) {
            if ($parameter instanceof Model) {
                return $parameter;
            }
        }

        return null;
    }
}
