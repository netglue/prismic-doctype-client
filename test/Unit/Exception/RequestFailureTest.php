<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Exception\AssertionFailed;
use Prismic\DocumentType\Exception\RequestFailure;

class RequestFailureTest extends TestCase
{
    public function testThatTheRequestMustBeProvidedToAccessOne(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('This error was not constructed with a request instance');
        $error = new RequestFailure('Anything', 0);
        $error->failedRequest();
    }
}
