<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Integration;

use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Override;
use PHPUnit\Framework\Attributes\Depends;
use Prismic\DocumentType\BaseClient;
use Prismic\DocumentType\Definition;
use Prismic\DocumentType\Exception\AuthenticationFailed;
use Prismic\DocumentType\Exception\DefinitionNotFound;
use Prismic\DocumentType\Exception\InsertFailed;
use Prismic\DocumentType\Exception\InvalidDefinition;
use Prismic\DocumentType\Exception\RequestFailure;
use Prismic\DocumentType\Exception\ResponseError;
use Prismic\DocumentType\Exception\UnexpectedStatusCode;
use Prismic\DocumentType\Exception\UpdateFailed;
use Prismic\DocumentType\SharedSlice;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

use function assert;
use function count;
use function PHPUnit\Framework\assertStringContainsString;

final class BaseClientTest extends MockServerTestCase
{
    private const EXPECTED_REPOSITORY = 'expected-repo-name';

    private BaseClient $client;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new BaseClient(
            MockServer::VALID_TOKEN,
            self::EXPECTED_REPOSITORY,
            $this->httpClient(),
            $this->requestFactory(),
            new UriFactory(),
            new StreamFactory(),
            self::apiServerUri(),
        );
    }

    protected function assertExceptionContainsReferenceToTheLastErroneousRequest(ResponseError $error): void
    {
        $lastRequest = $this->httpClient()->lastRequest();
        self::assertInstanceOf(RequestInterface::class, $lastRequest, 'The HTTP client has not issued any requests');
        self::assertSame($lastRequest, $error->request(), 'The request referenced in the error does not match the last request issued by the HTTP client');
    }

    protected function assertExceptionHasResponseWithMatchingHttpStatusCode(ResponseError $error): void
    {
        self::assertEquals(
            $error->response()->getStatusCode(),
            $error->getCode(),
            'The error code does not match the response status code',
        );
    }

    public function testThatANetworkErrorWillBeWrappedInARequestFailureException(): void
    {
        $client = new BaseClient(
            'whatever',
            'anything',
            $this->httpClient(),
            $this->requestFactory(),
            new UriFactory(),
            new StreamFactory(),
            'http://192.0.2.1',
        );

        $this->expectException(RequestFailure::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('The request to "/customtypes/whatever" failed');

        try {
            $client->getDefinition('whatever');
        } catch (RequestFailure $error) {
            self::assertInstanceOf(ClientExceptionInterface::class, $error->getPrevious());
            $lastRequest = $this->httpClient()->lastRequest();
            assert($lastRequest instanceof RequestInterface);
            self::assertSame($lastRequest, $error->failedRequest());

            throw $error;
        }
    }

    public function testFetchAllDefinitionsYieldsAnIterableOfDefinitions(): void
    {
        $definitions = $this->client->fetchAllDefinitions();
        self::assertGreaterThan(0, count($definitions));
        self::assertContainsOnlyInstancesOf(Definition::class, $definitions);
        foreach ($definitions as $key => $definition) {
            self::assertIsString($key);
            self::assertEquals($definition->id(), $key);
        }
    }

    public function testASingleDefinitionCanBeRetrieved(): void
    {
        $definition = $this->client->getDefinition('example');
        self::assertEquals('example', $definition->id());
    }

    public function testAnExceptionIsThrownWhenTheDefinitionIsNotFound(): void
    {
        $this->expectException(DefinitionNotFound::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('A custom type with the id "not-found" cannot be found');
        try {
            $this->client->getDefinition('not-found');
        } catch (DefinitionNotFound $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testValidInsertCausesNoExceptions(): void
    {
        $this->client->createDefinition(Definition::new(
            'not-found',
            'Foo',
            true,
            true,
            '{"foo":"bar"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/insert', $last->getUri()->getPath());
    }

    public function testThatAnExceptionIsThrownInsertingADefinitionWithAnInvalidSpec(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage('The document type definition was rejected');
        $this->expectExceptionCode(400);
        try {
            $this->client->createDefinition(Definition::new(
                'invalid-insert',
                'Foo',
                true,
                true,
                '{"foo":"bar"}',
            ));
        } catch (InvalidDefinition $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testThatAnExceptionIsThrownInsertingADuplicateDefinition(): void
    {
        $this->expectException(InsertFailed::class);
        $this->expectExceptionMessage('Failed to insert the definition "duplicate-insert" because one already exists with that identifier');
        $this->expectExceptionCode(409);
        try {
            $this->client->createDefinition(Definition::new(
                'duplicate-insert',
                'Foo',
                true,
                true,
                '{"foo":"bar"}',
            ));
        } catch (InsertFailed $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testUnexpectedStatusCodeDuringInsertWhenResponseCodeIsNotExpected(): void
    {
        $this->expectException(UnexpectedStatusCode::class);
        $this->expectExceptionMessage('Expected the HTTP response code 201 but received 500');
        $this->expectExceptionCode(500);
        try {
            $this->client->createDefinition(Definition::new(
                'no-existing-matches',
                'In Mock Server',
                true,
                true,
                '{"causes":"a 500 error"}',
            ));
        } catch (UnexpectedStatusCode $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testSuccessfulDeleteDoesNotCauseAnError(): void
    {
        $this->client->deleteDefinition('not-found');

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/not-found', $last->getUri()->getPath());
    }

    public function testDeleteWillThrowUnexpectedStatusCodeInOtherSituations(): void
    {
        $this->expectException(UnexpectedStatusCode::class);
        $this->expectExceptionMessage('Expected the HTTP response code 204 but received 500');
        $this->expectExceptionCode(500);
        try {
            $this->client->deleteDefinition('will-be-a-500');
        } catch (UnexpectedStatusCode $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testAValidUpdateWillCauseNoExceptions(): void
    {
        $this->client->updateDefinition(Definition::new(
            'example',
            'Foo',
            true,
            true,
            '{"foo":"bar"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/update', $last->getUri()->getPath());
    }

    public function testUpdateForTypeNotFoundIsExceptional(): void
    {
        $this->expectException(UpdateFailed::class);
        $this->expectExceptionMessage('Failed to update the definition "not-found-for-update" because it has not yet been created');
        $this->expectExceptionCode(422);
        try {
            $this->client->updateDefinition(Definition::new(
                'not-found-for-update',
                'Foo',
                true,
                true,
                '{"foo":"bar"}',
            ));
        } catch (UpdateFailed $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testAnExceptionIsThrownDuringUpdateWithAnInvalidSpec(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage('The document type definition was rejected');
        $this->expectExceptionCode(400);
        try {
            $this->client->updateDefinition(Definition::new(
                'invalid-spec',
                'Foo',
                true,
                true,
                '{"foo":"bar"}',
            ));
        } catch (InvalidDefinition $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testUnexpectedStatusCodeDuringUpdateWhenResponseCodeIsNotExpected(): void
    {
        $this->expectException(UnexpectedStatusCode::class);
        $this->expectExceptionMessage('Expected the HTTP response code 204 but received 500');
        $this->expectExceptionCode(500);
        try {
            $this->client->updateDefinition(Definition::new(
                'no-existing-matches',
                'In Mock Server',
                true,
                true,
                '{"causes":"a 500 error"}',
            ));
        } catch (InvalidDefinition $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testRemote401ThrowsException(): void
    {
        $this->expectException(AuthenticationFailed::class);
        $this->expectExceptionMessage('Authentication failed');
        $this->expectExceptionCode(401);
        try {
            $this->client->getDefinition('401');
        } catch (AuthenticationFailed $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    public function testRemote403ThrowsException(): void
    {
        $this->expectException(AuthenticationFailed::class);
        $this->expectExceptionMessage('Authentication failed');
        $this->expectExceptionCode(403);
        try {
            $this->client->getDefinition('403');
        } catch (AuthenticationFailed $error) {
            $this->assertExceptionContainsReferenceToTheLastErroneousRequest($error);
            $this->assertExceptionHasResponseWithMatchingHttpStatusCode($error);

            throw $error;
        }
    }

    #[Depends('testASingleDefinitionCanBeRetrieved')]
    #[Depends('testAValidUpdateWillCauseNoExceptions')]
    public function testThatWhenTheTypeExistsAnUpdateWillBeIssuedInSave(): void
    {
        $this->client->saveDefinition(Definition::new(
            'example',
            'Foo',
            true,
            true,
            '{"foo":"bar"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/update', $last->getUri()->getPath());
    }

    public function testThatWhenTheCurrentDefinitionIsIdenticalNoUpdateWillBeIssuedInSave(): void
    {
        $this->client->saveDefinition(Definition::new(
            'example',
            'Example',
            true,
            true,
            '{"Main":{"value":{"type":"Number","config":{"label":"Value","min":0,"max":10}}}}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/customtypes/example', $last->getUri()->getPath());
    }

    #[Depends('testAnExceptionIsThrownWhenTheDefinitionIsNotFound')]
    #[Depends('testValidInsertCausesNoExceptions')]
    public function testThatSaveWillCatchNotFoundErrorsAndPerformAnInsert(): void
    {
        $this->client->saveDefinition(Definition::new(
            'not-found',
            'Example',
            true,
            true,
            '{"foo":"bar"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/customtypes/insert', $last->getUri()->getPath());
    }

    #[Depends('testASingleDefinitionCanBeRetrieved')]
    public function testThatTheRepositoryHeaderIsSetInTheRequest(): void
    {
        $this->client->getDefinition('example');

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        $header = $last->getHeaderLine('repository');
        self::assertStringContainsString(self::EXPECTED_REPOSITORY, $header);
    }

    #[Depends('testThatTheRepositoryHeaderIsSetInTheRequest')]
    public function testThatAClientCanBeGeneratedForADifferentRepository(): void
    {
        $other = $this->client->withAlternativeRepository('new-repository', MockServer::VALID_TOKEN);
        $other->getDefinition('example');
        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        $header = $last->getHeaderLine('repository');
        self::assertStringContainsString('new-repository', $header);
    }

    public function testSuccessfulFetchOfAllSlices(): void
    {
        $results = $this->client->fetchAllSharedSlices();
        self::assertGreaterThan(0, count($results));
        self::assertContainsOnlyInstancesOf(SharedSlice::class, $results);
        foreach ($results as $key => $definition) {
            self::assertIsString($key);
            self::assertEquals($definition->id, $key);
        }
    }

    public function testCreateSharedSlice(): void
    {
        $this->client->createSharedSlice(SharedSlice::new(
            'example-slice',
            '{"id":"example-slice"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/slices/insert', $last->getUri()->getPath());
    }

    public function testCreateDuplicateSharedSlice(): void
    {
        $this->expectException(InsertFailed::class);
        $this->expectExceptionMessage('duplicate-slice');
        $this->client->createSharedSlice(SharedSlice::new(
            'duplicate-slice',
            '{"id":"duplicate-slice"}',
        ));
    }

    public function testCreateInvalidSharedSlice(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage('slice definition was rejected');
        $this->client->createSharedSlice(SharedSlice::new(
            'invalid-slice',
            '{"id":"invalid-slice"}',
        ));
    }

    public function testCreateSharedSliceWithUnexpectedResponse(): void
    {
        $this->expectException(UnexpectedStatusCode::class);
        $this->expectExceptionMessage('Expected the HTTP response code 201');
        $this->client->createSharedSlice(SharedSlice::new(
            'unexpected',
            '{"id":"unexpected"}',
        ));
    }

    public function testUpdateSharedSlice(): void
    {
        $this->client->updateSharedSlice(SharedSlice::new(
            'update',
            '{"id":"update"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/slices/update', $last->getUri()->getPath());
    }

    public function testUpdateSharedSliceWithInvalidSpec(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage('slice definition was rejected');
        $this->client->updateSharedSlice(SharedSlice::new(
            'update-invalid',
            '{"id":"update-invalid"}',
        ));
    }

    public function testUpdateSharedSliceThatDoesNotExist(): void
    {
        $this->expectException(UpdateFailed::class);
        $this->expectExceptionMessage('update-missing');
        $this->client->updateSharedSlice(SharedSlice::new(
            'update-missing',
            '{"id":"update-missing"}',
        ));
    }

    public function testUpdateSliceWithUnexpectedResponse(): void
    {
        $this->expectException(UnexpectedStatusCode::class);
        $this->expectExceptionMessage('Expected the HTTP response code 204');
        $this->client->updateSharedSlice(SharedSlice::new(
            'update-unexpected',
            '{"id":"update-unexpected"}',
        ));
    }

    public function testGetSliceById(): SharedSlice
    {
        $slice = $this->client->getSharedSlice('example-slice');
        self::assertSame('example-slice', $slice->id);

        return $slice;
    }

    public function testGetSliceWhenNotFound(): void
    {
        $this->expectException(DefinitionNotFound::class);
        $this->expectExceptionMessage('not-found');
        $this->client->getSharedSlice('not-found');
    }

    #[Depends('testGetSliceById')]
    public function testSaveDoesNothingWhenTheSliceHasNotChanged(SharedSlice $slice): void
    {
        $this->client->saveSharedSlice($slice);

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertSame('GET', $last->getMethod());
        self::assertStringContainsString('/slices/example-slice', $last->getUri()->getPath());
    }

    public function testSaveUpdatesWhenTheSliceIsChanged(): void
    {
        $this->client->saveSharedSlice(SharedSlice::new(
            'example-slice',
            '{"id":"example-slice", "was":"updated"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/slices/update', $last->getUri()->getPath());
    }

    public function testSaveInsertsWhenTheSliceIsNotFound(): void
    {
        $this->client->saveSharedSlice(SharedSlice::new(
            'insert-save',
            '{"id":"insert-save"}',
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/slices/insert', $last->getUri()->getPath());
    }

    public function testDeleteSharedSlice(): void
    {
        $this->client->deleteSharedSlice('delete-me');

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertSame('DELETE', $last->getMethod());
        self::assertStringContainsString('/slices/delete-me', $last->getUri()->getPath());
    }

    public function testDeleteWithUnexpectedResponse(): void
    {
        try {
            $this->client->deleteSharedSlice('delete-weird');
            self::fail('Exception expected');
        } catch (UnexpectedStatusCode $e) {
            assertStringContainsString('Expected the HTTP response code 204', $e->getMessage());
        }

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertSame('DELETE', $last->getMethod());
        self::assertStringContainsString('/slices/delete-weird', $last->getUri()->getPath());
    }
}
