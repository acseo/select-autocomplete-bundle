<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\DataProvider;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ODMDataProvider;
use Acseo\SelectAutocomplete\Tests\App\Document\Bar;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DataProviderRegistryTest extends KernelTestCase
{
    /**
     * @var DataProviderRegistry
     */
    private $providerRegistry;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $this->providerRegistry = $container->get(DataProviderRegistry::class);
    }

    public function testConstruct()
    {
        $providerRegistry = new DataProviderRegistry([
            $this->providerRegistry->getProviderByClassName(ODMDataProvider::class),
        ]);

        self::assertNotNull($providerRegistry->getProviderByClassName(ODMDataProvider::class));

        self::expectException(\UnexpectedValueException::class);
        new DataProviderRegistry([$this]);
    }

    public function testGetProvider()
    {
        self::assertInstanceOf(ODMDataProvider::class, $this->providerRegistry->getProvider(Bar::class));
        self::assertNull($this->providerRegistry->getProvider(self::class));
    }

    public function testGetProviderByClassName()
    {
        self::assertInstanceOf(ODMDataProvider::class, $this->providerRegistry->getProviderByClassName(ODMDataProvider::class));
        self::assertNull($this->providerRegistry->getProviderByClassName(self::class));
    }
}
