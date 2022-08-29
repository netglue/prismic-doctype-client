<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

final class DefinitionNotFound extends ResponseError
{
    public static function withIdentifier(
        string $id,
        RequestInterface $request,
        ResponseInterface $response
    ): self {
        return self::withHttpExchange(
            sprintf('A custom type with the id "%s" cannot be found', $id),
            $request,
            $response,
        );
    }
}
