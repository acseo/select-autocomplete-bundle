<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\App\Form\DataProvider;

use Acseo\SelectAutocomplete\DataProvider\DataProviderInterface;

class CustomProvider implements DataProviderInterface
{
    /**
     * Does provider supports the model class.
     */
    public function supports(string $class): bool
    {
        return false;
    }

    /**
     * Used to retrieve object with form view data (reverseTransform).
     */
    public function findByProperty(string $class, string $property, $value): array
    {
        return [];
    }

    /**
     * Find collection results of autocomplete action.
     */
    public function findByTerms(string $class, string $property, string $terms, string $strategy): array
    {
        return [];
    }
}
