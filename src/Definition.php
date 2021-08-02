<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use JsonSerializable;
use Prismic\DocumentType\Exception\AssertionFailed;

/**
 * @psalm-immutable
 */
final class Definition implements JsonSerializable
{
    /** @var string */
    private $id;
    /** @var string */
    private $label;
    /** @var bool */
    private $repeatable;
    /** @var bool */
    private $active;
    /** @var string */
    private $json;

    private function __construct(
        string $id,
        string $label,
        bool $repeatable,
        bool $active,
        string $json
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->repeatable = $repeatable;
        $this->active = $active;
        $this->json = $json;
    }

    public static function new(
        string $id,
        string $label,
        bool $repeatable,
        bool $active,
        string $json
    ): self {
        return new self($id, $label, $repeatable, $active, $json);
    }

    /** @return array{id: string, label: string, repeatable: bool, status: bool, json: string} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'repeatable' => $this->repeatable,
            'status' => $this->active,
            'json' => $this->json,
        ];
    }

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function isRepeatable(): bool
    {
        return $this->repeatable;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function json(): string
    {
        return $this->json;
    }

    public function withAlteredPayload(string $json): self
    {
        $clone = clone $this;
        $clone->json = $json;

        return $clone;
    }

    public function withActivationStatus(bool $status): self
    {
        $clone = clone $this;
        $clone->active = $status;

        return $clone;
    }

    public function withRepeatable(): self
    {
        $clone = clone $this;
        $clone->repeatable = true;

        return $clone;
    }

    public function withoutRepeatable(): self
    {
        $clone = clone $this;
        $clone->repeatable = false;

        return $clone;
    }

    /**
     * @psalm-param non-empty-string $label
     *
     * @throws AssertionFailed
     */
    public function withNewLabel(string $label): self
    {
        Assert::notEmpty($label);
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }
}
