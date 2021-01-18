<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\DataProvider\Doctrine;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ODMDataProvider;
use Acseo\SelectAutocomplete\Tests\App\Document\Bar;
use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ODMDataProviderTest extends KernelTestCase
{
    /**
     * @var ODMDataProvider
     */
    private $provider;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $this->provider = $container->get(DataProviderRegistry::class)->getProviderByClassName(ODMDataProvider::class);
    }

    public function testSupports()
    {
        self::assertTrue($this->provider->supports(Bar::class));
        self::assertFalse($this->provider->supports(Foo::class));
    }

    public function testFindByIds()
    {
        $data = $this->provider->findByIds(Bar::class, 'id', [1]);
        self::assertCount(1, $data);
    }

    public function testGetRepository()
    {
        self::assertInstanceOf(DocumentRepository::class, $this->provider->getRepository(Bar::class));
    }

    public function testGetRegistry()
    {
        $r = new \ReflectionMethod(ODMDataProvider::class, 'getRegistry');
        $r->setAccessible(true);

        $registry = $r->invoke($this->provider);
        self::assertInstanceOf(ManagerRegistry::class, $registry);

        $provider = new ODMDataProvider();
        self::expectException(\BadMethodCallException::class);
        $r->invoke($provider);
    }

    public function testGetManager()
    {
        $r = new \ReflectionMethod(ODMDataProvider::class, 'getManager');
        $r->setAccessible(true);

        $manager = $r->invoke($this->provider, Bar::class);
        self::assertInstanceOf(DocumentManager::class, $manager);

        self::expectException(\BadMethodCallException::class);
        $r->invoke($this->provider, self::class);
    }

    public function testFindByTerms()
    {
        $results = $this->provider->findByTerms(Bar::class, ['name'], 'Bar 1', 'equals');
        self::assertCount(1, $results);

        $results = $this->provider->findByTerms(Bar::class, ['name'], 'Bar 1', 'equals');
        self::assertCount(1, $results);

        $results = $this->provider->findByTerms(Bar::class, ['name'], 'Bar', 'starts_with');
        self::assertCount(20, $results);

        $results = $this->provider->findByTerms(Bar::class, ['child.name'], '13', 'ends_with');
        self::assertCount(1, $results);

        $results = $this->provider->findByTerms(Bar::class, ['name'], 'ar', 'contains');
        self::assertCount(20, $results);

        $results = $this->provider->findByTerms(Bar::class, ['items.name'], '13', 'ends_with');
        self::assertCount(1, $results);

        $results = $this->provider->findByTerms(Bar::class, ['embedded.name'], '13', 'ends_with');
        self::assertCount(1, $results);

        self::expectException(\InvalidArgumentException::class);
        $this->provider->findByTerms(Bar::class, ['name'], '1', 'undefined');
    }

    public function testAddLookup()
    {
        $r = new \ReflectionMethod(ODMDataProvider::class, 'addLookup');
        $r->setAccessible(true);

        $qb = $this->provider->getRepository(Bar::class)
            ->createAggregationBuilder()
            ->hydrate(Bar::class)
        ;

        self::assertEquals('name', $r->invoke($this->provider, $qb, Bar::class, 'name'));

        try {
            $qb->getPipeline();
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertInstanceOf(\OutOfRangeException::class, $e);
        }

        try {
            $r->invoke($this->provider, $qb, Bar::class, 'undefined.name');
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertTrue(true);
        }

        self::assertEquals('', $r->invoke($this->provider, $qb, Bar::class, ''));
    }

    public function testIsLookupExist()
    {
        $r = new \ReflectionMethod(ODMDataProvider::class, 'isLookupExist');
        $r->setAccessible(true);

        $qb = $this->provider->getRepository(Bar::class)
            ->createAggregationBuilder()
            ->hydrate(Bar::class)
        ;

        $qb->lookup(Bar::class)
            ->localField('child')
            ->foreignField('id')
            ->alias('child_lkp')
        ;

        self::assertTrue($r->invoke($this->provider, $qb, 'child_lkp'));
        self::assertFalse($r->invoke($this->provider, $qb, 'children_lkp'));
    }
}
