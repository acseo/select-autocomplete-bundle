<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Doctrine;

use Acseo\SelectAutocomplete\Doctrine\DataProvider\DataProviderInterface;

final class DataProviderRegistry
{
    private const DEFAULT_STRATEGY = 'contains';

    private $managerRegistry;

    /**
     * @var DataProviderInterface[]
     */
    private $providers = [];

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Data providers are loaded automatically by dependency injection with tagged services.
     */
    public function addProvider(DataProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Invoke all data providers and return result of first which supports object manager.
     */
    public function fetch(string $class, string $property, string $terms, string $strategy = self::DEFAULT_STRATEGY): array
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        foreach ($this->providers as $provider) {
            if ($provider->supports($manager)) {
                return $provider->fetch($manager, $class, $property, $terms, $strategy);
            }
        }

        throw new \RuntimeException(sprintf('No provider found for class "%s"', $class));
    }
}
