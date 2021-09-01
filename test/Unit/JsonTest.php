<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit;

use JsonSerializable;
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

    /** @return array<string, mixed> */
    private function arrayWithDepth(int $depth): array
    {
        $inputArray = ['foo' => 'foo'];

        for ($i = 1; $i < $depth; $i++) {
            $inputArray['foo'] = $inputArray;
        }

        return $inputArray;
    }

    public function testMaxDepthExceeded(): void
    {
        $inputArray = $this->arrayWithDepth(512);

        $json = json_encode($inputArray, JSON_THROW_ON_ERROR, 513);

        $this->expectException(JsonError::class);
        $this->expectExceptionCode(JSON_ERROR_DEPTH);

        Json::decodeToArray($json);
    }

    public function testMaxDepthNotExceeded(): void
    {
        $inputArray = $this->arrayWithDepth(511);

        $json = json_encode($inputArray, JSON_THROW_ON_ERROR, 513);

        self::assertEquals($inputArray, Json::decodeToArray($json));
    }

    public function testAnExceptionIsThrownEncodingAnInvalidObject(): void
    {
        $mock = $this->createMock(JsonSerializable::class);
        $mock->expects(self::once())
            ->method('jsonSerialize')
            ->willReturn($this->arrayWithDepth(513));

        $this->expectException(JsonError::class);
        Json::encodeObject($mock);
    }
}
