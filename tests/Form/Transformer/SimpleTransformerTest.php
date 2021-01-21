<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Tests\Form\Transformer;

use Acseo\SelectAutocomplete\Form\Transformer\SimpleTransformer;
use PHPUnit\Framework\TestCase;

final class SimpleTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new SimpleTransformer();
        self::assertEquals(1, $transformer->transform(1));
        self::assertEquals('1', $transformer->transform('1'));

        $transformer = new SimpleTransformer(true);
        self::assertEquals([1], $transformer->transform([1]));
        self::assertEquals([2], $transformer->transform(2));
    }

    public function testReverseTransform()
    {
        $transformer = new SimpleTransformer();
        self::assertEquals(1, $transformer->reverseTransform(1));
        self::assertEquals('1', $transformer->reverseTransform('1'));

        $transformer = new SimpleTransformer(true);
        self::assertEquals([1], $transformer->reverseTransform([1]));
        self::assertEquals([2], $transformer->reverseTransform(2));
    }
}
