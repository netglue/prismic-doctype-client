<?php
// phpcs:ignoreFile
declare(strict_types=1);

namespace Prismic\DocumentType;

use Override;
use Prismic\DocumentType\Exception\AssertionFailed;
use Webmozart\Assert\Assert as WebmozartAssert;

final class Assert extends WebmozartAssert
{
    /**
     * @param string $message
     *
     * @return never
     *
     * @throws AssertionFailed
     *
     * @psalm-pure
     */
    #[Override]
    protected static function reportInvalidArgument($message)
    {
        throw new AssertionFailed($message);
    }
}
