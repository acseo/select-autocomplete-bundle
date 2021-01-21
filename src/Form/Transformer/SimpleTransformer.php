<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

final class SimpleTransformer implements DataTransformerInterface
{
    private $multiple;

    public function __construct(bool $multiple = false)
    {
        $this->multiple = $multiple;
    }

    public function transform($value)
    {
        if ($this->multiple) {
            if (is_iterable($value)) {
                return $value;
            }

            return null !== $value && '' !== $value ? [$value] : [];
        }

        return $value;
    }

    public function reverseTransform($value)
    {
        return $this->transform($value);
    }
}
