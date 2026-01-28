<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Services;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Service\ServiceException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseRepositoryInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Services\Contracts\BaseCrudServiceInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class BaseCrudService
 */
abstract class BaseCrudService implements BaseCrudServiceInterface
{
    /**
     * @var BaseRepositoryInterface
     */
    protected $repository;

    /**
     * BaseCrudService constructor.
     */
    public function __construct()
    {
        $this->repository = app($this->getRepositoryClass());
    }

    /**
     * Set with for repository querying
     *
     * @param array $with
     * @return BaseCrudServiceInterface
     */
    public function with(array $with): BaseCrudServiceInterface
    {
        $this->repository->with($with);

        return $this;
    }

    /**
     * Set withCount for repository querying
     *
     * @param array $withCount
     * @return BaseCrudServiceInterface
     */
    public function withCount(array $withCount): BaseCrudServiceInterface
    {
        $this->repository->withCount($withCount);

        return $this;
    }

    /**
     * Include soft deleted records to a query
     *
     * @return BaseCrudServiceInterface
     */
    public function withTrashed(): BaseCrudServiceInterface
    {
        $this->repository->withTrashed();

        return $this;
    }

    /**
     * Show only soft deleted records in a query
     *
     * @return BaseCrudServiceInterface
     */
    public function onlyTrashed(): BaseCrudServiceInterface
    {
        $this->repository->onlyTrashed();

        return $this;
    }

    /**
     * Exclude soft deleted records from a query
     *
     * @return BaseCrudServiceInterface
     */
    public function withoutTrashed(): BaseCrudServiceInterface
    {
        $this->repository->withoutTrashed();

        return $this;
    }

    /**
     * Lock model
     */
    public function lock(Model $model): Model
    {
        return $this->repository->lock($model);
    }

    /**
     * Lock models by ids
     *
     * @param array $ids
     * @return EloquentCollection
     */
    public function lockMany(array $ids): EloquentCollection
    {
        return $this->repository->lockMany($ids);
    }

    /**
     * Get filtered results
     *
     * @param array $search
     * @param int $pageSize
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAllPaginated(array $search = [], int $pageSize = 15): LengthAwarePaginator
    {
        return $this->repository->getAllPaginated($search, request()->get('page_size', $pageSize));
    }

    /**
     * Get all records as collection
     *
     * @param array $search
     * @return EloquentCollection
     */
    public function getAll(array $search = []): EloquentCollection
    {
        return $this->repository->getAll($search);
    }

    /**
     * Get all records as lazy collection (cursor)
     *
     * @param array $search
     * @return LazyCollection
     */
    public function getAllAsCursor(array $search = []): LazyCollection
    {
        return $this->repository->getAllCursor($search);
    }

    /**
     * Get results count
     *
     * @throws RepositoryException
     */
    public function count(array $search = []): int
    {
        return $this->repository->count($search);
    }

    /**
     * Find or fail the model
     *
     * @param $key
     * @param string|null $column
     * @return Model
     */
    public function findOrFail($key, string $column = null): Model
    {
        return $this->repository->findOrFail($key, $column);
    }

    /**
     * Find models by attributes
     *
     * @param array $attributes
     * @return Collection
     */
    public function find(array $attributes): Collection
    {
        return $this->repository->findMany($attributes);
    }

    /**
     * Create model
     *
     * @param array $data
     * @return Model|null
     * @throws ServiceException
     */
    public function create(array $data): ?Model
    {
        if (is_null($model = $this->repository->create($data))) {
            throw new ServiceException('Error while creating model');
        }

        return $model;
    }

    /**
     * Insert data into db
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data): bool
    {
        return $this->repository->insert($data);
    }

    /**
     * Create many models
     *
     * @param array $attributes
     * @return Collection
     * @throws ServiceException
     */
    public function createMany(array $attributes): Collection
    {
        if (empty($attributes)) {
            throw new ServiceException('Data is empty');
        }

        return DB::transaction(function () use ($attributes) {
            $models = collect();

            foreach ($attributes as $data) {
                $models->push($this->create($data));
            }

            return $models;
        });
    }

    /**
     * Update or create model
     *
     * @param array $attributes
     * @param array $data
     * @return Model|null
     * @throws ServiceException
     */
    public function updateOrCreate(array $attributes, array $data): ?Model
    {
        if (is_null($model = $this->repository->updateOrCreate($attributes, $data))) {
            throw new ServiceException('Error while creating or updating the model');
        }

        return $model;
    }

    /**
     * Update model
     *
     * @param $keyOrModel
     * @param array $data
     * @return Model|null
     */
    public function update($keyOrModel, array $data): ?Model
    {
        return $this->repository->update($keyOrModel, $data);
    }

    /**
     * Delete model
     *
     * @param $keyOrModel
     * @return bool
     * @throws Exception
     */
    public function delete($keyOrModel): bool
    {
        if (!$this->repository->delete($keyOrModel)) {
            throw new ServiceException('Error while deleting model');
        }

        return true;
    }

    /**
     * Delete many records
     *
     * @param array $keysOrModels
     * @return void
     */
    public function deleteMany(array $keysOrModels): void
    {
        DB::transaction(function () use ($keysOrModels) {
            foreach ($keysOrModels as $keyOrModel) {
                $this->delete($keyOrModel);
            }
        });
    }

    /**
     * Perform soft delete on model
     *
     * @param $keyOrModel
     * @return void
     */
    public function softDelete($keyOrModel): void
    {
        $this->repository->softDelete($keyOrModel);
    }

    /**
     * Restore model
     *
     * @param $keyOrModel
     * @return void
     * @throws RepositoryException
     */
    public function restore($keyOrModel): void
    {
        $this->repository->restore($keyOrModel);
    }

    /**
     * Should return RepositoryInterface::class
     *
     * @return string
     */
    abstract protected function getRepositoryClass(): string;
}
