<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts;

/**
 * Interface DbManagerServiceInterface
 */
interface DbManagerServiceInterface
{
    /**
     * Get table properties
     *
     * @param string $tableName
     * @return array
     */
    public function getTableProperties(string $tableName): array;

    /**
     * Get table columns
     *
     * @param string $tableName
     * @return array
     */
    public function getTableColumns(string $tableName): array;

    /**
     * Get table PK
     *
     * @param string $tableName
     * @return mixed
     */
    public function getPrimaryKey(string $tableName);

    /**
     * Get table relations and relation to the table
     *
     * @param string $tableName
     * @return array
     */
    public function getRelations(string $tableName): array;
}
