<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Tests\Units;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Repository\RepositoryException;
use Adobrovolsky97\LaravelRepositoryServicePattern\Tests\TestCase;

/**
 * Class RepositoryFiltersTest
 */
class RepositoryFiltersTest extends TestCase
{
    /**
     * Test applyFilterConditions()
     *
     * @param array $testTableData
     * @param string $propertyVal
     * @param array $search
     * @return void
     * @throws RepositoryException
     * @dataProvider dataForTestProvider
     */
    public function testQueryModelQuery(array $testTableData, string $propertyVal, array $search): void
    {
        $this->createModel([]);
        $relatedId = DB::table('relations')->insertGetId(['property' => $propertyVal]);
        $model = $this->createModel(array_merge($testTableData, ['related_id' => $relatedId]));


        $collection = $this->repository->findMany($search);
        $this->assertCount(1, $collection);
        $this->assertEquals($model->getKey(), optional($collection->first())->getKey());
    }

    public function dataForTestProvider(): array
    {
        return [
            // tests table data, relations table data, filters
            [['string' => 'stringVal'], 'val', ['string' => 'stringVal']],
            [['string' => 'stringVal'], 'val', ['string', 'like', '%stringVal%']],
            [['string' => 'stringVal'], 'val', [['string' => 'stringVal']]],
            [['string' => 'stringVal'], 'val', [['string', 'stringVal']]],
            [['string' => 'stringVal'], 'val', ['string', 'stringVal']],
            [['integer' => 5], 'val', ['integer', 5]],
            [['integer' => 5], 'val', ['integer', '>', 3]],
            [['integer' => 5], 'val', ['integer', '<=', 5]],
            [['integer' => 5], 'val', ['integer', '=', 5]],
            [['integer' => 5], 'val', ['integer', '>=', 5]],
            [['integer' => 5], 'val', ['integer', 'in', [1, 3, 5]]],
            [['integer' => 5], 'val', ['integer', 'NOT_IN', [1, 3, 2]]],
            [['integer' => 5], 'val', ['integer', 'not_null']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'date', '2022-01-01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'date <', '2022-01-02']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'date <=', '2022-01-01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'date =', '2022-01-01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'date >=', '2022-01-01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'month', '01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'month >=', '01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'month <=', '01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'year', '2022']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'year >=', '2022']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'year <=', '2022']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'day <=', '01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'day', '01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'day =', '01']],
            [['datetime' => '2022-01-01 10:00:00'], 'val', ['datetime', 'day >= ', '01']],
            [
                [],
                'val',
                [
                    'related',
                    'has',
                    function ($query) {
                        $query->where('property', 'val');
                    }
                ]
            ],
            [['integer' => 5], 'val', ['integer', 'between', [1, 6]]],
            [['integer' => 5], 'val', ['integer', 'not_between', [6, 10]]],
            [['integer' => 5], 'val', ['integer', 'not_between', [6, 10]]],
        ];
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
            $table->string('string')->nullable();
            $table->integer('integer')->nullable();
            $table->dateTime('datetime')->nullable();
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

            protected $fillable = ['string', 'integer', 'datetime', 'related_id', 'deleted_at'];

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
}
