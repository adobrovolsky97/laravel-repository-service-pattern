<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Tests\Unit;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\BaseCacheableRepository;
use Adobrovolsky97\LaravelRepositoryServicePattern\Tests\TestCase;

/**
 * Class BaseCacheableRepository
 */
class BaseCacheableRepositoryTest extends TestCase
{
    /**
     * Testing getAll()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testGetAll(): void
    {
        $searchParams = [];

        $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);
        $collection = $this->repository->getAll($searchParams);

        $cacheKey = $this->repository->generateCacheKey(
            BaseCacheableRepository::KEY_ALL,
            array_merge(
                $searchParams,
                ['with' => [], 'withCount' => [], 'softDeleteQueryMode' => null]
            )
        );
        $this->assertEquals($collection, $this->getCachedValue($cacheKey));

        $searchParams = ['prop_one' => 'test'];

        $collection = $this->repository->getAll($searchParams);
        $anotherCacheKey = $this->repository->generateCacheKey(
            BaseCacheableRepository::KEY_ALL,
            array_merge(
                $searchParams,
                ['with' => [], 'withCount' => [], 'softDeleteQueryMode' => null]
            )
        );

        $this->assertEquals($collection, $this->getCachedValue($anotherCacheKey));
        $this->assertNotEquals($cacheKey, $anotherCacheKey);
    }

    /**
     * Testing getAllPaginated()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testGetAllPaginated(): void
    {
        $searchParams = [];

        $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);
        $collection = $this->repository->getAllPaginated($searchParams);

        $cacheKey = $this->repository->generateCacheKey(
            BaseCacheableRepository::KEY_PAGINATED,
            array_merge(
                $searchParams,
                ['with' => [], 'withCount' => [], 'softDeleteQueryMode' => null]
            )
        );

        $this->assertEquals($collection, $this->getCachedValue($cacheKey));

        $searchParams = ['prop_one' => 'test'];

        $collection = $this->repository->getAllPaginated($searchParams);
        $anotherCacheKey = $this->repository->generateCacheKey(
            BaseCacheableRepository::KEY_PAGINATED,
            array_merge(
                $searchParams,
                ['with' => [], 'withCount' => [], 'softDeleteQueryMode' => null]
            )
        );

        $this->assertEquals($collection, $this->getCachedValue($anotherCacheKey));
        $this->assertNotEquals($cacheKey, $anotherCacheKey);
    }

    /**
     * Testing findFirst()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testFindFirst(): void
    {
        $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $attributes = ['prop_one' => 'one'];
        $model = $this->repository->findFirst($attributes);

        $cacheKey = $this->repository->generateCacheKeyForModelInstance($model->getKey());

        $this->assertEquals($model, $this->getCachedValue($cacheKey));
    }

    /**
     * Testing find()
     *
     * @return void
     */
    public function testFind(): void
    {
        $model = $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $data = $this->repository->find($model->getKey());

        $cacheKey = $this->repository->generateCacheKeyForModelInstance($model->getKey());

        $this->assertEquals($data, $this->getCachedValue($cacheKey));
    }

    /**
     * Testing findOrFail()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testFindOrFail(): void
    {
        $model = $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $data = $this->repository->findOrFail($model->getKey());

        $cacheKey = $this->repository->generateCacheKeyForModelInstance($model->getKey());

        $this->assertEquals($data, $this->getCachedValue($cacheKey));

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(123);
    }

    /**
     * Testing insert()
     *
     * @return void
     */
    public function testInsert(): void
    {
        $cacheKeyForPaginated = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_PAGINATED, []);
        $cacheKeyForAll = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_ALL, []);
        $cacheKeyForCursor = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_CURSOR, []);

        Cache::tags($cacheKeyForPaginated['tags'])->put('', 'test');
        Cache::tags($cacheKeyForAll['tags'])->put('', 'test');

        $this->repository->insert([
            ['prop_one' => 'one', 'prop_two' => 2],
            ['prop_one' => 'two', 'prop_two' => 2],
        ]);

        $this->assertDatabaseHas('tests', [
            'prop_one' => 'one',
            'prop_two' => 2
        ]);
        $this->assertDatabaseHas('tests', [
            'prop_one' => 'two',
            'prop_two' => 2
        ]);

        $this->assertNull($this->getCachedValue($cacheKeyForPaginated));
        $this->assertNull($this->getCachedValue($cacheKeyForAll));
        $this->assertNull($this->getCachedValue($cacheKeyForCursor));
    }

    /**
     * Testing create()
     *
     * @return void
     */
    public function testCreate(): void
    {
        $cacheKeyForPaginated = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_PAGINATED, []);
        $cacheKeyForAll = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_ALL, []);
        $cacheKeyForCursor = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_CURSOR, []);

        Cache::tags($cacheKeyForPaginated['tags'])->put('', 'test');
        Cache::tags($cacheKeyForAll['tags'])->put('', 'test');

        $model = $this->repository->create(['prop_one' => 'one', 'prop_two' => 2]);

        $this->assertModelExists($model);

        $this->assertNull($this->getCachedValue($cacheKeyForPaginated));
        $this->assertNull($this->getCachedValue($cacheKeyForAll));
        $this->assertNull($this->getCachedValue($cacheKeyForCursor));
    }

    /**
     * Testing update()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testUpdate(): void
    {
        $cacheKeyForPaginated = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_PAGINATED, []);
        $cacheKeyForAll = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_ALL, []);
        $cacheKeyForCursor = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_CURSOR, []);

        Cache::tags($cacheKeyForPaginated['tags'])->put('', 'test');
        Cache::tags($cacheKeyForAll['tags'])->put('', 'test');

        $model = $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);
        $model = $this->repository->update($model, ['prop_one' => 'two', 'prop_two' => 3]);

        $this->assertEquals('two', $model->getAttribute('prop_one'));
        $this->assertEquals(3, $model->getAttribute('prop_two'));

        $this->assertNull($this->getCachedValue($cacheKeyForPaginated));
        $this->assertNull($this->getCachedValue($cacheKeyForAll));
        $this->assertNull($this->getCachedValue($cacheKeyForCursor));
    }

    /**
     * Testing updateOrCreate()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testUpdateOrCreate(): void
    {
        $cacheKeyForPaginated = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_PAGINATED, []);
        $cacheKeyForAll = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_ALL, []);
        $cacheKeyForCursor = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_CURSOR, []);

        Cache::tags($cacheKeyForPaginated['tags'])->put('', 'test');
        Cache::tags($cacheKeyForAll['tags'])->put('', 'test');

        $model = $this->repository->updateOrCreate(
            ['prop_one' => 'val'],
            ['prop_one' => 'val', 'prop_two' => 2]
        );

        $this->assertModelExists($model);

        $this->assertNull($this->getCachedValue($cacheKeyForPaginated));
        $this->assertNull($this->getCachedValue($cacheKeyForAll));
        $this->assertNull($this->getCachedValue($cacheKeyForCursor));
    }

    /**
     * Testing delete()
     *
     * @return void
     * @throws Exception
     */
    public function testDelete(): void
    {
        $cacheKeyForPaginated = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_PAGINATED, []);
        $cacheKeyForAll = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_ALL, []);
        $cacheKeyForCursor = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_CURSOR, []);

        Cache::tags($cacheKeyForPaginated['tags'])->put('', 'test');
        Cache::tags($cacheKeyForAll['tags'])->put('', 'test');

        $model = $this->createModel(['prop_one' => 'val', 'prop_two' => 2]);

        $modelCacheKey = $this->repository->generateCacheKeyForModelInstance($model->getKey());
        Cache::tags($modelCacheKey['tags'])->put($modelCacheKey['paramsKey'], 'test');

        $this->repository->delete($model);
        $this->assertModelMissing($model);

        $this->assertNull($this->getCachedValue($cacheKeyForPaginated));
        $this->assertNull($this->getCachedValue($cacheKeyForAll));
        $this->assertNull($this->getCachedValue($cacheKeyForCursor));
        $this->assertNull($this->getCachedValue($modelCacheKey));
    }

    /**
     * Testing softDelete()
     *
     * @return void
     * @throws Exception
     */
    public function testSoftDelete(): void
    {
        $cacheKeyForPaginated = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_PAGINATED, []);
        $cacheKeyForAll = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_ALL, []);
        $cacheKeyForCursor = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_CURSOR, []);

        Cache::tags($cacheKeyForPaginated['tags'])->put('', 'test');
        Cache::tags($cacheKeyForAll['tags'])->put('', 'test');

        $model = $this->createModel(['prop_one' => 'val', 'prop_two' => 2]);

        $modelCacheKey = $this->repository->generateCacheKeyForModelInstance($model->getKey());
        Cache::tags($modelCacheKey['tags'])->put($modelCacheKey['paramsKey'], 'test');

        $this->repository->softDelete($model);
        $this->assertSoftDeleted($model);

        $this->assertNull($this->getCachedValue($cacheKeyForPaginated));
        $this->assertNull($this->getCachedValue($cacheKeyForAll));
        $this->assertNull($this->getCachedValue($cacheKeyForCursor));
        $this->assertNull($this->getCachedValue($modelCacheKey));
    }

    /**
     * Testing restore()
     *
     * @return void
     * @throws Exception
     */
    public function testRestore(): void
    {
        $cacheKeyForPaginated = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_PAGINATED, []);
        $cacheKeyForAll = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_ALL, []);
        $cacheKeyForCursor = $this->repository->generateCacheKey(BaseCacheableRepository::KEY_CURSOR, []);

        Cache::tags($cacheKeyForPaginated['tags'])->put('', 'test');
        Cache::tags($cacheKeyForAll['tags'])->put('', 'test');

        $model = $this->createModel(['prop_one' => 'val', 'prop_two' => 2, 'deleted_at' => now()]);

        $modelCacheKey = $this->repository->generateCacheKeyForModelInstance($model->getKey());
        Cache::tags($modelCacheKey['tags'])->put($modelCacheKey['paramsKey'], 'test');

        $this->repository->restore($model);
        $this->assertNotSoftDeleted($model);

        $this->assertNull($this->getCachedValue($cacheKeyForPaginated));
        $this->assertNull($this->getCachedValue($cacheKeyForAll));
        $this->assertNull($this->getCachedValue($cacheKeyForCursor));
        $this->assertEquals($model->refresh(), $this->getCachedValue($modelCacheKey));
    }

    /**
     * Test soft deletes query cache
     *
     * @return void
     */
    public function testSoftDeletesQuery(): void
    {
        $this->createModel(['prop_one' => 'one', 'prop_two' => 2, 'deleted_at' => now()]);
        $collection = $this->repository->onlyTrashed()->getAll();

        $onlyTrashedCacheKey = $this->repository->generateCacheKey(
            BaseCacheableRepository::KEY_ALL,
            array_merge(
                [],
                ['with' => [], 'withCount' => [], 'softDeleteQueryMode' => 3]
            )
        );

        $this->assertEquals($collection, $this->getCachedValue($onlyTrashedCacheKey));

        $collection = $this->repository->withoutTrashed()->getAll();

        $withoutTrashedCacheKey = $this->repository->generateCacheKey(
            BaseCacheableRepository::KEY_ALL,
            array_merge(
                [],
                ['with' => [], 'withCount' => [], 'softDeleteQueryMode' => 1]
            )
        );

        $this->assertEquals($collection, $this->getCachedValue($withoutTrashedCacheKey));
        $this->assertNotEquals($this->getCachedValue($withoutTrashedCacheKey), $this->getCachedValue($onlyTrashedCacheKey));

        $collection = $this->repository->withTrashed()->getAll();

        $withTrashedCacheKey = $this->repository->generateCacheKey(
            BaseCacheableRepository::KEY_ALL,
            array_merge(
                [],
                ['with' => [], 'withCount' => [], 'softDeleteQueryMode' => 2]
            )
        );

        $this->assertEquals($collection, $this->getCachedValue($withTrashedCacheKey));
    }

    /**
     * @param array $keyParams
     * @return mixed
     */
    protected function getCachedValue(array $keyParams)
    {
        return Cache::tags($keyParams['tags'])->get($keyParams['paramsKey']);
    }

    /**
     * @return void
     */
    protected function initializeRepository(): void
    {
        $model = $this->model;

        $this->repository = new class($model) extends BaseCacheableRepository {
            public $cacheAlias = 'test';
            protected $modelClass;

            public function __construct(Model $model)
            {
                $this->modelClass = get_class($model);
                parent::__construct();
            }

            public function generateCacheKey(string $keyName, array $params = []): array
            {
                return parent::generateCacheKey($keyName, $params);
            }

            /**
             * Generate cache key for single model
             *
             * @param $primaryKey
             * @return array
             */
            public function generateCacheKeyForModelInstance($primaryKey): array
            {
                return parent::generateCacheKeyForModelInstance($primaryKey);
            }

            protected function getModelClass(): string
            {
                return $this->modelClass;
            }
        };
    }
}
