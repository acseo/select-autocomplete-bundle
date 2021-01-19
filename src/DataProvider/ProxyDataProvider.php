<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider;

final class ProxyDataProvider implements DataProviderInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var DataProviderInterface|null
     */
    private $provider;

    public function __construct(array $options = [], DataProviderInterface $provider = null)
    {
        $this->options = $options;
        $this->provider = $provider;
    }

    public function supports(string $class): bool
    {
        return true;
    }

    public function findByIds(string $class, string $property, array $ids): array
    {
        if (\is_callable($this->options['find_by_ids'] ?? 0)) {
            return $this->options['find_by_ids']($ids, $this->provider);
        }

        if (null !== $this->provider) {
            return $this->provider->findByIds($class, $property, $ids);
        }

        throw new \LogicException(sprintf('You must define "find_by_ids" option if no provider supports the model class "%s".', $class));
    }

    public function findByTerms(string $class, array $properties, string $terms, string $strategy): array
    {
        if (\is_callable($this->options['find_by_terms'] ?? 0)) {
            return $this->options['find_by_terms']($terms, $this->provider);
        }

        if (null !== $this->provider) {
            return $this->provider->findByTerms($class, $properties, $terms, $strategy);
        }

        throw new \LogicException(sprintf('You must define "find_by_terms" option if no provider supports the model class "%s".', $class));
    }
}
