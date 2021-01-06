<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Doctrine\DataProvider;

use Doctrine\Persistence\ObjectManager;

interface DataProviderInterface
{
    public function supports(ObjectManager $objectManager): bool;

    /**
     * Fetch object collection of specific class filtered by a value on specific property.
     * The strategy is used to define how the filter has to be apply.
     */
    public function fetch(ObjectManager $manager, string $class, string $property, string $value, string $strategy): array;
}
