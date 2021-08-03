<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\Definition;
use Prismic\DocumentType\Exception\AssertionFailed;

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
}
