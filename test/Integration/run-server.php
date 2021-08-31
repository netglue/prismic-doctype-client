<?php

declare(strict_types=1);

use Prismic\DocumentType\Assert;
use Prismic\DocumentType\Test\Integration\MockServer;

require __DIR__ . '/../../vendor/autoload.php';

$port = $argv[1] ?? 8085;
Assert::numeric($port);

$server = new MockServer((int) $port);
$server->start();
