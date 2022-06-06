<?php
// phpcs:ignoreFile
declare(strict_types=1);

namespace Prismic\DocumentType;

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
    protected static function reportInvalidArgument($message)
    {
        throw new AssertionFailed($message);
    }
}
