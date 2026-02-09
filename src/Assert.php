<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use Override;
use Prismic\DocumentType\Exception\AssertionFailed;
use Webmozart\Assert\Assert as WebmozartAssert;

/** @internal */
final class Assert extends WebmozartAssert
{
    /**
     * @throws AssertionFailed
     *
     * @psalm-pure
     */
    #[Override]
    protected static function reportInvalidArgument(string $message): never
    {
        throw new AssertionFailed($message);
    }
}
