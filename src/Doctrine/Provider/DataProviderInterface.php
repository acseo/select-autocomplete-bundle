<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Doctrine\Provider;

use Doctrine\Persistence\ObjectManager;

interface DataProviderInterface
{
    public const DEFAULT_STRATEGY = 'starts_with';

    public function supports(ObjectManager $objectManager): bool;

    public function getCollection(ObjectManager $manager, string $class, string $property, string $value, string $strategy): array;
}
