<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Integration;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpClient implements ClientInterface
{
    private ClientInterface $client;
    private ?RequestInterface $lastRequest = null;
    private ?ResponseInterface $lastResponse = null;
    private int $invocationCount = 0;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->lastResponse = $this->client->sendRequest($request);
        $this->invocationCount++;

        return $this->lastResponse;
    }

    public function clearState(): void
    {
        $this->lastRequest = $this->lastResponse = null;
        $this->invocationCount = 0;
    }

    public function lastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }

    public function lastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }
}
