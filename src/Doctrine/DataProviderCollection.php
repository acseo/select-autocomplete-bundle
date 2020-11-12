<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Doctrine;

use Acseo\SelectAutocomplete\Doctrine\Provider\DataProviderInterface;

final class DataProviderCollection
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var DataProviderInterface[]
     */
    private $providers = [];

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function addProvider(DataProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function getCollection(string $class, string $property, $value, string $strategy = DataProviderInterface::DEFAULT_STRATEGY): array
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        foreach ($this->providers as $provider) {
            if ($provider->supports($manager)) {
                return $provider->getCollection($manager, $class, $property, $value, $strategy);
            }
        }

        throw new \RuntimeException(sprintf('No providers found for class "%s"', $class));
    }
}
