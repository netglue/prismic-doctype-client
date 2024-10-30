<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Integration;

use Laminas\Diactoros\Response\Serializer;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Socket\SocketServer;

use function file_get_contents;
use function is_callable;
use function sprintf;
use function strpos;
use function strtoupper;

use const PHP_EOL;

final class MockServer
{
    public const VALID_TOKEN = 'Valid Token';
    private HttpServer $server;
    private SocketServer $socket;

    /**
     * Seconds before the server shuts down automatically
     */
    private int $timeout = 10;

    public function __construct(int $port)
    {
        $this->server = new HttpServer(
            fn (RequestInterface $request): ResponseInterface => $this->handleRequest($request),
        );
        $this->socket = new SocketServer(sprintf('0.0.0.0:%d', $port));
    }

    public function start(): void
    {
        Loop::addTimer($this->timeout, function (): void {
            $this->stop();
        });
        $this->server->listen($this->socket);
    }

    public function stop(): void
    {
        $this->server->removeAllListeners();
        $this->socket->close();
    }

    private function handleRequest(RequestInterface $request): ResponseInterface
    {
        $responses = [
            [
                'method' => 'GET',
                'token' => null,
                'path' => '/ping',
                'file' => __DIR__ . '/responses/GET.ping.http',
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes',
                'file' => __DIR__ . '/responses/GET.customtypes.http',
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/example',
                'file' => __DIR__ . '/responses/GET.customtypes-example.http',
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/not-found',
                'file' => __DIR__ . '/responses/GET.customtypes-not-found.http',
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/insert',
                'file' => __DIR__ . '/responses/POST.customtypes-insert.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"not-found"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/insert',
                'file' => __DIR__ . '/responses/POST.customtypes-insert.invalid-spec.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"invalid-insert"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/insert',
                'file' => __DIR__ . '/responses/POST.customtypes-insert.duplicate.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"duplicate-insert"') !== false,
            ],
            [
                'method' => 'DELETE',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/not-found',
                'file' => __DIR__ . '/responses/DELETE.customtypes-not-found.http',
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/update',
                'file' => __DIR__ . '/responses/POST.customtypes-update.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"example"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/update',
                'file' => __DIR__ . '/responses/POST.customtypes-update.not-found.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"not-found-for-update"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/update',
                'file' => __DIR__ . '/responses/POST.customtypes-update.invalid-spec.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"invalid-spec"') !== false,
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/401',
                'file' => __DIR__ . '/responses/401.http',
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/403',
                'file' => __DIR__ . '/responses/403.http',
            ],
            // Slices
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/slices',
                'file' => __DIR__ . '/responses/GET.slices.http',
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/insert',
                'file' => __DIR__ . '/responses/POST.slices-insert.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"example-slice"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/insert',
                'file' => __DIR__ . '/responses/POST.slices-insert.duplicate.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"duplicate-slice"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/insert',
                'file' => __DIR__ . '/responses/POST.slices-insert.invalid.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"invalid-slice"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/insert',
                'file' => __DIR__ . '/responses/POST.slices-insert.unexpected.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"unexpected"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/update',
                'file' => __DIR__ . '/responses/POST.slices-update.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"update"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/update',
                'file' => __DIR__ . '/responses/POST.slices-insert.invalid.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"update-invalid"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/update',
                'file' => __DIR__ . '/responses/POST.slices-update.not-found.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"update-missing"') !== false,
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/update',
                'file' => __DIR__ . '/responses/POST.slices-insert.unexpected.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"update-unexpected"') !== false,
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/example-slice',
                'file' => __DIR__ . '/responses/GET.slice.example.http',
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/not-found',
                'file' => __DIR__ . '/responses/GET.slices.not-found.http',
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/update',
                'file' => __DIR__ . '/responses/POST.slices-update.http',
                'body' => static fn (string $body): bool => strpos($body, '"was":"updated"') !== false,
            ],
            [
                'method' => 'GET',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/insert-save',
                'file' => __DIR__ . '/responses/GET.slices.not-found.http',
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/insert',
                'file' => __DIR__ . '/responses/POST.slices-insert.http',
                'body' => static fn (string $body): bool => strpos($body, '"id":"insert-save"') !== false,
            ],
            [
                'method' => 'DELETE',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/delete-me',
                'file' => __DIR__ . '/responses/DELETE.slices.http',
            ],
            [
                'method' => 'DELETE',
                'token' => self::VALID_TOKEN,
                'path' => '/slices/delete-weird',
                'file' => __DIR__ . '/responses/POST.slices-insert.unexpected.http',
            ],
        ];

        $match = null;

        foreach ($responses as $response) {
            if ($response['method'] !== strtoupper($request->getMethod())) {
                continue;
            }

            if ($request->getUri()->getPath() !== $response['path']) {
                continue;
            }

            $header = $request->getHeaderLine('Authorization');
            $header = $header === '' ? null : $header;
            $token = $response['token']
                ? sprintf('Bearer %s', $response['token'])
                : null;

            if ($header !== $token) {
                continue;
            }

            $body = (string) $request->getBody();
            $matcher = $response['body'] ?? null;

            if (is_callable($matcher) && $matcher($body) === false) {
                continue;
            }

            $match = $response;
        }

        if (! $match) {
            return new TextResponse('The request did not match any fixtures' . PHP_EOL, 999);
        }

        return Serializer::fromString(file_get_contents($match['file']));
    }
}
