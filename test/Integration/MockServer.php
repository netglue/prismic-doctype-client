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
        $this->server = new HttpServer(function (RequestInterface $request): ResponseInterface {
            return $this->handleRequest($request);
        });
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
        $response = self::match($request);
        if (! $response) {
            return new TextResponse('Invalid Request', 500);
        }

        return $response;
    }

    private static function match(RequestInterface $request): ?ResponseInterface
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
                'body' => static function (string $body): bool {
                    return strpos($body, '"id":"not-found"') !== false;
                },
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/insert',
                'file' => __DIR__ . '/responses/POST.customtypes-insert.invalid-spec.http',
                'body' => static function (string $body): bool {
                    return strpos($body, '"id":"invalid-insert"') !== false;
                },
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/insert',
                'file' => __DIR__ . '/responses/POST.customtypes-insert.duplicate.http',
                'body' => static function (string $body): bool {
                    return strpos($body, '"id":"duplicate-insert"') !== false;
                },
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
                'body' => static function (string $body): bool {
                    return strpos($body, '"id":"example"') !== false;
                },
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/update',
                'file' => __DIR__ . '/responses/POST.customtypes-update.not-found.http',
                'body' => static function (string $body): bool {
                    return strpos($body, '"id":"not-found-for-update"') !== false;
                },
            ],
            [
                'method' => 'POST',
                'token' => self::VALID_TOKEN,
                'path' => '/customtypes/update',
                'file' => __DIR__ . '/responses/POST.customtypes-update.invalid-spec.http',
                'body' => static function (string $body): bool {
                    return strpos($body, '"id":"invalid-spec"') !== false;
                },
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
            return null;
        }

        return Serializer::fromString(file_get_contents($match['file']));
    }
}
