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

    public function testFindByProperty()
    {
        $data = $this->provider->findByProperty(Bar::class, 'id', 1);
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
        self::expectException(\RuntimeException::class);
        $r->invoke($provider);
    }

    public function testGetManager()
    {
        $r = new \ReflectionMethod(ODMDataProvider::class, 'getManager');
        $r->setAccessible(true);

        $manager = $r->invoke($this->provider, Bar::class);
        self::assertInstanceOf(DocumentManager::class, $manager);

        self::expectException(\RuntimeException::class);
        $r->invoke($this->provider, self::class);
    }

    public function testFindByTerms()
    {
        $results = $this->provider->findByTerms(Bar::class, 'name', 'Bar 1', 'equals');
        self::assertCount(1, $results);

        $results = $this->provider->findByTerms(Bar::class, 'name', 'Bar', 'starts_with');
        self::assertCount(20, $results);

        $results = $this->provider->findByTerms(Bar::class, 'name', '13', 'ends_with');
        self::assertCount(1, $results);

        $results = $this->provider->findByTerms(Bar::class, 'name', 'ar', 'contains');
        self::assertCount(20, $results);

        self::expectException(\RuntimeException::class);
        $results = $this->provider->findByTerms(Bar::class, 'name', '1', 'undefined');
    }
}
