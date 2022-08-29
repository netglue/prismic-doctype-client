<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use Prismic\DocumentType\Exception\AuthenticationFailed;
use Prismic\DocumentType\Exception\DefinitionNotFound;
use Prismic\DocumentType\Exception\InsertFailed;
use Prismic\DocumentType\Exception\InvalidDefinition;
use Prismic\DocumentType\Exception\RequestFailure;
use Prismic\DocumentType\Exception\UnexpectedStatusCode;
use Prismic\DocumentType\Exception\UpdateFailed;
use Psr\Http\Client\ClientExceptionInterface as PsrHttpError;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

use function sprintf;

final class BaseClient implements Client
{
    private const DEFAULT_BASE_URI = 'https://customtypes.prismic.io';

    private string $token;
    private string $repository;
    private HttpClient $httpClient;
    private RequestFactoryInterface $requestFactory;
    private UriFactoryInterface $uriFactory;
    private StreamFactoryInterface $streamFactory;
    private string $baseUri;

    public function __construct(
        string $token,
        string $repository,
        HttpClient $httpClient,
        RequestFactoryInterface $requestFactory,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory,
        string $baseUri = self::DEFAULT_BASE_URI
    ) {
        $this->token = $token;
        $this->repository = $repository;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
        $this->baseUri = $baseUri;
    }

    public function withAlternativeRepository(
        string $repository,
        string $token
    ): self {
        return new self(
            $token,
            $repository,
            $this->httpClient,
            $this->requestFactory,
            $this->uriFactory,
            $this->streamFactory,
            $this->baseUri
        );
    }

    public function getDefinition(string $id): Definition
    {
        $request = $this->request('GET', sprintf('/customtypes/%s', $id));
        $response = $this->send($request);

        if ($response->getStatusCode() === 404) {
            throw DefinitionNotFound::withIdentifier($id, $request, $response);
        }

        $body = Json::decodeToArray((string) $response->getBody());

        return Definition::fromArray($body);
    }

    /** @inheritDoc */
    public function fetchAllDefinitions(): iterable
    {
        $response = $this->send(
            $this->request('GET', '/customtypes')
        );

        $body = Json::decodeToArray((string) $response->getBody());
        $list = [];
        foreach ($body as $item) {
            Assert::isArray($item);
            $definition = Definition::fromArray($item);
            $list[$definition->id()] = $definition;
        }

        return $list;
    }

    public function deleteDefinition(string $id): void
    {
        /**
         * The API returns a 204 even if the type does not exist, so the only plausible errors are
         * network failures or authentication failures.
         */
        $request = $this->request('DELETE', sprintf('/customtypes/%s', $id));
        $response = $this->send($request);

        if ($response->getStatusCode() !== 204) {
            throw UnexpectedStatusCode::withExpectedCode(204, $request, $response);
        }
    }

    public function createDefinition(Definition $definition): void
    {
        $request = $this->request('POST', '/customtypes/insert')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(
                Json::encodeObject($definition)
            ));

        $response = $this->send($request);

        if ($response->getStatusCode() === 400) {
            throw InvalidDefinition::new($request, $response);
        }

        if ($response->getStatusCode() === 409) {
            throw InsertFailed::withDefinition($definition, $request, $response);
        }

        if ($response->getStatusCode() !== 201) {
            throw UnexpectedStatusCode::withExpectedCode(201, $request, $response);
        }
    }

    public function updateDefinition(Definition $definition): void
    {
        $request = $this->request('POST', '/customtypes/update')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(
                Json::encodeObject($definition)
            ));

        $response = $this->send($request);

        if ($response->getStatusCode() === 400) {
            throw InvalidDefinition::new($request, $response);
        }

        if ($response->getStatusCode() === 422) {
            throw UpdateFailed::withDefinition($definition, $request, $response);
        }

        if ($response->getStatusCode() !== 204) {
            throw UnexpectedStatusCode::withExpectedCode(204, $request, $response);
        }
    }

    public function saveDefinition(Definition $definition): void
    {
        try {
            $current = $this->getDefinition($definition->id());
        } catch (DefinitionNotFound $error) {
            $this->createDefinition($definition);

            return;
        }

        if ($definition->equals($current)) {
            return;
        }

        $this->updateDefinition($definition);
    }

    private function request(string $method, string $path): RequestInterface
    {
        $request = $this->requestFactory->createRequest(
            $method,
            $this->uriFactory->createUri($this->baseUri)
                ->withPath($path)
        );

        return $request->withHeader('repository', $this->repository)
            ->withHeader('Authorization', sprintf('Bearer %s', $this->token))
            ->withHeader('Accept', 'application/json');
    }

    private function send(RequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (PsrHttpError $error) {
            throw RequestFailure::withPsrError($request, $error);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 401 || $statusCode === 403) {
            throw AuthenticationFailed::new($request, $response);
        }

        return $response;
    }
}
