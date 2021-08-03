<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use RuntimeException;

use function sprintf;

final class DefinitionNotFound extends RuntimeException implements Exception
{
    public static function withIdentifier(string $id): self
    {
        return new self(sprintf(
            'A custom type with the id "%s" cannot be found',
            $id
        ), 404);
    }
}
