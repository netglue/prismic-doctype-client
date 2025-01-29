<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class SharedSlice
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $json
     */
    private function __construct(
        public string $id,
        public string $json,
    ) {
    }

    /**
     * @param non-empty-string $id
     * @param non-empty-string $json
     */
    public static function new(string $id, string $json): self
    {
        return new self($id, $json);
    }

    /** @param array<array-key, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        Assert::keyExists($payload, 'id');
        Assert::stringNotEmpty($payload['id']);

        return new self($payload['id'], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function equals(SharedSlice $other): bool
    {
        return $this->id === $other->id
            && $this->json === $other->json;
    }
}
