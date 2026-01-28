<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseRepositoryInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Traits\Crudable;
use Adobrovolsky97\LaravelRepositoryServicePattern\Traits\Queryable;
use InvalidArgumentException;

/**
 * Class BaseRepository
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    use Crudable, Queryable;

    /**
     * Resolve model by key or return the model instance back
     *
     * @param $keyOrModel
     * @return Model
     * @throws RepositoryException
     */
    public function resolveModel($keyOrModel): Model
    {
        if ($keyOrModel instanceof Model) {
            $modelClass = $this->getModelClass();
            if (!$keyOrModel instanceof $modelClass) {
                throw new RepositoryException("Model is not an entity of repository model class");
            }
            return $keyOrModel;
        }

        return $this->withTrashed()->findOrFail($keyOrModel);
    }

    /**
     * Lock models by ids
     *
     * @param array $ids
     * @return Collection
     */
    public function lockMany(array $ids): Collection
    {
        sort($ids);

        return app($this->getModelClass())
            ->newQuery()
            ->whereKey($ids)
            ->lockForUpdate()
            ->get();
    }

    /**
     * Lock model
     *
     * @param Model $model
     * @return Model
     */
    public function lock(Model $model): Model
    {
        $repositoryModel = app($this->getModelClass());

        if ($model->getTable() !== $repositoryModel->getTable()) {
            throw new InvalidArgumentException(
                "Repository accepts models of type {$repositoryModel->getTable()}, but " . $model->getTable() . " given."
            );
        }

        $freshModel = $model->newQuery()
            ->lockForUpdate()
            ->findOrFail($model->getKey());

        $model->setRawAttributes($freshModel->getAttributes(), true);
        $model->setRelations($freshModel->getRelations());
        $model->exists = $freshModel->exists;

        return $model;
    }

    /**
     * Check if model is instance of SoftDeletes trait
     *
     * @param Model|null $model
     * @return bool
     */
    protected function isInstanceOfSoftDeletes(Model $model = null): bool
    {
        $model = $model ?? app($this->getModelClass());

        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Check if model has soft delete column
     *
     * @param Model|null $model
     * @return bool
     */
    protected function isModelHasSoftDeleteColumn(Model $model = null): bool
    {
        $model = $model ?? app($this->getModelClass());

        return Schema::hasColumn($model->getTable(), $this->deletedAtColumnName);
    }

    /**
     * Get model class
     *
     * @return string
     */
    abstract protected function getModelClass(): string;
}
