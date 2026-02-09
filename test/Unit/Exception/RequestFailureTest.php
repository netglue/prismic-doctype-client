<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit\Exception;

use Laminas\Diactoros\Request;
use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Exception\AssertionFailed;
use Prismic\DocumentType\Exception\RequestFailure;
use Psr\Http\Client\ClientExceptionInterface;

final class RequestFailureTest extends TestCase
{
    public function testThatTheRequestMustBeProvidedToAccessOne(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('This error was not constructed with a request instance');
        $error = new RequestFailure('Anything', 0);
        $error->failedRequest();
    }

    public function testPsrErrorWillHaveTheExpectedCode(): void
    {
        $error = RequestFailure::withPsrError(
            new Request('/foo'),
            $this->createStub(ClientExceptionInterface::class),
        );

        self::assertEquals(0, $error->getCode());
    }

    public function testPsrErrorWillReferencePreviousException(): void
    {
        $exception = $this->createStub(ClientExceptionInterface::class);
        $error = RequestFailure::withPsrError(
            new Request('/foo'),
            $exception,
        );

        self::assertSame($exception, $error->getPrevious());
    }
}
