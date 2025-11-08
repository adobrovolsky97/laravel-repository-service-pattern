<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CacheDrivers;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\LazyCollection;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;

abstract class BaseCacheDriver
{
    protected const NULL_SENTINEL = '__NULL__';

    /**
     * Remember data safely
     * @throws RepositoryException
     */
    public function remember(array $keyData, int $ttl, callable $callback)
    {
        $this->validateKeyData($keyData);

        // Basic lock to prevent double-query under high concurrency
        $lockName = 'lock:' . $keyData['keyWithTag'];

        return Cache::lock($lockName, 5)->block(3, function () use ($keyData, $ttl, $callback) {
            return Cache::tags($keyData['tags'])->remember($keyData['paramsKey'], $ttl, function () use ($callback) {
                $value = $callback();

                // Skip caching unserializable objects
                if ($this->isUnserializable($value)) {
                    return $value;
                }

                // Store sentinel for nulls
                return $value ?? self::NULL_SENTINEL;
            });
        });
    }

    /**
     * Put data with safety checks
     */
    public function put(array $keyData, $data, int $ttl): void
    {
        if ($this->isUnserializable($data)) {
            return;
        }

        $value = $data ?? self::NULL_SENTINEL;

        Cache::tags($keyData['tags'])->put($keyData['paramsKey'], $value, $ttl);
    }

    /**
     * Forget cache data (flush tag group)
     */
    public function forget(array $keyData): void
    {
        if (!empty($keyData['tags'])) {
            Cache::tags($keyData['tags'])->flush();
        }
    }

    /**
     * Bulk put (pipeline optimization for mass caching)
     */
    public function putMany(array $items, int $ttl): void
    {
        Redis::pipeline(function ($pipe) use ($items, $ttl) {
            foreach ($items as $keyData => $data) {
                if ($this->isUnserializable($data)) {
                    continue;
                }
                $pipe->setex($keyData, $ttl, serialize($data ?? self::NULL_SENTINEL));
            }
        });
    }

    /**
     * Detect unserializable / volatile objects
     */
    protected function isUnserializable($value): bool
    {
        return $value instanceof Closure
            || $value instanceof EloquentBuilder
            || $value instanceof QueryBuilder
            || $value instanceof LazyCollection
            || is_resource($value);
    }

    /**
     * Validate cache key structure
     * @throws RepositoryException
     */
    protected function validateKeyData(array $keyData): void
    {
        if (!isset($keyData['tags'], $keyData['keyWithTag'], $keyData['paramsKey'])) {
            throw new RepositoryException('Cache key data is invalid');
        }
    }

    /**
     * Retrieve and normalize sentinel values
     */
    public function get(array $keyData)
    {
        $value = Cache::tags($keyData['tags'])->get($keyData['paramsKey']);

        return $value === self::NULL_SENTINEL ? null : $value;
    }
}
