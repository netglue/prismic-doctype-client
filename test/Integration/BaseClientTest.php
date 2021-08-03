<?php

declare(strict_types=1);

namespace Integration;

use Prismic\DocumentType\Definition;
use Prismic\DocumentType\Exception\AuthenticationFailed;
use Prismic\DocumentType\Exception\DefinitionNotFound;
use Prismic\DocumentType\Exception\InsertFailed;
use Prismic\DocumentType\Exception\UpdateFailed;
use Prismic\DocumentType\Test\Integration\HttpTestCase;
use Traversable;

use function count;
use function iterator_to_array;
use function uniqid;

/** @psalm-suppress MissingConstructor */
final class BaseClientTest extends HttpTestCase
{
    /** @return array<string, string[]> */
    public function invalidRepositoryDataProvider(): array
    {
        return [
            'Invalid Repo + Invalid Token' => ['foo', 'foo'],
        ];
    }

    /** @dataProvider invalidRepositoryDataProvider */
    public function testThatAuthorisationCanFail(string $repo, string $token): void
    {
        $client = $this->client->withAlternativeRepository($repo, $token);
        $this->expectException(AuthenticationFailed::class);
        $client->getDefinition('foo');
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
        $definition = Definition::new(
            uniqid('new_', false),
            'Example Definition',
            true,
            true,
            '{"Main":{"value": {"type":"Number","config":{"label":"Value","min":0,"max":10}}}}'
        );

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
}
