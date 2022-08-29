<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

final class UnexpectedStatusCode extends ResponseError
{
    public static function withExpectedCode(int $code, RequestInterface $request, ResponseInterface $response): self
    {
        return self::withHttpExchange(
            sprintf(
                'Expected the HTTP response code %d but received %d',
                $code,
                $response->getStatusCode(),
            ),
            $request,
            $response,
        );
    }
}
