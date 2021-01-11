<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider;

interface DataProviderInterface
{
    /**
     * Does provider support resource class.
     */
    public function supports(string $class): bool;

    /**
     * Fetch object collection of specific class filtered by a value on specific property.
     * The strategy is used to define how the filter has to be applied.
     *
     * @return object[]|array[]
     */
    public function findByTerms(string $class, string $property, string $terms, string $strategy): array;

    /**
     * Find object by property value.
     *
     * @return object[]
     */
    public function findByProperty(string $class, string $property, $value): array;
}