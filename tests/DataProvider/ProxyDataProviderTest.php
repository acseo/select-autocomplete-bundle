<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\DataProvider;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ORMDataProvider;
use Acseo\SelectAutocomplete\DataProvider\ProxyDataProvider;
use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProxyDataProviderTest extends KernelTestCase
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

    public function testSupports()
    {
        $provider = new ProxyDataProvider([], $this->providerRegistry->getProvider(Foo::class));
        self::assertTrue($provider->supports(Foo::class));
    }

    public function testFindByIds()
    {
        $provider = new ProxyDataProvider([
            'find_by_ids' => function ($ids, $provider) {
                self::assertInstanceOf(ORMDataProvider::class, $provider);
                self::assertIsArray($ids);

                return [];
            },
        ], $this->providerRegistry->getProvider(Foo::class));

        self::assertIsArray($provider->findByIds(Foo::class, 'id', []));

        $provider = new ProxyDataProvider([], $this->providerRegistry->getProvider(Foo::class));
        self::assertIsArray($provider->findByIds(Foo::class, 'id', []));

        $provider = new ProxyDataProvider([], null);
        self::expectException(\LogicException::class);
        $provider->findByIds(Foo::class, 'id', []);
    }

    public function testFindByTerms()
    {
        $provider = new ProxyDataProvider([
            'find_by_terms' => function ($terms, $provider) {
                self::assertInstanceOf(ORMDataProvider::class, $provider);
                self::assertIsString($terms);

                return [];
            },
        ], $this->providerRegistry->getProvider(Foo::class));

        self::assertIsArray($provider->findByTerms(Foo::class, ['id', 'child.name', 'name'], '', 'test'));

        $provider = new ProxyDataProvider([], $this->providerRegistry->getProvider(Foo::class));
        self::assertIsArray($provider->findByTerms(Foo::class, ['id', 'child.name', 'name'], '', 'equals'));

        $provider = new ProxyDataProvider([], null);
        self::expectException(\LogicException::class);
        self::assertIsArray($provider->findByTerms(Foo::class, ['id', 'child.name', 'name'], '', 'equals'));
    }
}
