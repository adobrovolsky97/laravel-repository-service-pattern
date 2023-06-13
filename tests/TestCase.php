<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\BaseRepository;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Class TestCase
 */
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var BaseRepositoryInterface|BaseRepository
     */
    protected $repository;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->runMigrations();
        $this->initializeModel();
        $this->initializeRepository();
    }

    /**
     * @return void
     */
    protected function runMigrations(): void
    {
        Schema::create('relations', function (Blueprint $table) {
            $table->id();
            $table->integer('property');
            $table->timestamps();
        });
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('prop_one');
            $table->integer('prop_two');
            $table->foreignId('related_id')->nullable()->constrained('relations');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * @return void
     */
    protected function initializeModel(): void
    {
        $this->model = new class extends Model {
            use SoftDeletes;

            protected $table = 'tests';

            protected $fillable = ['prop_one', 'prop_two', 'related_id', 'deleted_at'];

            protected $related;

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);

                $this->related = new class extends Model {
                    protected $table = 'relations';
                    protected $fillable = ['property'];
                };
            }

            public function related(): BelongsTo
            {
                return $this->belongsTo(get_class($this->related), 'related_id');
            }
        };
    }

    /**
     * @return void
     */
    protected function initializeRepository(): void
    {
        $model = $this->model;

        $this->repository = new class($model) extends BaseRepository {
            protected $modelClass;
            public function __construct(Model $model)
            {
                $this->modelClass = get_class($model);
            }

            protected function getModelClass(): string
            {
                return $this->modelClass;
            }
        };
    }

    /**
     * @param array $data
     * @return Model
     */
    protected function createModel(array $data): Model
    {
        return $this->model->query()->create($data);
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return ['Adobrovolsky97\LaravelRepositoryServicePattern\LaravelRepositoryServicePatternServiceProvider'];
    }
}
