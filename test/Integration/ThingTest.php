<?php

declare(strict_types=1);

namespace Gsteel\Package\Test\Integration;

use Gsteel\Package\Thing;
use PHPUnit\Framework\TestCase;

use function sprintf;

class ThingTest extends TestCase
{
    public function testAThingIntegratesWithAnotherThing(): void
    {
        $thingOne = Thing::fromString('Thing 1');
        $thingTwo = Thing::fromString('Thing 2');

        self::assertEquals(
            'Thing 1 and Thing 2',
            sprintf('%s and %s', (string) $thingOne, (string) $thingTwo)
        );
    }
}
