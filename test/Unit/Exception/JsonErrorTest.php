<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit\Exception;

use JsonException;
use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Exception\AssertionFailed;
use Prismic\DocumentType\Exception\JsonError;

use const JSON_ERROR_DEPTH;

class JsonErrorTest extends TestCase
{
    public function testThatAskingForTheJsonStringIsExceptionalWhenItIsNotTheResultOfADecodeError(): void
    {
        $error = new JsonError();
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('This error does not have a json payload');
        $error->jsonString();
    }

    public function testEncodeErrorPreservesTheNativeCode(): void
    {
        $exception = new JsonException('Whatever', JSON_ERROR_DEPTH);
        $error = JsonError::onEncode($exception);
        $this->expectException(JsonError::class);
        $this->expectExceptionMessage('JSON Encode Failure: Whatever');
        $this->expectExceptionCode(JSON_ERROR_DEPTH);

        throw $error;
    }

    public function testThatADecodeErrorPreservesTheNativeCode(): void
    {
        $exception = new JsonException('Whatever', JSON_ERROR_DEPTH);
        $error = JsonError::onDecode('foo', $exception);
        $this->expectException(JsonError::class);
        $this->expectExceptionMessage('JSON Decode Failure: Whatever');
        $this->expectExceptionCode(JSON_ERROR_DEPTH);

        throw $error;
    }

    public function testThatADecodeErrorCanReturnTheGivenPayload(): void
    {
        $exception = new JsonException('Whatever', JSON_ERROR_DEPTH);
        $error = JsonError::onDecode('foo', $exception);
        self::assertEquals('foo', $error->jsonString());
    }
}
