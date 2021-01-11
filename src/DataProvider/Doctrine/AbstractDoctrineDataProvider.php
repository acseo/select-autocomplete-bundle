<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider\Doctrine;

use Acseo\SelectAutocomplete\DataProvider\DataProviderInterface;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

abstract class AbstractDoctrineDataProvider implements DataProviderInterface
{
    /**
     * Expected registry alias.
     */
    protected const REGISTRY = '';

    /**
     * Default limit of search queries.
     */
    protected const SEARCH_LIMIT_RESULTS = 20;

    /**
     * @var AbstractManagerRegistry|null
     */
    protected $registry;

    public function supports(string $class): bool
    {
        return null !== $this->registry && null !== $this->registry->getManagerForClass($class);
    }

    public function findByProperty(string $class, string $property, $value): array
    {
        return $this->getRepository($class)->findBy([$property => $value]);
    }

    public function getRepository(string $class): ObjectRepository
    {
        return $this->getManager($class)->getRepository($class);
    }

    public function setRegistry(AbstractManagerRegistry $registry): void
    {
        $this->registry = $registry;
    }

    protected function getRegistry(): AbstractManagerRegistry
    {
        if (null === $this->registry) {
            throw new \RuntimeException(sprintf('The doctrine registry "%s" expected by provider "%s" was not found', static::REGISTRY, static::class));
        }

        return $this->registry;
    }

    protected function getManager(string $class): ObjectManager
    {
        $manager = $this->getRegistry()->getManagerForClass($class);

        if (null === $manager) {
            throw new \RuntimeException(sprintf('Object manager not found for class "%s"', $class));
        }

        return $manager;
    }
}
