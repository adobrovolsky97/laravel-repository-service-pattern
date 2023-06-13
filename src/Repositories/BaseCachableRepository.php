<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Adobrovolsky97\LaravelRepositoryServicePattern\CacheDrivers\TaggableCacheDriver;
use Adobrovolsky97\LaravelRepositoryServicePattern\CacheDrivers\BaseCacheDriver;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseCachableRepositoryInterface;

/**
 * Class BaseCachableRepository
 */
abstract class BaseCachableRepository extends BaseRepository implements BaseCachableRepositoryInterface
{
    /**
     * Keys
     *
     * @const
     */
    public const KEY_ALL = 'all';
    public const KEY_PAGINATED = 'paginated';
    public const KEY_CURSOR = 'cursor';

    /**
     * @var BaseCacheDriver
     */
    protected $cacheDriver = TaggableCacheDriver::class;

    /**
     * Cache ttl in minutes
     *
     * @var integer
     */
    protected $cacheTtl = 60;

    /**
     * @var string
     */
    protected $cacheAlias = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cacheDriver = app($this->cacheDriver);
    }

    /**
     * Get models collection
     *
     * @param array $search
     * @return Collection
     * @throws RepositoryException
     */
    public function getAll(array $search = []): Collection
    {
        return $this->cacheDriver->remember(
            $this->generateCacheKey(
                self::KEY_ALL,
                array_merge(
                    $search,
                    [
                        'with'                => $this->with,
                        'withCount'           => $this->withCount,
                        'softDeleteQueryMode' => $this->softDeleteQueryMode
                    ]
                )
            ),
            $this->getTtl(),
            function () use ($search) {
                return parent::getAll($search);
            }
        );
    }

    /**
     * Get paginated data
     *
     * @param array $search
     * @param int $pageSize
     * @return LengthAwarePaginator
     * @throws RepositoryException
     */
    public function getAllPaginated(array $search = [], int $pageSize = 15): LengthAwarePaginator
    {
        return $this->cacheDriver->remember(
            $this->generateCacheKey(
                self::KEY_PAGINATED,
                array_merge(
                    $search,
                    [
                        'with'                => $this->with,
                        'withCount'           => $this->withCount,
                        'softDeleteQueryMode' => $this->softDeleteQueryMode
                    ]
                )
            ),
            $this->getTtl(),
            function () use ($search) {
                return parent::getAllPaginated($search);
            }
        );
    }

    /**
     * Get all as cursor
     *
     * @param array $search
     * @return LazyCollection
     * @throws RepositoryException
     */
    public function getAllCursor(array $search = []): LazyCollection
    {
        return $this->cacheDriver->remember(
            $this->generateCacheKey(
                self::KEY_CURSOR,
                array_merge(
                    $search,
                    [
                        'with'                => $this->with,
                        'withCount'           => $this->withCount,
                        'softDeleteQueryMode' => $this->softDeleteQueryMode
                    ]
                )
            ),
            $this->getTtl(),
            function () use ($search) {
                return parent::getAllCursor($search);
            }
        );
    }

    /**
     * Find first model
     *
     * @param array $attributes
     * @return Model|null
     * @throws RepositoryException
     */
    public function findFirst(array $attributes): ?Model
    {
        $model = parent::findFirst($attributes);

        $this->cacheModel($model);

        return $model;
    }

    /**
     * Find model by PK
     *
     * @param $key
     * @return Model|null
     * @throws RepositoryException
     */
    public function find($key): ?Model
    {
        return $this->cacheDriver->remember(
            $this->generateCacheKeyForModelInstance($key),
            $this->getTtl(),
            function () use ($key) {
                return parent::find($key);
            }
        );
    }

    /**
     * Find or fail by primary key or custom column
     *
     * @param $value
     * @param string|null $column
     * @return Model
     * @throws RepositoryException
     */
    public function findOrFail($value, ?string $column = null): Model
    {
        $model = parent::findOrFail($value, $column);
        $this->cacheModel($model);

        return $model;
    }

    /**
     * Insert data into DB
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data): bool
    {
        $this->flushListsCache();

        return parent::insert($data);
    }

    /**
     * Create model with data
     *
     * @param array $data
     * @return Model|null
     * @throws RepositoryException
     */
    public function create(array $data): ?Model
    {
        $model = parent::create($data);

        $this->cacheModel($model);
        $this->flushListsCache();

        return $model;
    }

    /**
     * Update model
     *
     * @param Model|mixed $keyOrModel
     * @param array $data
     * @return Model|null
     * @throws RepositoryException
     */
    public function update($keyOrModel, array $data): ?Model
    {
        $model = parent::update($keyOrModel, $data);

        $this->cacheModel($model);
        $this->flushListsCache();

        return $model;
    }

    /**
     * Update or create model
     *
     * @param array $attributes
     * @param array $data
     * @return Model|null
     * @throws RepositoryException
     */
    public function updateOrCreate(array $attributes, array $data): ?Model
    {
        $model = parent::updateOrCreate($attributes, $data);

        $this->cacheModel($model);
        $this->flushListsCache();

        return $model;
    }

    /**
     * Delete model
     *
     * @param Model|mixed $keyOrModel
     * @return bool
     * @throws Exception
     */
    public function delete($keyOrModel): bool
    {
        $model = $this->resolveModel($keyOrModel);

        $this->cacheDriver->forget($this->generateCacheKeyForModelInstance($model->getKey()));
        $this->flushListsCache();

        return parent::delete($model);
    }

    /**
     * Soft delete model
     *
     * @param $keyOrModel
     * @return void
     * @throws RepositoryException
     */
    public function softDelete($keyOrModel): void
    {
        $model = $this->resolveModel($keyOrModel);
        parent::softDelete($model);

        $this->flushListsCache();
        $this->cacheDriver->forget($this->generateCacheKeyForModelInstance($model->getKey()));
    }

    /**
     * Soft delete model
     *
     * @param $keyOrModel
     * @return void
     * @throws RepositoryException
     */
    public function restore($keyOrModel): void
    {
        parent::restore($keyOrModel);

        $this->cacheModel($keyOrModel);
        $this->flushListsCache();
    }

    /**
     * Get cache key from query params
     *
     * @param string $keyName
     * @param array $params
     * @return array
     */
    protected function generateCacheKey(string $keyName, array $params = []): array
    {
        ksort($params);

        return [
            'tags'       => [$this->cacheAlias . '.' . $keyName],
            'keyWithTag' => ($this->cacheAlias ?? Str::camel(last(explode('\\', $this->getModelClass())))) . '.' . $keyName . (!empty($params) ? '.' . md5(json_encode($params)) : ''),
            'paramsKey'  => (!empty($params) ? md5(json_encode($params)) : '')
        ];
    }

    /**
     * Clear get all & paginated cache keys
     *
     * @return void
     */
    protected function flushListsCache(): void
    {
        $this->cacheDriver->forget($this->generateCacheKey(self::KEY_ALL));
        $this->cacheDriver->forget($this->generateCacheKey(self::KEY_PAGINATED));
        $this->cacheDriver->forget($this->generateCacheKey(self::KEY_CURSOR));
    }

    /**
     * Generate cache key for single model
     *
     * @param $primaryKey
     * @return array
     */
    protected function generateCacheKeyForModelInstance($primaryKey): array
    {
        $primaryKey = implode('.', Arr::wrap($primaryKey));
        return [
            'tags'       => ["$this->cacheAlias.$primaryKey"],
            'keyWithTag' => "$this->cacheAlias.$primaryKey",
            'paramsKey'  => $primaryKey
        ];
    }

    /**
     * Cache model
     *
     * @param null|string|integer|Model $keyOrModel
     * @return void
     * @throws RepositoryException
     */
    protected function cacheModel($keyOrModel = null): void
    {
        if (is_null($keyOrModel)) {
            return;
        }

        $model = $this->resolveModel($keyOrModel);

        $this->cacheDriver->put($this->generateCacheKeyForModelInstance($model->getKey()), $model, $this->getTtl());
    }

    /**
     * Get cache ttl in seconds
     *
     * @return int
     */
    protected function getTtl(): int
    {
        return $this->cacheTtl * 60;
    }
}
