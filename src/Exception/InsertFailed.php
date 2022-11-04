<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use Prismic\DocumentType\Definition;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

final class InsertFailed extends ResponseError
{
    public static function withDefinition(
        Definition $definition,
        RequestInterface $request,
        ResponseInterface $response,
    ): self {
        return self::withHttpExchange(
            sprintf(
                'Failed to insert the definition "%s" because one already exists with that identifier',
                $definition->id(),
            ),
            $request,
            $response,
        );
    }
}
