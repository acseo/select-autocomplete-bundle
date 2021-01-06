<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Form\Transformer;

use Acseo\SelectAutocomplete\Traits\PropertyAccessorTrait;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class DoctrineObjectTransformer implements DataTransformerInterface
{
    use PropertyAccessorTrait;

    private $manager;

    private $class;

    private $isMultiple;

    public function __construct(ObjectManager $manager, string $class, bool $isMultiple)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->isMultiple = $isMultiple;
    }

    /**
     * Transform object[]|object to string[]|string.
     *
     * @return string|string[]
     */
    public function transform($value)
    {
        $identifier = $this->getRootIdentifier();

        if ($this->isMultiple) {
            $data = [];
            if (is_iterable($value)) {
                foreach ($value as $object) {
                    $data[] = (string) $this->getValue($object, $identifier);
                }
            }

            return $data;
        }

        return (string) (null === $value ? null : $this->getValue($value, $identifier));
    }

    /**
     * Transform string[]|string to object[]|object.
     *
     * @return object|object[]|null
     */
    public function reverseTransform($value)
    {
        if ($this->isMultiple) {
            $data = [];

            if (\is_array($value)) {
                $data = $this->findByIds($value);
            }

            return $data;
        }

        if ('' === $value || null === $value) {
            return null;
        }

        return $this->findByIds([$value])[0] ?? null;
    }

    /**
     * Find objects in database.
     *
     * @throws TransformationFailedException if an object is not found
     */
    private function findByIds(array $ids): array
    {
        $identifier = $this->getRootIdentifier();
        $results = $this->manager->getRepository($this->class)->findBy([$identifier => $ids]);

        if (\count($ids) !== \count($results)) {
            foreach ($results as $result) {
                $id = $this->getValue($result, $identifier);
                if (!\in_array($id, $ids, true)) {
                    throw new TransformationFailedException(sprintf('Object from class %s with id "%s" not found', $this->class, $id));
                }
            }
        }

        return $results;
    }

    /**
     * Extract root identifier of $this->class.
     */
    private function getRootIdentifier(): string
    {
        return $this->manager->getClassMetadata($this->class)->getIdentifierFieldNames()[0] ?? 'id';
    }
}
