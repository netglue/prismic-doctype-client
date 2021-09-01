<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Exception\AssertionFailed;
use Prismic\DocumentType\Exception\InvalidDefinition;

class ResponseErrorTest extends TestCase
{
    public function testThatTheResponseMustBeProvidedToAccessOne(): void
    {
        $this->expectException(AssertionFailed::class);
        $error = new InvalidDefinition('Anything', 0);
        $error->response();
    }

    public function testThatTheRequestMustBeProvidedToAccessOne(): void
    {
        $this->expectException(AssertionFailed::class);
        $error = new InvalidDefinition('Anything', 0);
        $error->request();
    }
}
