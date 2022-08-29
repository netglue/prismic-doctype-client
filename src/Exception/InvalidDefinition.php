<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class InvalidDefinition extends ResponseError
{
    public static function new(RequestInterface $request, ResponseInterface $response): self
    {
        return self::withHttpExchange(
            'The document type definition was rejected because it (most likely) has validation errors',
            $request,
            $response,
        );
    }
}
