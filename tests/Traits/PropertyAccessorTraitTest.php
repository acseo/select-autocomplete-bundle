<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\Traits;

use Acseo\SelectAutocomplete\Tests\App\Entity\Foo;
use Acseo\SelectAutocomplete\Traits\PropertyAccessorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class PropertyAccessorTraitTest extends TestCase
{
    use PropertyAccessorTrait;

    public function testGetPropertyAccessor()
    {
        self::assertInstanceOf(PropertyAccessor::class, self::getPropertyAccessor());
    }

    public function testGetValue()
    {
        $obj = (new Foo())->setName('test');

        self::assertEquals('test', $this->getValue($obj, 'name'));
        self::assertFalse($this->getValue($obj, 'undefined', false));

        $array = ['foo' => ['bar' => 'test']];

        self::assertEquals('test', $this->getValue($array, 'foo.bar'));
        self::assertFalse($this->getValue($array, 'undefined', false));
    }
}
