<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\DataProvider\Doctrine;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ORMDataProvider;
use Acseo\SelectAutocomplete\Tests\App\Document\Bar;
use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ORMDataProviderTest extends KernelTestCase
{
    /**
     * @var ORMDataProvider
     */
    private $provider;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $this->provider = $container->get(DataProviderRegistry::class)->getProviderByClassName(ORMDataProvider::class);
    }

    public function testSupports()
    {
        self::assertTrue($this->provider->supports(Foo::class));
        self::assertFalse($this->provider->supports(Bar::class));
    }

    public function testFindByIds()
    {
        $data = $this->provider->findByIds(Foo::class, 'id', [1]);
        self::assertCount(1, $data);
    }

    public function testGetRepository()
    {
        self::assertInstanceOf(EntityRepository::class, $this->provider->getRepository(Foo::class));
    }

    public function testGetRegistry()
    {
        $r = new \ReflectionMethod(ORMDataProvider::class, 'getRegistry');
        $r->setAccessible(true);

        $registry = $r->invoke($this->provider);
        self::assertInstanceOf(Registry::class, $registry);

        $provider = new ORMDataProvider();
        self::expectException(\BadMethodCallException::class);
        $r->invoke($provider);
    }

    public function testGetManager()
    {
        $r = new \ReflectionMethod(ORMDataProvider::class, 'getManager');
        $r->setAccessible(true);

        $manager = $r->invoke($this->provider, Foo::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        self::expectException(\BadMethodCallException::class);
        $r->invoke($this->provider, self::class);
    }

    public function testFindByTerms()
    {
        $results = $this->provider->findByTerms(Foo::class, ['name'], 'Foo 1', 'equals');
        self::assertCount(1, $results);

        $results = $this->provider->findByTerms(Foo::class, ['name'], 'Foo', 'starts_with');
        self::assertCount(20, $results);

        $results = $this->provider->findByTerms(Foo::class, ['name'], 'oo', 'contains');
        self::assertCount(20, $results);

        $results = $this->provider->findByTerms(Foo::class, ['children.name'], '13', 'ends_with');
        self::assertCount(1, $results);

        self::expectException(\InvalidArgumentException::class);
        $this->provider->findByTerms(Foo::class, ['name'], '1', 'undefined');
    }
}
