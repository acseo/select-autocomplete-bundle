<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider;

final class DataProviderRegistry
{
    /**
     * @var DataProviderInterface[]
     */
    private $providers = [];

    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            if (!$provider instanceof DataProviderInterface) {
                throw new \RuntimeException(sprintf('Provider must be instance of "%s"', DataProviderInterface::class));
            }

            $this->providers[] = $provider;
        }
    }

    /**
     * Get provider for a specific resource class.
     */
    public function getProvider(string $resourceClass): DataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($resourceClass)) {
                return $provider;
            }
        }

        throw new \RuntimeException(sprintf('No provider found for resource class "%s"', $resourceClass));
    }

    /**
     * Get specific provider by service class name.
     */
    public function getProviderByClassName(string $class): DataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if (\get_class($provider) === $class) {
                return $provider;
            }
        }

        throw new \RuntimeException(sprintf('No provider found with class "%s"', $class));
    }
}
