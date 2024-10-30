# Prismic Document Type API Client

[![Build Status](https://github.com/netglue/prismic-doctype-client/workflows/Continuous%20Integration/badge.svg)](https://github.com/netglue/prismic-doctype-client/actions?query=workflow%3A"Continuous+Integration")

[![codecov](https://codecov.io/gh/netglue/prismic-doctype-client/branch/main/graph/badge.svg)](https://codecov.io/gh/netglue/prismic-doctype-client)
[![Psalm Type Coverage](https://shepherd.dev/github/netglue/prismic-doctype-client/coverage.svg)](https://shepherd.dev/github/netglue/prismic-doctype-client)

[![Latest Stable Version](https://poser.pugx.org/netglue/prismic-doctype-client/v/stable)](https://packagist.org/packages/netglue/prismic-doctype-client)
[![Total Downloads](https://poser.pugx.org/netglue/prismic-doctype-client/downloads)](https://packagist.org/packages/netglue/prismic-doctype-client)

## Introduction

This library provides an API client so that you can read and write your document type definitions using the [Prismic Custom Types API](https://prismic.io/docs/technologies/custom-types-api).

Currently, you can list, read, insert and update document types, and do the same for 'Shared Slices'

The client requires that you make use of _(And similarly, it returns instances of)_ the shipped `Definition` or `SharedSlice` value objects.

Typically, you wouldn't interact with the client directly, but as part of a build process that takes care of all that stuff for you. This client is quite fresh, but it's primary use will soon be part of [`netglue/prismic-cli`](https://github.com/netglue/prismic-cli), so that it will become trivial to synchronise your local development document definitions with those in your production Prismic repository _(and vice-versa)_.

It's worth noting that document types and slices are not validated in any way. That's up to you, but, you should get descriptive exceptions when your JSON doesn't validate on the remote server.

## Installation

The only supported installation method is via composer:

```shell
composer require --dev netglue/prismic-doctype-client
```

## Configuration

The client has been designed to work with whatever [PSR-18 HTTP Client](https://packagist.org/providers/psr/http-client-implementation) and [PSR-7 and PSR-17 implementations](https://packagist.org/providers/psr/http-factory-implementation) that you like to use. Once you have got hold of an API token for the custom types API, you can create a client with:

```php
<?php
use Prismic\DocumentType\BaseClient;

$client = new BaseClient(
    'some-token',
    'my-repository-name',
    $httpClient,     // \Psr\Http\Client\ClientInterface
    $requestFactory, // \Psr\Http\Message\RequestFactoryInterface
    $uriFactory,     // \Psr\Http\Message\UriFactoryInterface
    $streamFactory   // \Psr\Http\Message\StreamFactoryInterface
)
```

## Usage

### CRUD for Document Types

```php
use Prismic\DocumentType\Client;
use Prismic\DocumentType\Definition;

assert($client instanceof Client);

// Insert or update a document type:
$client->saveDefinition(Definition::new(
    id: 'my-type',
    label: 'Some Label',
    repeatable: true,
    active: true,
    json: $someJsonPayloadAsAString,
));

// Fetch all remote document type defs
$client->fetchAllDefinitions();

// Fetch a single definition
$client->getDefinition('some-type');

// Delete a document type definition
$client->deleteDefinition('some-type');
```

### CRUD for Shared Slices

```php
use Prismic\DocumentType\SharedSlice;use Prismic\DocumentType\SharedSliceManagementClient;

assert($client instanceof SharedSliceManagementClient);

// Insert or update a slice def:
$client->saveSharedSlice(SharedSlice::new(
    id: 'some-slice-id',
    json: $someJsonPayloadAsAString, 
));

// Fetch all slice defs:
$client->fetchAllSharedSlices();

// Fetch one slice def:
$client->getSharedSlice('whatever');

// Delete a shared slice:
$client->deleteSharedSlice('some-id');
```

## Limitations/Roadmap

### Authentication

Currently, authentication is only possible with a [permanent access token](https://prismic.io/docs/technologies/custom-types-api#permanent-token-recommended) that you create/retrieve from the Prismic repository settings. Session based tokens are not supported.  

## Contributing

Please feel free to get involved with development. The project uses PHPUnit for tests, [Psalm](https://psalm.dev) for static analysis and [Infection](https://infection.github.io) for mutation testing. CI should have your back if you want to submit a feature or fix ;)

## License

[MIT Licensed](LICENSE.md).

## Changelog

See [`CHANGELOG.md`](CHANGELOG.md).
