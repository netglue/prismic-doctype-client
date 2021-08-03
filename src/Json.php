<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use JsonException;
use JsonSerializable;
use Prismic\DocumentType\Exception\JsonError;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class Json
{
    /**
     * @return array<array-key, mixed>
     *
     * @throws JsonError
     *
     * @psalm-pure
     */
    public static function decodeToArray(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            Assert::isArray($decoded);

            return $decoded;
        } catch (JsonException $e) {
            throw JsonError::onDecode($json, $e);
        }
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public static function encodeArray(array $parameters): string
    {
        try {
            return json_encode($parameters, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw JsonError::onEncode($e);
        }
    }

    public static function encodeObject(JsonSerializable $object): string
    {
        try {
            return json_encode($object, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw JsonError::onEncode($e);
        }
    }
}
