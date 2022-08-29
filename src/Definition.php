<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use JsonSerializable;
use Prismic\DocumentType\Exception\AssertionFailed;

/** @psalm-immutable */
final class Definition implements JsonSerializable
{
    private string $id;
    private string $label;
    private bool $repeatable;
    private bool $active;
    private string $json;

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

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        $expect = ['id', 'label', 'repeatable', 'status', 'json'];
        foreach ($expect as $key) {
            Assert::keyExists($data, $key);
        }

        Assert::string($data['id']);
        Assert::notEmpty($data['id']);
        Assert::string($data['label']);
        Assert::notEmpty($data['label']);
        Assert::isArray($data['json']);
        Assert::boolean($data['repeatable']);
        Assert::boolean($data['status']);

        return self::new(
            $data['id'],
            $data['label'],
            $data['repeatable'],
            $data['status'],
            Json::encodeArray($data['json']),
        );
    }

    /** @return array{id: string, label: string, repeatable: bool, status: bool, json: array<array-key, mixed>} */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'repeatable' => $this->repeatable,
            'status' => $this->active,
            'json' => Json::decodeToArray($this->json),
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

    public function equals(self $other): bool
    {
        return $this->id === $other->id
            &&
            $this->label === $other->label
            &&
            $this->repeatable === $other->repeatable
            &&
            $this->active === $other->active
            &&
            $this->json === $other->json;
    }
}
