<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Integration;

use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Prismic\DocumentType\BaseClient;
use Prismic\DocumentType\Definition;
use Prismic\DocumentType\Exception\AuthenticationFailed;
use Prismic\DocumentType\Exception\DefinitionNotFound;
use Prismic\DocumentType\Exception\InsertFailed;
use Prismic\DocumentType\Exception\InvalidDefinition;
use Prismic\DocumentType\Exception\RequestFailure;
use Prismic\DocumentType\Exception\UnexpectedStatusCode;
use Prismic\DocumentType\Exception\UpdateFailed;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

use function assert;
use function count;

class BaseClientTest extends MockServerTestCase
{
    private const EXPECTED_REPOSITORY = 'expected-repo-name';

    /** @var BaseClient */
    private $client;

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
            self::apiServerUri()
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
            'http://192.0.2.1'
        );

        try {
            $client->getDefinition('whatever');
            $this->fail('No exception was thrown');
        } catch (RequestFailure $error) {
            self::assertInstanceOf(ClientExceptionInterface::class, $error->getPrevious());
        } catch (Throwable $error) {
            $this->fail('Unexpected exception type');
        }
    }

    public function testFetchAllDefinitionsYieldsAnIterableOfDefinitions(): void
    {
        $definitions = $this->client->fetchAllDefinitions();
        self::assertGreaterThan(0, count($definitions));
        self::assertContainsOnlyInstancesOf(Definition::class, $definitions);
    }

    public function testASingleDefinitionCanBeRetrieved(): void
    {
        $definition = $this->client->getDefinition('example');
        self::assertEquals('example', $definition->id());
    }

    public function testAnExceptionIsThrownWhenTheDefinitionIsNotFound(): void
    {
        $this->expectException(DefinitionNotFound::class);
        $this->client->getDefinition('not-found');
    }

    public function testValidInsertCausesNoExceptions(): void
    {
        $this->expectNotToPerformAssertions();
        $this->client->createDefinition(Definition::new(
            'not-found',
            'Foo',
            true,
            true,
            '{"foo":"bar"}'
        ));
    }

    public function testThatAnExceptionIsThrownInsertingADefinitionWithAnInvalidSpec(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->client->createDefinition(Definition::new(
            'invalid-insert',
            'Foo',
            true,
            true,
            '{"foo":"bar"}'
        ));
    }

    public function testThatAnExceptionIsThrownInsertingADuplicateDefinition(): void
    {
        $this->expectException(InsertFailed::class);
        $this->client->createDefinition(Definition::new(
            'duplicate-insert',
            'Foo',
            true,
            true,
            '{"foo":"bar"}'
        ));
    }

    public function testUnexpectedStatusCodeDuringInsertWhenResponseCodeIsNotExpected(): void
    {
        $this->expectException(UnexpectedStatusCode::class);
        $this->client->createDefinition(Definition::new(
            'no-existing-matches',
            'In Mock Server',
            true,
            true,
            '{"causes":"a 500 error"}'
        ));
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
        $this->client->deleteDefinition('will-be-a-500');
    }

    public function testAValidUpdateWillCauseNoExceptions(): void
    {
        $this->expectNotToPerformAssertions();
        $this->client->updateDefinition(Definition::new(
            'example',
            'Foo',
            true,
            true,
            '{"foo":"bar"}'
        ));
    }

    public function testUpdateForTypeNotFoundIsExceptional(): void
    {
        $this->expectException(UpdateFailed::class);
        $this->client->updateDefinition(Definition::new(
            'not-found-for-update',
            'Foo',
            true,
            true,
            '{"foo":"bar"}'
        ));
    }

    public function testAnExceptionIsThrownDuringUpdateWithAnInvalidSpec(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->client->updateDefinition(Definition::new(
            'invalid-spec',
            'Foo',
            true,
            true,
            '{"foo":"bar"}'
        ));
    }

    public function testUnexpectedStatusCodeDuringUpdateWhenResponseCodeIsNotExpected(): void
    {
        $this->expectException(UnexpectedStatusCode::class);
        $this->client->updateDefinition(Definition::new(
            'no-existing-matches',
            'In Mock Server',
            true,
            true,
            '{"causes":"a 500 error"}'
        ));
    }

    public function testRemote401ThrowsException(): void
    {
        $this->expectException(AuthenticationFailed::class);
        $this->client->getDefinition('401');
    }

    public function testRemote403ThrowsException(): void
    {
        $this->expectException(AuthenticationFailed::class);
        $this->client->getDefinition('403');
    }

    /**
     * @depends testASingleDefinitionCanBeRetrieved
     * @depends testAValidUpdateWillCauseNoExceptions
     */
    public function testThatWhenTheTypeExistsAnUpdateWillBeIssuedInSave(): void
    {
        $this->client->saveDefinition(Definition::new(
            'example',
            'Foo',
            true,
            true,
            '{"foo":"bar"}'
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
            '{"Main":{"value":{"type":"Number","config":{"label":"Value","min":0,"max":10}}}}'
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/customtypes/example', $last->getUri()->getPath());
    }

    /**
     * @depends testAnExceptionIsThrownWhenTheDefinitionIsNotFound
     * @depends testValidInsertCausesNoExceptions
     */
    public function testThatSaveWillCatchNotFoundErrorsAndPerformAnInsert(): void
    {
        $this->client->saveDefinition(Definition::new(
            'not-found',
            'Example',
            true,
            true,
            '{"foo":"bar"}'
        ));

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        self::assertStringContainsString('/customtypes/insert', $last->getUri()->getPath());
    }

    /** @depends testASingleDefinitionCanBeRetrieved */
    public function testThatTheRepositoryHeaderIsSetInTheRequest(): void
    {
        $this->client->getDefinition('example');

        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        $header = $last->getHeaderLine('repository');
        self::assertStringContainsString(self::EXPECTED_REPOSITORY, $header);
    }

    /** @depends testThatTheRepositoryHeaderIsSetInTheRequest */
    public function testThatAClientCanBeGeneratedForADifferentRepository(): void
    {
        $other = $this->client->withAlternativeRepository('new-repository', MockServer::VALID_TOKEN);
        $other->getDefinition('example');
        $last = $this->httpClient()->lastRequest();
        assert($last instanceof RequestInterface);
        $header = $last->getHeaderLine('repository');
        self::assertStringContainsString('new-repository', $header);
    }
}
