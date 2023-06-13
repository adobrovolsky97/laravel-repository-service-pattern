<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CacheDrivers;

use Illuminate\Support\Facades\Cache;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;

/**
 * Class BaseCacheDriver
 */
abstract class BaseCacheDriver
{
    /**
     * Remember data
     *
     * @param array $keyData
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     * @throws RepositoryException
     */
    public function remember(array $keyData, int $ttl, callable $callback)
    {
        $this->validateKeyData($keyData);

        return Cache::tags($keyData['tags'])->remember($keyData['paramsKey'], $ttl, $callback);
    }

    /**
     * Put data
     *
     * @param array $keyData
     * @param int $ttl
     * @param mixed $data
     * @return void
     */
    public function put(array $keyData, $data, int $ttl): void
    {
        Cache::tags($keyData['tags'])->put($keyData['paramsKey'], $data, $ttl);
    }

    /**
     * Forget cache data
     *
     * @param array $keyData
     * @return void
     */
    public function forget(array $keyData): void
    {
        Cache::tags($keyData['tags'])->flush();
    }

    /**
     * @param array $keyData
     * @return void
     * @throws RepositoryException
     */
    protected function validateKeyData(array $keyData): void
    {
        if (!isset($keyData['tags']) || !isset($keyData['keyWithTag']) || !isset($keyData['paramsKey'])) {
            throw new RepositoryException('Cache key data is invalid');
        }
    }
}
