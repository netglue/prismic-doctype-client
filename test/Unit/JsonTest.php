<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Exception\AssertionFailed;
use Prismic\DocumentType\Exception\JsonError;
use Prismic\DocumentType\Json;
use Throwable;

use function json_encode;

use const JSON_ERROR_DEPTH;
use const JSON_THROW_ON_ERROR;
use const STDOUT;

class JsonTest extends TestCase
{
    public function testAnArrayCanBeEncoded(): void
    {
        $input = ['foo' => 'bar'];
        $expect = json_encode($input, JSON_THROW_ON_ERROR);
        $result = Json::encodeArray($input);
        self::assertEquals($expect, $result);
    }

    public function testAnUnEncodeAbleArrayWillCauseAnException(): void
    {
        $this->expectException(JsonError::class);
        Json::encodeArray(['cant-touch-this' => STDOUT]);
    }

    /** @return array<string, array{0: string, 1: class-string<Throwable>}> */
    public function decodeArrayInvalidData(): array
    {
        return [
            'Trailing Comma' => ['{"foo":"bar",}', JsonError::class],
            'Unquoted Word' => ['foo', JsonError::class],
            'Quoted Word' => ['"foo"', AssertionFailed::class],
            'Boolean' => ['true', AssertionFailed::class],
            'Null' => ['null', AssertionFailed::class],
        ];
    }

    /**
     * @param class-string<Throwable> $expectedException
     *
     * @dataProvider decodeArrayInvalidData
     */
    public function testArrayDecodingFailures(string $json, string $expectedException): void
    {
        $this->expectException($expectedException);
        Json::decodeToArray($json);
    }

    public function testDecodeToArrayCanDecode(): void
    {
        $expect = ['foo' => 'bar'];
        self::assertEquals($expect, Json::decodeToArray('{"foo":"bar"}'));
    }

    public function testDecodeToArrayCanDecodeMultipleItems(): void
    {
        $expect = ['foo' => 'bar', 'num' => 1];
        self::assertEquals($expect, Json::decodeToArray('{"foo":"bar", "num": 1}'));
    }

    public function testMaxDepthExceeded(): void
    {
        $inputArray = ['foo' => 'foo'];

        for ($i = 0; $i < 550; $i++) {
            $inputArray['foo'] = $inputArray;
        }

        $json = json_encode($inputArray, JSON_THROW_ON_ERROR, 551);

        $this->expectException(JsonError::class);
        $this->expectExceptionCode(JSON_ERROR_DEPTH);

        Json::decodeToArray($json);
    }

    public function testMaxDepthNotExceeded(): void
    {
        $inputArray = ['foo' => ['foo' => ['foo' => ['foo']]]];
        $json = json_encode($inputArray, JSON_THROW_ON_ERROR);

        self::assertEquals($inputArray, Json::decodeToArray($json));
    }
}
