<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class AuthenticationFailed extends ResponseError
{
    public static function new(RequestInterface $request, ResponseInterface $response): self
    {
        return self::withHttpExchange(
            'Authentication failed. This could mean a missing or invalid token, or an incorrect repository',
            $request,
            $response
        );
    }
}
