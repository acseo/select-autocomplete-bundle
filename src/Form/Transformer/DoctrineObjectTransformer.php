<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Transformer;

use Acseo\SelectAutocomplete\Doctrine\ManagerRegistry;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DoctrineObjectTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $class;

    /**
     * @var bool
     */
    private $isMultiple;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(ManagerRegistry $registry, string $class, bool $isMultiple)
    {
        $this->registry = $registry;
        $this->class = $class;
        $this->isMultiple = $isMultiple;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Transforms an object (object) to a string (id).
     *
     * @return string|string[]
     */
    public function transform($value)
    {
        $identifier = $this->registry
            ->getManagerForClass($this->class)
            ->getClassMetadata($this->class)
            ->getIdentifierFieldNames()[0] ?? 'id'
        ;

        if ($this->isMultiple) {
            $data = [];
            if (is_iterable($value)) {
                foreach ($value as $object) {
                    $data[] = (string) $this->propertyAccessor->getValue($object, $identifier);
                }
            }

            return $data;
        }

        return (string) (null === $value ? null : $this->propertyAccessor->getValue($value, $identifier));
    }

    /**
     * Transforms a string (id) to an object (object).
     *
     * @return object|object[]|null
     */
    public function reverseTransform($value)
    {
        if ($this->isMultiple) {
            $data = [];

            if (is_iterable($value)) {
                foreach ($value as $id) {
                    $data[] = $this->find((string) $id);
                }
            }

            return $data;
        }

        return '' === $value || null === $value ? null : $this->find((string) $value);
    }

    /**
     * Find object in DB.
     *
     * @throws TransformationFailedException if object is not found
     */
    private function find(string $id): object
    {
        $class = $this->class;
        $object = $this->registry->getManagerForClass($class)->getRepository($class)->find($id);

        if (null !== $object) {
            return $object;
        }

        throw new TransformationFailedException(sprintf('Object from class %s with id "%s" not found', $this->class, $id));
    }
}
