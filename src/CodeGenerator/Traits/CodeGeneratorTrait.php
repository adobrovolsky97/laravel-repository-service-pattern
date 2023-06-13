<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits;

use Illuminate\Support\Str;

/**
 * Trait CodeGeneratorTrait
 */
trait CodeGeneratorTrait
{
    /**
     * Get ModelName From TableName
     *
     * @param string $tableName
     * @return string
     */
    protected function getEntityNameFromTableName(string $tableName): string
    {
        return ucfirst(Str::camel(Str::singular($tableName)));
    }
}
