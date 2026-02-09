<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts;

use Exception;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;

/**
 * Interface BaseRepositoryInterface
 */
interface BaseRepositoryInterface
{
    /**
     * Create model with data
     *
     * @param array $data
     * @return Model|null
     */
    public function create(array $data): ?Model;

    /**
     * Insert records
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data): bool;

    /**
     * Resolve model by key or return the model instance back
     *
     * @param $keyOrModel
     * @return Model
     * @throws RepositoryException
     */
    public function resolveModel($keyOrModel): Model;

    /**
     * Lock model
     *
     * @param Model $model
     * @return Model
     */
    public function lock(Model $model): Model;

    /**
     * Lock models by ids
     *
     * @param array $ids
     * @return Collection
     */
    public function lockMany(array $ids): Collection;

    /**
     * Update model
     *
     * @param Model|mixed $keyOrModel
     * @param array $data
     * @return Model|null
     */
    public function update($keyOrModel, array $data): ?Model;

    /**
     * Update or create model
     *
     * @param array $attributes
     * @param array $data
     * @return Model|null
     */
    public function updateOrCreate(array $attributes, array $data): ?Model;

    /**
     * Delete model
     *
     * @param Model|mixed $keyOrModel
     * @return bool
     * @throws Exception
     */
    public function delete($keyOrModel): bool;

    /**
     * Perform model soft delete
     *
     * @param $keyOrModel
     * @return void
     */
    public function softDelete($keyOrModel): void;

    /**
     * Restore soft deleted model
     *
     * @param $keyOrModel
     * @return void
     * @throws RepositoryException
     */
    public function restore($keyOrModel): void;

    /**
     * Find model by PK
     *
     * @param int|string $key
     * @return Model|null
     */
    public function find($key): ?Model;

    /**
     * Find or fail by primary key or custom column
     *
     * @param $value
     * @param string|null $column
     * @return Model
     */
    public function findOrFail($value, ?string $column = null): Model;

    /**
     * Get filtered collection
     *
     * @param array $search
     * @return Collection
     */
    public function getAll(array $search = []): Collection;

    /**
     * Get filtered collection as cursor output
     *
     * @param array $search
     * @return LazyCollection
     */
    public function getAllCursor(array $search = []): LazyCollection;

    /**
     * Get paginated data
     *
     * @param array $search
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $search = [], int $pageSize = 15): LengthAwarePaginator;

    /**
     * Get cursor paginated
     *
     * @param array $search
     * @param int $pageSize
     * @return CursorPaginator
     * @throws RepositoryException
     */
    public function getCursorPaginated(array $search = [], int $pageSize = 15): CursorPaginator;

    /**
     * Get results count
     *
     * @throws RepositoryException
     */
    public function count(array $search = []): int;

    /**
     * Find first model
     *
     * @param array $attributes
     * @return Model|null
     */
    public function findFirst(array $attributes): ?Model;

    /**
     * Find all models by params
     *
     * @param array $attributes
     * @return Collection
     */
    public function findMany(array $attributes): Collection;

    /**
     * Set with
     *
     * @param array $with
     * @return BaseRepositoryInterface
     */
    public function with(array $with): BaseRepositoryInterface;

    /**
     * Set with count
     *
     * @param array $withCount
     * @return BaseRepositoryInterface
     */
    public function withCount(array $withCount): BaseRepositoryInterface;

    /**
     * Include soft deleted records to a query
     *
     * @return BaseRepositoryInterface
     */
    public function withTrashed(): BaseRepositoryInterface;

    /**
     * Show only soft deleted records in a query
     *
     * @return BaseRepositoryInterface
     */
    public function onlyTrashed(): BaseRepositoryInterface;

    /**
     * Exclude soft deleted records from a query
     *
     * @return BaseRepositoryInterface
     */
    public function withoutTrashed(): BaseRepositoryInterface;
}
