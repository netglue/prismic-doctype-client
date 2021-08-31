<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit\Exception;

use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Exception\UnexpectedStatusCode;
use Psr\Http\Message\ServerRequestInterface;

class UnexpectedStatusCodeTest extends TestCase
{
    public function testThatTheExpectedCodeIsReported(): void
    {
        $error = UnexpectedStatusCode::withExpectedCode(
            123,
            $this->createMock(ServerRequestInterface::class),
            new TextResponse('Foo', 234)
        );

        self::assertEquals(
            'Expected the HTTP response code 123 but received 234',
            $error->getMessage()
        );
    }
}
