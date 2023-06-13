<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CacheDrivers;

use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;

/**
 * Class TaggableCacheDriver
 */
class TaggableCacheDriver extends BaseCacheDriver
{
    /**
     *
     * @throws RepositoryException
     */
    public function __construct()
    {
        if (in_array(config('cache.default'), ['file', 'dynamodb', 'database'])) {
            throw new RepositoryException('Caching is not supported for your current cache driver');
        }
    }
}
