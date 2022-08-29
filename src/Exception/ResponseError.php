<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Exception;

use Prismic\DocumentType\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/** @internal */
abstract class ResponseError extends RuntimeException implements Exception
{
    protected ?RequestInterface $request = null;
    protected ?ResponseInterface $response = null;

    final public function __construct(string $message, int $code, ?Throwable $previous = null) // phpcs:ignore
    {
        parent::__construct($message, $code, $previous);
    }

    /** @return static */
    final protected static function withHttpExchange(
        string $message,
        RequestInterface $request,
        ResponseInterface $response
    ): self {
        $error = new static($message, $response->getStatusCode());
        $error->request = $request;
        $error->response = $response;

        return $error;
    }

    public function request(): RequestInterface
    {
        Assert::isInstanceOf(
            $this->request,
            RequestInterface::class,
            'This error was not constructed with a request instance',
        );

        return $this->request;
    }

    public function response(): ResponseInterface
    {
        Assert::isInstanceOf(
            $this->response,
            ResponseInterface::class,
            'This error was not constructed with a response instance',
        );

        return $this->response;
    }
}
