<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Integration;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\BaseClient;

use function getenv;
use function is_string;
use function strlen;

/** @psalm-suppress MissingConstructor */
abstract class HttpTestCase extends TestCase
{
    /** @var BaseClient */
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = getenv('PRISMIC_REPOSITORY');
        if (! is_string($repository) || strlen($repository) < 3) {
            $this->markTestSkipped('No repository has been configured in the "PRISMIC_REPOSITORY" environment variable');
        }

        $token = getenv('PRISMIC_TOKEN');
        if (! is_string($token) || empty($token)) {
            $this->markTestSkipped('No authentication token has been configured in the "PRISMIC_TOKEN" environment variable');
        }

        $this->client = new BaseClient(
            $token,
            $repository,
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findUriFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
    }
}
