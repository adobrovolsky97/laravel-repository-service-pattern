<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Traits;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Models\BaseModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\BaseRepository;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Trait Queryable
 *
 * @property Model|BaseModel $model
 * @mixin BaseRepository
 */
trait Queryable
{
    /**
     * Handle soft deletes
     *
     * @var int
     */
    private static $EXCLUDE_DELETED = 1;
    private static $INCLUDE_DELETED = 2;
    private static $ONLY_DELETED = 3;

    /**
     * @var string
     */
    protected $defaultOrderBy;

    /**
     * @var string
     */
    protected $deletedAtColumnName = 'deleted_at';

    /**
     * Array of "with" relations
     *
     * @var array
     */
    protected $with = [];

    /**
     * Array of "withCount" relations
     *
     * @var array
     */
    protected $withCount = [];

    /**
     * Array of "global scopes" to exclude
     *
     * @var array
     */
    protected $withoutGlobalScopes = [];

    /**
     * Soft query delete handle
     *
     * @var int
     */
    protected $softDeleteQueryMode;

    /**
     * Find model by PK
     *
     * @param $key
     * @return Model|null
     */
    public function find($key): ?Model
    {
        return $this->getQuery()->whereKey($key)->first();
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
        if (is_null($column)) {
            return is_array($value)
                ? $this->findMany([$value])->firstOrFail()
                : $this->getQuery()->findOrFail($value);
        }

        return $this->getQuery()->where($column, $value)->firstOrFail();
    }

    /**
     * Find all models by params
     *
     * @param array $attributes
     * @return Collection
     * @throws RepositoryException
     */
    public function findMany(array $attributes): Collection
    {
        return $this->applyFilterConditions($attributes)->get();
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
        return $this->findMany($attributes)->first();
    }

    /**
     * Get filtered collection
     *
     * @param array $search
     * @return Collection
     * @throws RepositoryException
     */
    public function getAll(array $search = []): Collection
    {
        return $this->applyFilters($search)->get();
    }

    /**
     * Get filtered collection as cursor output
     *
     * @param array $search
     * @return LazyCollection
     * @throws RepositoryException
     */
    public function getAllCursor(array $search = []): LazyCollection
    {
        return $this->applyFilters($search)->cursor();
    }

    /**
     * Get results count
     *
     * @throws RepositoryException
     */
    public function count(array $search = []): int
    {
        return $this->applyFilters($search)->count();
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
        return $this->applyFilters($search)->paginate($pageSize);
    }

    /**
     * Set with
     *
     * @param array $with
     * @return BaseRepositoryInterface
     */
    public function with(array $with): BaseRepositoryInterface
    {
        $this->with = $with;

        return $this;
    }

    /**
     * Set global scopes to include
     *
     * @param array $withoutGlobalScopes
     * @return BaseRepositoryInterface
     */
    public function withoutGlobalScopes(array $withoutGlobalScopes): BaseRepositoryInterface
    {
        $this->withoutGlobalScopes = $withoutGlobalScopes;

        return $this;
    }

    /**
     * Set with count
     *
     * @param array $withCount
     * @return BaseRepositoryInterface
     */
    public function withCount(array $withCount): BaseRepositoryInterface
    {
        $this->withCount = $withCount;

        return $this;
    }

    /**
     * Include soft deleted records to a query
     *
     * @return BaseRepositoryInterface
     */
    public function withTrashed(): BaseRepositoryInterface
    {
        $this->softDeleteQueryMode = self::$INCLUDE_DELETED;

        return $this;
    }

    /**
     * Show only soft deleted records in a query
     *
     * @return BaseRepositoryInterface
     */
    public function onlyTrashed(): BaseRepositoryInterface
    {
        $this->softDeleteQueryMode = self::$ONLY_DELETED;

        return $this;
    }

    /**
     * Exclude soft deleted records from a query
     *
     * @return BaseRepositoryInterface
     */
    public function withoutTrashed(): BaseRepositoryInterface
    {
        $this->softDeleteQueryMode = self::$EXCLUDE_DELETED;

        return $this;
    }

    /**
     * The purpose of this function is to apply filtering to the model by overriding this function inside child repository
     *
     * @param array $searchParams
     * @return Builder
     * @throws RepositoryException
     */
    protected function applyFilters(array $searchParams = []): Builder
    {
        return $this
            ->applyFilterConditions($searchParams)
            ->when(
                !is_array(($orderByField = $this->defaultOrderBy ?? app($this->getModelClass())->getKeyName())),
                function (Builder $query) use ($orderByField) {
                    $query->orderBy($orderByField, 'desc');
                });
    }

    /**
     * Apply filter conditions
     *
     * @param array $conditions
     * @return Builder
     * @throws RepositoryException
     */
    protected function applyFilterConditions(array $conditions): Builder
    {
        $query = $this->getQuery();

        if (empty($conditions) || !Schema::hasColumn($query->getModel()->getTable(), key($conditions))) {
            return $query;
        }

        $conditions = $this->parseConditions($conditions);

        foreach ($conditions as $data) {

            list ($field, $operator, $val) = $data;

            $operator = preg_replace('/\s\s+/', ' ', trim($operator));

            $exploded = explode(' ', $operator);
            $condition = trim($exploded[0]);
            $operator = trim($exploded[1] ?? '=');

            switch (strtoupper($condition)) {
                case 'NULL':
                    $query->whereNull($field);
                    break;
                case 'NOT_NULL':
                    $query->whereNotNull($field);
                    break;
                case 'IN':
                    $this->validateArrayData($val);
                    $query->whereIn($field, $val);
                    break;
                case 'NOT_IN':
                    $this->validateArrayData($val);
                    $query->whereNotIn($field, $val);
                    break;
                case 'DATE':
                    $query->whereDate($field, $operator, $val);
                    break;
                case 'DAY':
                    $query->whereDay($field, $operator, $val);
                    break;
                case 'MONTH':
                    $query->whereMonth($field, $operator, $val);
                    break;
                case 'YEAR':
                    $query->whereYear($field, $operator, $val);
                    break;
                case 'HAS':
                    $this->validateClosureFunction($val);
                    $query->whereHas($field, $val);
                    break;
                case 'DOESNT_HAVE':
                    $this->validateClosureFunction($val);
                    $query->whereDoesntHave($field, $val);
                    break;
                case 'HAS_MORPH':
                    $this->validateClosureFunction($val);
                    $query->whereHasMorph($field, $val);
                    break;
                case 'DOESNT_HAVE_MORPH':
                    $this->validateClosureFunction($val);
                    $query->whereDoesntHaveMorph($field, $val);
                    break;
                case 'BETWEEN':
                    $this->validateArrayData($val);
                    $query->whereBetween($field, $val);
                    break;
                case 'BETWEEN_COLUMNS':
                    $this->validateArrayData($val);
                    $query->whereBetweenColumns($field, $val);
                    break;
                case 'NOT_BETWEEN':
                    $this->validateArrayData($val);
                    $query->whereNotBetween($field, $val);
                    break;
                case 'NOT_BETWEEN_COLUMNS':
                    $this->validateArrayData($val);
                    $query->whereNotBetweenColumns($field, $val);
                    break;
                default:
                    $query->where($field, $condition, $val);
            }
        }

        return $query;
    }

    /**
     * @return Builder
     */
    protected function getQuery(): Builder
    {
        /** @var Model $model */
        $model = app($this->getModelClass());

        $query = $this->isInstanceOfSoftDeletes($model)
            ? $model->newQueryWithoutScope(SoftDeletingScope::class)
            : $model->query();

        return $query
            ->when(!empty($this->withoutGlobalScopes), function (Builder $query) {
                $query->withoutGlobalScopes($this->withoutGlobalScopes);
            })
            ->when(!empty($this->with), function (Builder $query) {
                $query->with($this->with);
            })
            ->when(!empty($this->withCount), function (Builder $query) {
                $query->withCount($this->withCount);
            })
            ->when(
                $this->isInstanceOfSoftDeletes($model) || $this->isModelHasSoftDeleteColumn($model),
                function (Builder $query) {
                    $mode = $this->softDeleteQueryMode ?? self::$EXCLUDE_DELETED;

                    $query
                        ->when($mode === self::$EXCLUDE_DELETED, function (Builder $query) {
                            $query->whereNull($this->deletedAtColumnName);
                        })
                        ->when($mode === self::$INCLUDE_DELETED, function (Builder $query) {
                            $query->where(function (Builder $query) {
                                $query
                                    ->whereNull($this->deletedAtColumnName)
                                    ->orWhereNotNull($this->deletedAtColumnName);
                            });
                        })
                        ->when($mode === self::$ONLY_DELETED, function (Builder $query) {
                            $query->whereNotNull($this->deletedAtColumnName);
                        });
                });
    }

    /**
     * Get condition data for search
     *
     * @param array $conditions
     * @return array
     */
    private function parseConditions(array $conditions): array
    {
        $processedConditions = [];

        if (isset($conditions[0]) && !is_array($conditions[0])) {
            $conditions = [$conditions];
        }

        foreach ($conditions as $field => $condition) {
            // [field, operator, value] or [field, value] handler
            if (is_array($condition) && ($count = count($condition)) >= 2 && isset($condition[0])) {
                $processedConditions[] = [
                    $condition[0],
                    $count === 2 ? (!in_array(strtoupper($condition[1]), [
                        'NULL',
                        'NOT_NULL'
                    ]) ? '=' : $condition[1]) : $condition[1],
                    $condition[2] ?? $condition[1]
                ];
                continue;
            }

            // 'key' => 'value' handler
            if (is_string($field) && !is_array($condition)) {
                $processedConditions[] = [$field, '=', $condition];
                continue;
            }

            // ['key' => 'value'] handler
            if (is_numeric($field) && is_array($condition) && !isset($condition[0])) {
                $field = key($condition);

                if (!isset($condition[$field])) {
                    continue;
                }

                $processedConditions[] = [$field, '=', $condition[$field]];
            }
        }

        return $processedConditions;
    }

    /**
     * Validate array data
     *
     * @throws RepositoryException
     */
    private function validateArrayData($data): void
    {
        if (!is_array($data)) {
            throw new RepositoryException("Invalid data provided, data must be an array.");
        }
    }

    /**
     * Validate closure data
     *
     * @throws RepositoryException
     */
    private function validateClosureFunction($value): void
    {
        if (!$value instanceof Closure && !is_null($value)) {
            throw new RepositoryException("Invalid closure provided.");
        }
    }
}
