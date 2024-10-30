<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use Prismic\DocumentType\Definition;
use Prismic\DocumentType\SharedSlice;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

final class UpdateFailed extends ResponseError
{
    public static function withDefinition(
        Definition $definition,
        RequestInterface $request,
        ResponseInterface $response,
    ): self {
        return self::withHttpExchange(
            sprintf(
                'Failed to update the definition "%s" because it has not yet been created',
                $definition->id(),
            ),
            $request,
            $response,
        );
    }

    public static function forSlice(
        SharedSlice $definition,
        RequestInterface $request,
        ResponseInterface $response,
    ): self {
        return self::withHttpExchange(
            sprintf(
                'Failed to update the shared slice "%s" because it has not yet been created',
                $definition->id,
            ),
            $request,
            $response,
        );
    }
}
