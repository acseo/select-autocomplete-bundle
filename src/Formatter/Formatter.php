<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Formatter;

use Acseo\SelectAutocomplete\Doctrine\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\SerializerInterface;

final class Formatter
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(SerializerInterface $serializer, ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->serializer = $serializer;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function format(string $class, iterable $data, string $display): array
    {
        $identifier = $this->registry
            ->getManagerForClass($class)
            ->getClassMetadata($class)
            ->getIdentifierFieldNames()[0] ?? 'id'
        ;

        $results = [];

        foreach ($data as $object) {
            $results[] = [
                'label' => $this->propertyAccessor->getValue($object, $display),
                'value' => $this->propertyAccessor->getValue($object, $identifier),
            ];
        }

        return $results;
    }
}
