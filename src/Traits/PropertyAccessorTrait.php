<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Traits;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait PropertyAccessorTrait
{
    /**
     * @var PropertyAccessor|null
     */
    private static $propertyAccessor;

    protected static function getPropertyAccessor(): PropertyAccessor
    {
        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$propertyAccessor;
    }

    /**
     * @param object|array $objOrArray
     */
    protected function getValue($objOrArray, string $path, $default = null)
    {
        if (\is_array($objOrArray)) {
            $path = self::convertDotPathToArrayNotation($path);
        }

        try {
            return self::getPropertyAccessor()->getValue($objOrArray, $path) ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Transform path.to.prop to [path][to][prop] to accessing array.
     *
     * @see https://symfony.com/doc/current/components/property_access.html#reading-from-arrays
     */
    private static function convertDotPathToArrayNotation(string $path): string
    {
        return sprintf('[%s]', implode('][', explode('.', $path)));
    }
}
