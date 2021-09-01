<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use Countable;
use Prismic\DocumentType\Exception\DefinitionNotFound;
use Prismic\DocumentType\Exception\Exception;

interface Client
{
    /**
     * Retrieve a document type definition by its identifier
     *
     * @throws DefinitionNotFound if there is no such type with the given id.
     * @throws Exception if any errors occur communicating with the remote API.
     */
    public function getDefinition(string $id): Definition;

    /**
     * Update or create the given document type definition
     *
     * @throws Exception if any errors occur communicating with the remote API.
     */
    public function saveDefinition(Definition $definition): void;

    /**
     * Fetch all definitions known to the repository
     *
     * @return iterable<string, Definition>&Countable
     *
     * @throws Exception if any errors occur communicating with the remote API.
     */
    public function fetchAllDefinitions(): iterable;

    /**
     * Deletes the definition with the given identifier
     *
     * @throws Exception if any errors occur communicating with the remote API.
     */
    public function deleteDefinition(string $id): void;
}
