<?php

declare(strict_types=1);

namespace Prismic\DocumentType;

use Countable;
use Prismic\DocumentType\Exception\DefinitionNotFound;
use Prismic\DocumentType\Exception\Exception;

interface SharedSliceManagementClient
{
    /**
     * Return a list of the Shared Slices found in the remote repo
     *
     * @return iterable<string, SharedSlice>&Countable
     */
    public function fetchAllSharedSlices(): iterable;

    /**
     * Insert or update a shared slice
     *
     * @throws Exception
     */
    public function saveSharedSlice(SharedSlice $slice): void;

    /**
     * Fetch a shared slice
     *
     * @param non-empty-string $id
     *
     * @throws DefinitionNotFound if there is no such type with the given id.
     * @throws Exception if any errors occur communicating with the remote API.
     */
    public function getSharedSlice(string $id): SharedSlice;

    /**
     * Deletes the shared slice with the given identifier
     *
     * @param non-empty-string $id
     *
     * @throws Exception if any errors occur communicating with the remote API.
     */
    public function deleteSharedSlice(string $id): void;
}
