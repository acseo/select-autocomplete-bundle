<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Traits;

use Symfony\Component\OptionsResolver\OptionsResolver;

trait FormTypeHelperTrait
{
    /**
     * Generate the most uniq hash possible of a field with its options.
     */
    protected static function generateUniqId(OptionsResolver $options, string $ignore = null): string
    {
        $data = [];

        foreach ($options->getDefinedOptions() as $name) {
            if ($ignore !== $name && isset($options[$name])) {
                $data[$name] = static::hash($options[$name]);
            }
        }

        return static::hash($data);
    }

    /**
     * Hash PHP value.
     */
    protected static function hash($value, int $depth = 1): string
    {
        if ($value instanceof \Closure) {
            $rf = new \ReflectionFunction($value);
            $value = $rf->getFileName().$rf->getEndLine();
        } elseif (is_iterable($value) || \is_object($value)) {
            $data = [];

            if (\is_object($value)) {
                $value = (array) $value;
            }

            if ($depth <= 2) {
                foreach ($value as $i => $element) {
                    $data[$i] = static::hash($element, $depth + 1);
                }
            }

            $value = serialize($data);
        }

        return md5((string) $value);
    }
}
