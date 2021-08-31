<?php

declare(strict_types=1);

namespace Integration;

use Prismic\DocumentType\Test\Integration\MockServerTestCase;

use function sprintf;

final class MockServerSetupTest extends MockServerTestCase
{
    public function testThatTheServerIsRunningAndRespondsWithTheExpectedOutput(): void
    {
        $request = $this->requestFactory()->createRequest('GET', sprintf('%s/ping', self::apiServerUri()));
        $response = $this->httpClient()->sendRequest($request);
        self::assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        self::assertStringContainsString('pong', $body);
    }
}
