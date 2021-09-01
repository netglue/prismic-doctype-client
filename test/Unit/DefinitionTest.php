<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Definition;
use Prismic\DocumentType\Exception\AssertionFailed;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class DefinitionTest extends TestCase
{
    /** @var Definition */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->type = Definition::new(
            'custom',
            'Custom Type',
            true,
            true,
            '{"Foo":{}}'
        );
    }

    public function testExpectedJsonStructure(): void
    {
        $expect = [
            'id' => 'custom',
            'label' => 'Custom Type',
            'repeatable' => true,
            'status' => true,
            'json' => ['Foo' => []],
        ];
        self::assertEquals($expect, $this->type->jsonSerialize());
    }

    public function testThatTheIdHasTheExpectedValue(): void
    {
        self::assertEquals('custom', $this->type->id());
    }

    public function testThatAlteringThePayloadYieldsANewInstance(): void
    {
        $clone = $this->type->withAlteredPayload('{"Bar":"Baz"}');
        self::assertNotSame($this->type, $clone);
        self::assertNotEquals($this->type->json(), $clone->json());
    }

    public function testThatAlteringTheLabelYieldsANewInstance(): void
    {
        $clone = $this->type->withNewLabel('New Label');
        self::assertNotSame($this->type, $clone);
        self::assertNotEquals($this->type->label(), $clone->label());
    }

    public function testThatAnEmptyLabelIsExceptional(): void
    {
        $this->expectException(AssertionFailed::class);
        /** @psalm-suppress UnusedMethodCall, InvalidArgument */
        $this->type->withNewLabel('');
    }

    public function testThatChangingActivationStateYieldsANewInstance(): void
    {
        $clone = $this->type->withActivationStatus(false);
        self::assertNotSame($this->type, $clone);
        self::assertNotEquals($this->type->isActive(), $clone->isActive());
    }

    public function testThatWithRepeatableStateYieldsANewInstance(): void
    {
        $clone = $this->type->withRepeatable();
        self::assertNotSame($this->type, $clone);
        self::assertEquals($this->type->isRepeatable(), $clone->isRepeatable());
    }

    public function testThatWithoutRepeatableStateYieldsANewInstance(): void
    {
        $clone = $this->type->withoutRepeatable();
        self::assertNotSame($this->type, $clone);
        self::assertNotEquals($this->type->isRepeatable(), $clone->isRepeatable());
    }

    public function testEqualityIsTrueForTheSameInstance(): void
    {
        self::assertTrue($this->type->equals($this->type));
    }

    public function testEqualityIsTrueForDifferentInstancesWithTheSameValues(): void
    {
        $a = $this->type->withActivationStatus(false);
        $b = $this->type->withActivationStatus(false);
        self::assertNotSame($a, $b);
        self::assertTrue($a->equals($b));
    }

    public function testEqualityIsFalseForDifferentLabel(): void
    {
        $other = $this->type->withNewLabel('changed');
        self::assertFalse($this->type->equals($other));
    }

    public function testEqualityIsFalseForDifferentActivationStatus(): void
    {
        $other = $this->type->withActivationStatus(false);
        self::assertFalse($this->type->equals($other));
    }

    public function testEqualityIsFalseForDifferentRepeatableStatus(): void
    {
        $other = $this->type->withoutRepeatable();
        self::assertFalse($this->type->equals($other));
    }

    public function testEqualityIsFalseForJsonWithAndWithoutWhitespace(): void
    {
        $input = ['foo' => 'bar'];
        $plain = json_encode($input, JSON_THROW_ON_ERROR);
        $pretty = json_encode($input, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        $a = $this->type->withAlteredPayload($plain);
        $b = $this->type->withAlteredPayload($pretty);
        self::assertFalse($a->equals($b));
    }

    /** @return array<string, array{0:array<string, mixed>}> */
    public function invalidDefinitionArrayProvider(): array
    {
        return [
            'Missing ID' => [['label' => 'foo', 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'Missing Label' => [['id' => 'bar', 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'Missing Repeat' => [['id' => 'bar', 'label' => 'foo', 'status' => true, 'json' => ['foo' => 'bar']]],
            'Missing Status' => [['id' => 'bar', 'label' => 'foo', 'repeatable' => true, 'json' => ['foo' => 'bar']]],
            'Missing Json' => [['id' => 'bar', 'label' => 'foo', 'repeatable' => true, 'status' => true]],
            'ID is null' => [['id' => null, 'label' => 'foo', 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'ID is empty' => [['id' => '', 'label' => 'foo', 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'ID not string' => [['id' => 1, 'label' => 'foo', 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'Label is null' => [['id' => 'foo', 'label' => null, 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'Label is empty' => [['id' => 'foo', 'label' => '', 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'Label not string' => [['id' => 'foo', 'label' => 1, 'repeatable' => true, 'status' => true, 'json' => ['foo' => 'bar']]],
            'Repeat not bool' => [['id' => 'foo', 'label' => 'bar', 'repeatable' => 1, 'status' => true, 'json' => ['foo' => 'bar']]],
            'Status not bool' => [['id' => 'foo', 'label' => 'bar', 'repeatable' => true, 'status' => 1, 'json' => ['foo' => 'bar']]],
            'Json not array' => [['id' => 'foo', 'label' => 'bar', 'repeatable' => true, 'status' => true, 'json' => 'whut?']],
        ];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @dataProvider invalidDefinitionArrayProvider
     */
    public function testAssertionErrorIsThrownForInvalidDefinitionStructure(array $input): void
    {
        $this->expectException(AssertionFailed::class);
        Definition::fromArray($input);
    }
}
