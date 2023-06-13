<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseModel
 */
class BaseModel extends Model
{
    /**
     * @return string
     */
    public static function getTableName():string
    {
        return (new static())->getTable();
    }
}
