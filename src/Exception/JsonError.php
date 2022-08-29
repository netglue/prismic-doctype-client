<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use JsonException;
use Prismic\DocumentType\Assert;
use RuntimeException;

use function sprintf;

final class JsonError extends RuntimeException implements Exception
{
    private ?string $jsonString = null;

    /**
     * @psalm-suppress InvalidScalarArgument
     */
    public static function onDecode(string $payload, JsonException $error): self
    {
        $instance = new self(
            sprintf('JSON Decode Failure: %s', $error->getMessage()),
            $error->getCode(),
            $error
        );
        $instance->jsonString = $payload;

        return $instance;
    }

    /**
     * @psalm-suppress InvalidScalarArgument
     */
    public static function onEncode(JsonException $error): self
    {
        return new self(
            sprintf('JSON Encode Failure: %s', $error->getMessage()),
            $error->getCode(),
            $error
        );
    }

    public function jsonString(): string
    {
        Assert::string($this->jsonString, 'This error does not have a json payload');

        return $this->jsonString;
    }
}
