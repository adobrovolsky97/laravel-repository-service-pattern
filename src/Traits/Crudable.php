<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;

/**
 * Trait Crudable
 * @mixin
 */
trait Crudable
{
    /**
     * Create model with data
     *
     * @param array $data
     * @return Model|null
     */
    public function create(array $data): ?Model
    {
        /** @var Model $model */
        $model = resolve($this->getModelClass());

        if (!$model->fill($data)->save()) {
            return null;
        }

        if (!is_array($model->getKey())) {
            return $model->refresh();
        }

        return $model;
    }

    /**
     * Insert records
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data): bool
    {
        return $this->getQuery()->insert($data);
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

        if (!$model->update($data)) {
            return null;
        }

        if (!is_array($model->getKey())) {
            return $model->refresh();
        }

        return $model;
    }

    /**
     * Update or create model
     *
     * @param array $attributes
     * @param array $data
     * @return Model|null
     */
    public function updateOrCreate(array $attributes, array $data): ?Model
    {
        return $this->getQuery()->updateOrCreate($attributes, $data);
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

        if ($this->isInstanceOfSoftDeletes($model)) {
            return !is_null($model->forceDelete());
        }

        return !is_null($model->delete());
    }

    /**
     * Perform model soft delete
     *
     * @param $keyOrModel
     * @return void
     * @throws RepositoryException
     * @throws Exception
     */
    public function softDelete($keyOrModel): void
    {
        $model = $this->resolveModel($keyOrModel);

        if ($this->isInstanceOfSoftDeletes($model)) {
            $model->delete();
            return;
        }

        if ($this->isModelHasSoftDeleteColumn($model)) {
            $this->update($model, [$this->deletedAtColumnName => now()]);
            return;
        }

        throw new RepositoryException('Model does not support soft deletes.');
    }

    /**
     * Restore soft deleted model
     *
     * @param $keyOrModel
     * @return void
     * @throws RepositoryException
     */
    public function restore($keyOrModel): void
    {
        /** @var Model|SoftDeletes $model */
        $model = $this->resolveModel($keyOrModel);

        if ($model->{$this->deletedAtColumnName} === null) {
            throw new RepositoryException('Model is not deleted so could not be restored');
        }

        if ($this->isInstanceOfSoftDeletes($model)) {
            $model->restore();
            return;
        }

        if ($this->isModelHasSoftDeleteColumn($model)) {
            $this->update($model, [$this->deletedAtColumnName => null]);
            return;
        }

        throw new RepositoryException('Model does not support soft deletes.');
    }
}
