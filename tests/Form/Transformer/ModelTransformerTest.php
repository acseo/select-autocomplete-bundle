<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\Form\Transformer;

use Acseo\SelectAutocomplete\DataProvider\DataProviderRegistry;
use Acseo\SelectAutocomplete\DataProvider\Doctrine\ORMDataProvider;
use Acseo\SelectAutocomplete\Form\Transformer\ModelTransformer;
use Acseo\SelectAutocomplete\Tests\App\Document\Bar;
use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ModelTransformerTest extends KernelTestCase
{
    /**
     * @var Foo
     */
    private $foo;

    /**
     * @var ORMDataProvider
     */
    private $fooProvider;

    /**
     * @var Bar
     */
    private $bar;

    /**
     * @var ODMDataProvider
     */
    private $barProvider;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();
        $dataProviders = $container->get(DataProviderRegistry::class);

        $this->fooProvider = $dataProviders->getProvider(Foo::class);
        $this->foo = $this->fooProvider->findByProperty(Foo::class, 'id', 1)[0];
        $this->barProvider = $dataProviders->getProvider(Bar::class);
        $this->bar = $this->barProvider->findByProperty(Bar::class, 'id', 1)[0];
    }

    public function testTransform(): void
    {
        // Single value context
        $transformer = new ModelTransformer($this->fooProvider, Foo::class, 'id', false);
        // Test if given object id transformed value are equal
        self::assertEquals((string) $this->foo->getId(), $transformer->transform($this->foo));

        // Test unvalid value
        self::assertEquals('', $transformer->transform(null));

        // ODM
        $transformer = new ModelTransformer($this->barProvider, Bar::class, 'id', false);
        // Test if given object id transformed value are equal
        self::assertEquals((string) $this->bar->getId(), $transformer->transform($this->bar));

        // Multiple values context
        $transformer = new ModelTransformer($this->fooProvider, Foo::class, 'id', true);
        // Test if given object id transformed value are equal
        $collection = $transformer->transform([$this->foo]);

        self::assertCount(1, $collection);
        self::assertEquals([(string) $this->foo->getId()], $collection);

        // ODM
        $transformer = new ModelTransformer($this->barProvider, Bar::class, 'id', true);
        // Test if given object id transformed value are equal
        self::assertEquals([(string) $this->bar->getId()], $transformer->transform([$this->bar]));
    }

    public function testReverseTransform(): void
    {
        // Single value context
        $transformer = new ModelTransformer($this->fooProvider, Foo::class, 'id', false);
        $object = $transformer->reverseTransform($this->foo->getId());

        // Test if given and retrieved objects are equal
        self::assertEquals(\get_class($object), \get_class($this->foo));
        self::assertEquals($object->getId(), $this->foo->getId());

        // Test unvalid values
        self::assertNull($transformer->reverseTransform(0));
        self::assertNull($transformer->reverseTransform(null));

        // ODM
        $transformer = new ModelTransformer($this->barProvider, Bar::class, 'id', false);
        $object = $transformer->reverseTransform($this->bar->getId());

        // Test if given and retrieved objects are equal
        self::assertEquals(\get_class($object), \get_class($this->bar));
        self::assertEquals($object->getId(), $this->bar->getId());

        // Multiple values contexts
        $transformer = new ModelTransformer($this->fooProvider, Foo::class, 'id', true);
        $collection = $transformer->reverseTransform([$this->foo->getId()]);

        // Test if retrieved collection length is equal to array given
        self::assertCount(1, $collection);

        // Test if given and retrieved objects are equal
        self::assertEquals(\get_class($collection[0]), \get_class($this->foo));
        self::assertEquals($collection[0]->getId(), $this->foo->getId());

        // Test unvalid values
        self::assertEmpty($transformer->reverseTransform([0]));

        // ODM
        $transformer = new ModelTransformer($this->barProvider, Bar::class, 'id', true);
        $collection = $transformer->reverseTransform([$this->bar->getId()]);

        // Test if retrieved collection length is equal to array given
        self::assertCount(1, $collection);

        // Test if given and retrieved objects are equal
        self::assertEquals(\get_class($collection[0]), \get_class($this->bar));
        self::assertEquals($collection[0]->getId(), $this->bar->getId());
    }
}
