<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\DependencyInjection;

use Acseo\SelectAutocomplete\DependencyInjection\SelectAutocompleteExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class SelectAutocompleteExtensionTest extends TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder(new ParameterBag());
        $extension = new SelectAutocompleteExtension();

        try {
            $extension->load([], $container);
            self::assertTrue(true);
        } catch (\Exception $e) {
            self::assertFalse(true);
        }
    }
}
