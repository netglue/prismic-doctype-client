<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use function json_encode;

final class SharedSlice
{
    /**
     * @param non-empty-string $id
     * @param non-empty-string $json
     */
    private function __construct(
        public readonly string $id,
        public readonly string $json,
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

        return new self($payload['id'], json_encode($payload));
    }

    public function equals(SharedSlice $other): bool
    {
        return $this->id === $other->id
            && $this->json === $other->json;
    }
}
