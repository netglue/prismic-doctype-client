<?php

declare(strict_types=1);

namespace Gsteel\Package\Test\Unit;

use Gsteel\Package\Thing;
use PHPUnit\Framework\TestCase;

class ThingTest extends TestCase
{
    public function testAThingDoesAThang(): void
    {
        self::assertEquals('thang', (string) Thing::fromString('thang'));
    }
}
