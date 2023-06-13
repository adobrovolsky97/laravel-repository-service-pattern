<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Tests\Unit;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Service\ServiceException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseRepositoryInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Services\BaseCrudService;
use Adobrovolsky97\LaravelRepositoryServicePattern\Services\Contracts\BaseCrudServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Tests\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class BaseCrudServiceTest
 */
class BaseCrudServiceTest extends TestCase
{
    /**
     * @var BaseCrudServiceInterface|BaseCrudService
     */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeService();
    }

    /**
     * Test getAllPaginated()
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetAllPaginated(): void
    {
        $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $data = $this->service->getAllPaginated();

        $this->assertNotEmpty($data->items());
        $this->assertEquals(1, $data->total());
        $this->assertInstanceOf(LengthAwarePaginator::class, $data);
    }

    /**
     * Test getAll()
     *
     * @return void
     */
    public function testGetAll(): void
    {
        $this->createModel([
            'prop_one'   => 'one',
            'prop_two'   => 2,
            'related_id' => DB::table('relations')->insertGetId(['property' => 'test'])
        ]);

        $data = $this->service
            ->with(['related'])
            ->withCount(['related'])
            ->getAll();

        $this->assertNotEmpty($data);
        $this->assertCount(1, $data);
        $this->assertInstanceOf(Collection::class, $data);
        $this->assertTrue($data->first()->relationLoaded('related'));
        $this->assertEquals(1, $data->first()->getAttribute('related_count'));
    }

    /**
     * Test getAll() with softDeletes
     *
     * @return void
     */
    public function testGetAllWithSoftDeletes(): void
    {
        $trashed = $this->createModel([
            'prop_one'   => 'one',
            'prop_two'   => 2,
            'deleted_at' => now()
        ]);

        $notTrashed = $this->createModel([
            'prop_one'   => 'two',
            'prop_two'   => 2,
            'deleted_at' => null
        ]);

        // By default, soft deleted records are excluded from query
        $data = $this->service->getAll();
        $this->assertCount(1, $data);
        $this->assertNotNull(
            $data
                ->where('prop_one', $notTrashed->getAttribute('prop_one'))
                ->where('prop_two', $notTrashed->getAttribute('prop_two'))
                ->first()
        );

        $data = $this->service->withoutTrashed()->getAll();
        $this->assertCount(1, $data);
        $this->assertNotNull(
            $data
                ->where('prop_one', $notTrashed->getAttribute('prop_one'))
                ->where('prop_two', $notTrashed->getAttribute('prop_two'))
                ->first()
        );

        // Included soft deleted
        $this->assertCount(2, $this->service->withTrashed()->getAll());

        $data = $this->service->onlyTrashed()->getAll();
        $this->assertCount(1, $data);
        $this->assertNotNull(
            $data
                ->where('prop_one', $trashed->getAttribute('prop_one'))
                ->where('prop_two', $trashed->getAttribute('prop_two'))
                ->first()
        );
    }

    /**
     * Test getAllAsCursor()
     *
     * @return void
     */
    public function testGetAllAsCursor(): void
    {
        $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $data = $this->service->getAllAsCursor();

        $this->assertNotEmpty($data);
        $this->assertCount(1, $data);
        $this->assertInstanceOf(LazyCollection::class, $data);
    }

    /**
     * Test count()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testCount(): void
    {
        $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $this->assertEquals(1, $this->service->count(['prop_one' => 'one']));
        $this->assertEquals(0, $this->service->count(['prop_one' => 'two']));
    }

    /**
     * Test findOrFail()
     *
     * @return void
     */
    public function testFindOrFail(): void
    {
        $model = $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $this->service->findOrFail($model->getKey());
        $this->service->findOrFail(2, 'prop_two');

        $this->expectException(ModelNotFoundException::class);
        $this->service->findOrFail(3, 'prop_two');
    }

    /**
     * Test find()
     *
     * @return void
     */
    public function testFind(): void
    {
        $this->createModel(['prop_one' => 'one', 'prop_two' => 2]);

        $this->assertNotEmpty($this->service->find([['prop_one', 'one']]));
        $this->assertNotEmpty($this->service->find([['prop_one', 'one'], ['prop_two', 2]]));
        $this->assertEmpty($this->service->find([['prop_one', 'two']]));
    }

    /**
     * Test create()
     *
     * @return void
     * @throws ServiceException
     */
    public function testCreate(): void
    {
        $this->service->create(['prop_one' => 'one', 'prop_two' => 3]);

        $this->assertDatabaseHas(
            'tests', [
                'prop_one' => 'one',
                'prop_two' => 3
            ]
        );
    }

    /**
     * Test insert()
     *
     * @return void
     */
    public function testInsert(): void
    {
        $result = $this->service->insert([
            ['prop_one' => 'val', 'prop_two' => 1],
            ['prop_one' => 'val1', 'prop_two' => 2],
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('tests', [
            'prop_one' => 'val',
            'prop_two' => 1
        ]);
        $this->assertDatabaseHas('tests', [
            'prop_one' => 'val1',
            'prop_two' => 2
        ]);
    }

    /**
     * Test createMany()
     *
     * @return void
     * @throws ServiceException
     */
    public function testCreateMany(): void
    {
        $models = $this->service->createMany([
            ['prop_one' => 'val', 'prop_two' => 1],
            ['prop_one' => 'val1', 'prop_two' => 2]
        ]);

        $this->assertCount(2, $models);
        $this->assertDatabaseHas('tests', [
            'prop_one' => 'val',
            'prop_two' => 1
        ]);
        $this->assertDatabaseHas('tests', [
            'prop_one' => 'val1',
            'prop_two' => 2
        ]);
    }

    /**
     * Test updateOrCreate()
     *
     * @return void
     * @throws ServiceException
     */
    public function testUpdateOrCreate(): void
    {
        $model = $this->service->updateOrCreate(
            ['prop_one' => 'val'],
            ['prop_one' => 'val', 'prop_two' => 2]
        );

        $this->assertDatabaseHas(
            'tests',
            ['prop_one' => 'val', 'prop_two' => 2]
        );

        $this->assertTrue($model->wasRecentlyCreated);

        $model = $this->service->updateOrCreate(
            ['prop_one' => 'val'],
            ['prop_one' => 'val', 'prop_two' => 5]
        );

        $this->assertFalse($model->wasRecentlyCreated);
        $this->assertEquals(5, $model->getAttribute('prop_two'));
    }

    /**
     * Test update()
     *
     * @return void
     */
    public function testUpdate(): void
    {
        $model = $this->createModel(['prop_one' => 'val', 'prop_two' => 5]);
        $model = $this->service->update($model->getKey(), ['prop_one' => 'test', 'prop_two' => 15]);

        $this->assertEquals('test', $model->getAttribute('prop_one'));
        $this->assertEquals(15, $model->getAttribute('prop_two'));
    }

    /**
     * Test delete()
     *
     * @return void
     * @throws Exception
     */
    public function testDelete(): void
    {
        $model = $this->createModel(['prop_one' => 'test', 'prop_two' => 15]);

        $this->service->delete($model->getKey());

        $this->assertDatabaseMissing(
            'tests',
            ['prop_one' => 'test', 'prop_two' => 15]
        );
    }

    /**
     * Test deleteMany()
     *
     * @return void
     * @throws Exception
     */
    public function testDeleteMany(): void
    {
        $model = $this->createModel(['prop_one' => 'test', 'prop_two' => 15]);
        $modelTwo = $this->createModel(['prop_one' => 'val', 'prop_two' => 5]);

        $this->service->deleteMany([$model->getKey(), $modelTwo->getKey()]);

        $this->assertDatabaseMissing(
            'tests',
            ['prop_one' => 'test', 'prop_two' => 15]
        );

        $this->assertDatabaseMissing(
            'tests',
            ['prop_one' => 'val', 'prop_two' => 5]
        );
    }

    /**
     * Test softDelete()
     *
     * @return void
     */
    public function testSoftDelete(): void
    {
        $model = $this->createModel(['prop_one' => 'test', 'prop_two' => 15]);

        $this->service->softDelete($model->getKey());
        $model = $this->service->withTrashed()->find([['prop_one', 'test'], ['prop_two', 15]])->first();
        $this->assertNotNull($model->deleted_at);

        $model = new class extends Model {
        };

        // Model is not an entity of the repository model class
        $this->expectException(RepositoryException::class);
        $this->service->softDelete($model);
    }

    /**
     * Test restore()
     *
     * @return void
     * @throws RepositoryException
     */
    public function testRestore(): void
    {
        $model = $this->createModel(['prop_one' => 'test', 'prop_two' => 15, 'deleted_at' => now()]);

        $this->service->restore($model->getKey());

        $model = $this->service->findOrFail($model->getKey());
        $this->assertNull($model->getAttribute('deleted_at'));
    }

    /**
     * Initialize service for testing
     *
     * @return void
     */
    protected function initializeService(): void
    {
        $repository = $this->repository;

        $this->app->bind(get_class($repository), function () use ($repository) {
            return $repository;
        });

        $this->service = new class($repository) extends BaseCrudService {
            private $tmpRepository;

            public function __construct(BaseRepositoryInterface $repository)
            {
                $this->tmpRepository = $repository;
                parent::__construct();
                unset($this->tmpRepository);
            }

            protected function getRepositoryClass(): string
            {
                return get_class($this->tmpRepository);
            }
        };
    }
}
