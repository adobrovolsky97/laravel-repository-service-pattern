<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\DatabaseManager;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\DbManagerServiceInterface;

/**
 * Class DbManagerService
 */
class DbManagerService implements DbManagerServiceInterface
{
    /**
     * Relations
     *
     * @const
     */
    const RELATION_BELONGS_TO = 'belongsTo';
    const RELATION_BELONGS_TO_MANY = 'belongsToMany';
    const RELATION_HAS_ONE = 'hasOne';
    const RELATION_HAS_MANY = 'hasMany';

    /**
     * @var DatabaseManager
     */
    protected $dbManager;

    /**
     * @var AbstractSchemaManager
     */
    protected $schemaManager;

    /**
     * @var string
     */
    protected $tablePrefix;

    /**
     * Mapping DB columns to the model properties
     *
     * @var string[]
     */
    protected $types = [
        'array'        => 'array',
        'simple_array' => 'array',
        'json_array'   => 'string',
        'bigint'       => 'integer',
        'boolean'      => 'boolean',
        'datetime'     => 'Carbon',
        'datetimetz'   => 'string',
        'date'         => 'Carbon',
        'time'         => 'string',
        'decimal'      => 'float',
        'integer'      => 'integer',
        'object'       => 'object',
        'smallint'     => 'integer',
        'string'       => 'string',
        'text'         => 'string',
        'binary'       => 'string',
        'blob'         => 'string',
        'float'        => 'float',
        'guid'         => 'string',
    ];

    /**
     * @param DatabaseManager $dbManager
     */
    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
        $this->schemaManager = $this->dbManager->connection(config('database.default'))->getDoctrineSchemaManager();
        $this->tablePrefix = $this->dbManager->connection(config('database.default'))->getTablePrefix();
    }

    /**
     * Get table PK
     *
     * @param string $tableName
     * @return mixed
     */
    public function getPrimaryKey(string $tableName)
    {
        $tableDetails = $this->schemaManager->listTableDetails($this->tablePrefix . $tableName);

        $primaryKey = $tableDetails->getPrimaryKey() ? $tableDetails->getPrimaryKey()->getColumns() : [];

        if (count($primaryKey) === 1) {
            $primaryKey = $primaryKey[0];
        }

        return $primaryKey;
    }

    /**
     * @param string $tableName
     * @return array
     */
    public function getTableProperties(string $tableName): array
    {
        $properties = [];
        $tableDetails = $this->schemaManager->listTableDetails($this->tablePrefix . $tableName);

        foreach ($tableDetails->getColumns() as $column) {

            $type = $this->resolveType($column->getType()->getName());
            $properties[$column->getName()] = $type;
        }

        return $properties;
    }

    /**
     * Get table columns
     *
     * @param string $tableName
     * @return array
     */
    public function getTableColumns(string $tableName): array
    {
        return $this->schemaManager
            ->listTableDetails($this->tablePrefix . $tableName)
            ->getColumns();
    }

    /**
     * Get table relations and relation to the table
     *
     * @param string $tableName
     * @return array
     */
    public function getRelations(string $tableName): array
    {
        $relations = [];

        foreach ($this->schemaManager->listTableForeignKeys($this->tablePrefix . $tableName) as $tableForeignKey) {
            $tableForeignColumns = $tableForeignKey->getForeignColumns();
            if (count($tableForeignColumns) !== 1) {
                continue;
            }

            $relations[] = [
                'type'         => self::RELATION_BELONGS_TO,
                'relatedTable' => $tableForeignKey->getForeignTableName(),
                'foreignKey'   => $tableForeignKey->getLocalColumns()[0],
                'localKey'     => $tableForeignColumns[0]
            ];
        }

        foreach ($this->schemaManager->listTables() as $table) {
            if ($table->getName() === $this->tablePrefix . $tableName) {
                continue;
            }

            $foreignKeys = $table->getForeignKeys();

            foreach ($foreignKeys as $name => $foreignKey) {

                if ($foreignKey->getForeignTableName() !== $this->tablePrefix . $tableName) {
                    continue;
                }

                $localColumns = $foreignKey->getLocalColumns();

                if (count($localColumns) !== 1) {
                    continue;
                }

                if (count($foreignKeys) === 2 && count($table->getColumns()) === 2) {

                    $keys = array_keys($foreignKeys);
                    $key = array_search($name, $keys) === 0 ? 1 : 0;
                    $secondForeignKey = $foreignKeys[$keys[$key]];
                    $secondForeignTable = $this->removePrefix($secondForeignKey->getForeignTableName());

                    $relations[] = [
                        'type'         => self::RELATION_BELONGS_TO_MANY,
                        'relatedTable' => $secondForeignTable,
                        'joinTable'    => $this->removePrefix($table->getName()),
                        'foreignKey'   => $localColumns[0],
                        'localKey'     => $secondForeignKey->getLocalColumns()[0]
                    ];

                    break;
                }

                $relations[] = [
                    'type'         => $this->isColumnUnique($table, $localColumns[0])
                        ? self::RELATION_HAS_ONE
                        : self::RELATION_HAS_MANY,
                    'relatedTable' => $this->removePrefix($foreignKey->getLocalTableName()),
                    'foreignKey'   => $localColumns[0],
                    'localKey'     => $foreignKey->getForeignColumns()[0]
                ];
            }
        }

        return $relations;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function resolveType($type): string
    {
        return $this->types[$type] ?? 'mixed';
    }

    /**
     * @param Table $table
     * @param string $column
     * @return bool
     */
    protected function isColumnUnique(Table $table, string $column): bool
    {
        foreach ($table->getIndexes() as $index) {
            $indexColumns = $index->getColumns();
            if (count($indexColumns) !== 1) {
                continue;
            }
            $indexColumn = $indexColumns[0];
            if ($indexColumn === $column && $index->isUnique()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove prefix from table name
     *
     * @param string $tableName
     * @return string
     */
    protected function removePrefix(string $tableName): string
    {
        $prefix = preg_quote($this->tablePrefix, '/');

        return preg_replace("/^$prefix/", '', $tableName);
    }
}
