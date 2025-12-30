<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Adobrovolsky97\LaravelRepositoryServicePattern\CacheDrivers\TaggableCacheDriver;
use Adobrovolsky97\LaravelRepositoryServicePattern\CacheDrivers\BaseCacheDriver;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseCachableRepositoryInterface;

/**
 * Class BaseCacheableRepository
 */
abstract class BaseCacheableRepository extends BaseRepository implements BaseCachableRepositoryInterface
{
    /**
     * Keys
     *
     * @const
     */
    public const KEY_ALL = 'all';
    public const KEY_PAGINATED = 'paginated';

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
        $page = (int) ($search['page'] ?? request()?->input('page', 1));

        $cachedData = $this->cacheDriver->remember(

            $this->generateCacheKey(
                self::KEY_PAGINATED,
                array_merge(
                    $search,
                    [
                        'with'                => $this->with,
                        'withCount'           => $this->withCount,
                        'softDeleteQueryMode' => $this->softDeleteQueryMode,
                        'pageSize'            => $pageSize,
                        'page'                => $page,
                    ]
                )
            ),
            $this->getTtl(),
            function () use ($search, $pageSize) {
                $paginator = parent::getAllPaginated($search, $pageSize);

                return [
                    'items' => $paginator->getCollection(),
                    'total' => $paginator->total(),
                ];
            }
        );

        return new Paginator(
            $cachedData['items'],
            $cachedData['total'],
            $pageSize,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
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
        return parent::getAllCursor($search);
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
        $result = $this->cacheDriver->remember(
            $this->generateCacheKeyForModelInstance($key),
            $this->getTtl(),
            function () use ($key) {
                return parent::find($key);
            }
        );

        if ($result instanceof Model) {
            return $result;
        }

        return null;
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
        // Searching by PK
        if ($column === null) {
            $model = $this->find($value);

            if (!empty($model)) {
                return $model;
            }

            throw (new ModelNotFoundException())->setModel($this->getModelClass(), $value);
        }

        $cacheKeyData = $this->generateCacheKeyForModelInstance("map.$column.$value");

        // Caching ID
        $id = $this->cacheDriver->remember(
            $cacheKeyData,
            $this->getTtl(),
            function () use ($value, $column) {
                return parent::findOrFail($value, $column)->getKey();
            }
        );

        // Now we have an ID and use $this->find to get the model from cache by ID
        $model = $this->find($id);

        // Edge case
        if (!$model) {
            $this->cacheDriver->forget($cacheKeyData);

            throw (new ModelNotFoundException())->setModel($this->getModelClass(), $value);
        }

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
        $this->flushListsCaches();

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
        $this->flushListsCaches();

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
        $model = $this->resolveModel($keyOrModel);

        $keysToForget = [];

        foreach ($this->getUniqueKeys() as $field) {
            if (array_key_exists($field, $data) && !empty($model->{$field}) && $data[$field] !== $model->{$field}) {
                $keysToForget[] = "map.$field." . $model->{$field};
            }
        }

        $updatedModel = parent::update($model, $data);

        $this->cacheModel($updatedModel);
        $this->flushListsCaches();

        foreach ($keysToForget as $keyToRemove) {
            $this->cacheDriver->forget($this->generateCacheKeyForModelInstance($keyToRemove));
        }

        return $updatedModel;
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
        $this->flushListsCaches();

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
        $this->flushListsCaches();

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

        $this->flushListsCaches();
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
        $this->flushListsCaches();
    }

    /**
     * Clear get all & paginated cache keys
     *
     * @return void
     */
    public function flushListsCaches(): void
    {
        $this->cacheDriver->forget($this->generateCacheKey(self::KEY_ALL));
        $this->cacheDriver->forget($this->generateCacheKey(self::KEY_PAGINATED));
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
        $alias = $this->cacheAlias ?? Str::camel(class_basename($this->getModelClass()));
        $hash  = !empty($params) ? md5(serialize($params)) : '';

        return [
            'tags'       => ["$alias.$keyName"],
            'keyWithTag' => "$alias.$keyName" . ($hash ? ".$hash" : ''),
            'paramsKey'  => $hash,
        ];
    }

    /**
     * Generate cache key for single model
     *
     * @param $primaryKey
     * @return array
     */
    protected function generateCacheKeyForModelInstance($primaryKey): array
    {
        if (is_array($primaryKey)) {
            ksort($primaryKey);
            $primaryKey = implode('_', array_map(fn($k, $v) => "$k-$v", array_keys($primaryKey), $primaryKey));
        } else {
            $primaryKey = (string) $primaryKey;
        }

        $alias = $this->cacheAlias ?? Str::camel(class_basename($this->getModelClass()));

        return [
            'tags'       => ["$alias.model"],
            'keyWithTag' => "$alias.$primaryKey",
            'paramsKey'  => "$alias.$primaryKey",
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
     * Get unique keys for cache
     *
     * @return array
     */
    protected function getUniqueKeys(): array
    {
        return [];
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
