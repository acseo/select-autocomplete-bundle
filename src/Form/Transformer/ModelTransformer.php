<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Transformer;

use Acseo\SelectAutocomplete\DataProvider\DataProviderInterface;
use Acseo\SelectAutocomplete\Traits\PropertyAccessorTrait;
use Symfony\Component\Form\DataTransformerInterface;

final class ModelTransformer implements DataTransformerInterface
{
    use PropertyAccessorTrait;

    /**
     * @var DataProviderInterface
     */
    private $provider;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var bool
     */
    private $multiple;

    public function __construct(DataProviderInterface $provider, string $class, string $identifier, bool $multiple)
    {
        $this->class = $class;
        $this->multiple = $multiple;
        $this->provider = $provider;
        $this->identifier = $identifier;
    }

    /**
     * Transform object[]|object to string[]|string.
     *
     * @return string|string[]
     */
    public function transform($value)
    {
        if ($this->multiple) {
            $data = [];

            if (is_iterable($value)) {
                foreach ($value as $object) {
                    $data[] = (string) $this->getValue($object, $this->identifier);
                }
            }

            return $data;
        }

        if (null === $value) {
            return '';
        }

        return (string) $this->getValue($value, $this->identifier);
    }

    /**
     * Transform string[]|string to object[]|object.
     *
     * @return object|object[]|null
     */
    public function reverseTransform($value)
    {
        if ($this->multiple) {
            return \is_array($value) ? $this->findByIds($value) : [];
        }

        if ('' === $value || null === $value) {
            return null;
        }

        return $this->findByIds([$value])[0] ?? null;
    }

    /**
     * Invoke provider to retrieve model from database.
     */
    private function findByIds(array $ids): array
    {
        return $this->provider->findByIds($this->class, $this->identifier, $ids);
    }
}
