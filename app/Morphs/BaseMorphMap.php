<?php

namespace App\Morphs;

use Illuminate\Database\Eloquent\Model;

abstract class BaseMorphMap
{
    /**
     * Retorna o array de opções mapeadas.
     */
    abstract public static function options(): array;

    /**
     * Retorna a interface/contrato exigido. Se null, não exige contrato.
     */
    abstract protected static function contract(): ?string;

    public static function allowedValues(): array
    {
        $values = [];

        foreach (static::options() as $alias => $class) {
            $validatedClass = static::validateClass($class, static::contract());
            if (!$validatedClass) {
                continue;
            }

            $values[] = (string) $alias;
            $values[] = $validatedClass;
        }

        return array_values(array_unique($values));
    }

    public static function resolveClass(string $type): ?string
    {
        if ($type === '') {
            return null;
        }

        $options = static::options();

        if (isset($options[$type])) {
            return static::validateClass($options[$type], static::contract());
        }

        $typeWithoutSlash = ltrim($type, '\\');
        if (in_array($typeWithoutSlash, $options, true)) {
            return static::validateClass($typeWithoutSlash, static::contract());
        }

        return null;
    }

    public static function morphMap(): array
    {
        $map = [];

        foreach (static::options() as $alias => $class) {
            $validatedClass = static::validateClass($class, null);
            if ($validatedClass) {
                $map[$alias] = $validatedClass;
            }
        }

        return $map;
    }

    protected static function validateClass(mixed $class, ?string $contract): ?string
    {
        if (!is_string($class) || $class === '') {
            return null;
        }

        $class = ltrim($class, '\\');

        if (!class_exists($class)) {
            return null;
        }

        if (!is_subclass_of($class, Model::class)) {
            return null;
        }

        if ($contract && !is_subclass_of($class, $contract)) {
            return null;
        }

        return $class;
    }
}
