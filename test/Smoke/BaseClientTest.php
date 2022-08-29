<?php

declare(strict_types=1);

namespace Prismic\DocumentType\Test\Smoke;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;
use Prismic\DocumentType\BaseClient;
use Prismic\DocumentType\Definition;
use Prismic\DocumentType\Exception\AuthenticationFailed;
use Prismic\DocumentType\Exception\DefinitionNotFound;
use Prismic\DocumentType\Exception\InsertFailed;
use Prismic\DocumentType\Exception\InvalidDefinition;
use Prismic\DocumentType\Exception\UpdateFailed;
use Traversable;

use function count;
use function getenv;
use function is_string;
use function iterator_to_array;
use function strlen;
use function uniqid;

/**
 * @psalm-suppress MissingConstructor
 * @group LiveAPI
 */
final class BaseClientTest extends TestCase
{
    protected BaseClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = getenv('PRISMIC_REPOSITORY');
        if (! is_string($repository) || strlen($repository) < 3) {
            $this->markTestSkipped('No repository has been configured in the "PRISMIC_REPOSITORY" environment variable');
        }

        $token = getenv('PRISMIC_TOKEN');
        if (! is_string($token) || empty($token)) {
            $this->markTestSkipped('No authentication token has been configured in the "PRISMIC_TOKEN" environment variable');
        }

        $this->client = new BaseClient(
            $token,
            $repository,
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findUriFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
    }

    /** @return array<string, string[]> */
    public function invalidRepositoryDataProvider(): array
    {
        return [
            'Invalid Repo + Invalid Token' => ['foo', 'foo'],
            'Valid Repo + Invalid Token' => [
                (string) getenv('PRISMIC_REPOSITORY'),
                'foo',
            ],
            /**
             * Oddly, it does not appear to matter if you provide a completely unknown repository
             * name. Only the token is required, therefore the following will succeed/fail depending on
             * which way you look at it 🤔
             */
            // 'Invalid Repo + Valid Token' => [
            //     uniqid('not_there_', false),
            //     (string) getenv('PRISMIC_TOKEN'),
            // ],
        ];
    }

    /** @dataProvider invalidRepositoryDataProvider */
    public function testThatAuthorisationCanFail(string $repo, string $token): void
    {
        $client = $this->client->withAlternativeRepository($repo, $token);
        $this->expectException(AuthenticationFailed::class);
        $client->getDefinition('foo');
    }

    private function initialiseAValidDefinition(): Definition
    {
        return Definition::new(
            uniqid('new_', false),
            'Example Definition',
            true,
            true,
            '{"Main":{"value": {"type":"Number","config":{"label":"Value","min":0,"max":10}}}}'
        );
    }

    public function testGetDefinitionWillThrownDefinitionNotFound(): void
    {
        $id = uniqid('missing_', false);
        $this->expectException(DefinitionNotFound::class);
        $this->expectExceptionMessage($id);
        $this->client->getDefinition($id);
    }

    public function testThatAllTypesCanBeRetrieved(): void
    {
        $types = $this->client->fetchAllDefinitions();
        $array = $types instanceof Traversable ? iterator_to_array($types) : $types;
        self::assertContainsOnlyInstancesOf(Definition::class, $array);
        self::assertGreaterThanOrEqual(1, count($array));
    }

    public function testThatANewDefinitionCanBeSaved(): Definition
    {
        $definition = $this->initialiseAValidDefinition();

        $this->client->createDefinition($definition);

        $fetched = $this->client->getDefinition($definition->id());

        self::assertEquals($definition->id(), $fetched->id());
        self::assertEquals($definition->label(), $fetched->label());
        self::assertEquals($definition->isRepeatable(), $fetched->isRepeatable());
        self::assertEquals($definition->isActive(), $fetched->isActive());

        return $fetched;
    }

    /** @depends testThatANewDefinitionCanBeSaved */
    public function testThatAttemptingToInsertAnExistingDefinitionIsExceptional(Definition $definition): void
    {
        $this->expectException(InsertFailed::class);
        $this->expectExceptionMessage($definition->id());
        $this->client->createDefinition($definition);
    }

    /** @depends testThatANewDefinitionCanBeSaved */
    public function testThatADefinitionCanBeDisabled(Definition $definition): Definition
    {
        $update = $definition->withActivationStatus(false);
        self::assertTrue($definition->isActive());
        self::assertFalse($update->isActive());
        $this->client->updateDefinition($update);

        $fetched = $this->client->getDefinition($definition->id());
        self::assertFalse($fetched->isActive());

        return $fetched;
    }

    /** @depends testThatADefinitionCanBeDisabled */
    public function testThatADefinitionCanBeDeleted(Definition $definition): void
    {
        $this->client->deleteDefinition($definition->id());
        $this->expectException(DefinitionNotFound::class);
        $this->client->getDefinition($definition->id());
    }

    /**
     * @depends testThatADefinitionCanBeDisabled
     * @depends testThatADefinitionCanBeDeleted
     */
    public function testThatYouCannotUpdateADefinitionThatDoesNotExist(Definition $definition): void
    {
        $this->expectException(UpdateFailed::class);
        $this->expectExceptionMessage($definition->id());
        $this->client->updateDefinition($definition);
    }

    public function testThatInvalidDocumentDefinitionWillCauseAnError(): void
    {
        $definition = Definition::new(
            uniqid('new_', false),
            'Invalid Definition',
            true,
            true,
            '{"Main":{"actually": "valid json, but invalid spec for Prismic"}}'
        );

        $this->expectException(InvalidDefinition::class);
        $this->client->saveDefinition($definition);
    }

    public function testThatCallingSaveWillInsertADefinition(): Definition
    {
        $definition = $this->initialiseAValidDefinition();
        $this->client->saveDefinition($definition);
        $retrieved = $this->client->getDefinition($definition->id());
        self::assertTrue($retrieved->id() === $definition->id());

        return $retrieved;
    }

    /** @depends testThatCallingSaveWillInsertADefinition */
    public function testThatCallingSaveWithAnExistingDefinitionWillIssueAnUpdate(Definition $definition): Definition
    {
        $changed = $definition->withNewLabel('New Label');
        $this->client->saveDefinition($changed);
        $retrieved = $this->client->getDefinition($changed->id());

        self::assertEquals('New Label', $retrieved->label());

        return $retrieved;
    }

    /** @depends testThatCallingSaveWillInsertADefinition */
    public function testThatUpdatingWithInvalidSpecWillCauseAnError(Definition $definition): void
    {
        $changed = $definition->withAlteredPayload('{"Main":{"actually": "valid json, but invalid spec for Prismic"}}');
        $this->expectException(InvalidDefinition::class);
        $this->client->saveDefinition($changed);
    }

    /**
     * @depends testThatCallingSaveWillInsertADefinition
     * @depends testThatUpdatingWithInvalidSpecWillCauseAnError
     */
    public function cleanUpTheDefinitionInTheRepo(Definition $definition): void
    {
        $this->expectNotToPerformAssertions();
        $this->client->deleteDefinition($definition->id());
    }
}
