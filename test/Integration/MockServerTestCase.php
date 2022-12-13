<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Integration;

use Http\Client\Curl\Client;
use Laminas\Diactoros\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use React\ChildProcess\Process;

use function sprintf;
use function usleep;

use const CURLOPT_CONNECTTIMEOUT_MS;

abstract class MockServerTestCase extends TestCase
{
    private static int $serverPort;
    private static Process $serverProcess;
    private static HttpClient $httpClient;
    private static RequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient()->clearState();
    }

    public static function setUpBeforeClass(): void
    {
        self::$httpClient = new HttpClient(
            new Client(null, null, [CURLOPT_CONNECTTIMEOUT_MS => 100]),
        );
        self::$requestFactory = new RequestFactory();
        self::$serverPort = 8089;
        self::$serverProcess = new Process(
            sprintf('exec php %s/run-server.php %d', __DIR__, self::$serverPort),
            __DIR__,
        );
        self::$serverProcess->start();
        usleep(100000);
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$serverProcess->pipes as $pipe) {
            $pipe->close();
        }

        self::$serverProcess->terminate();
    }

    protected static function apiServerUri(): string
    {
        return sprintf('http://0.0.0.0:%d', self::$serverPort);
    }

    protected function httpClient(): HttpClient
    {
        return self::$httpClient;
    }

    protected function requestFactory(): RequestFactoryInterface
    {
        return self::$requestFactory;
    }
}
