<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class BaseCrudServiceWithCompositeKeysTest
 */
class BaseCrudServiceWithCompositeKeysTest extends BaseCrudServiceTest
{
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
            $table->string('prop_one');
            $table->integer('prop_two');
            $table->foreignId('related_id')->nullable()->constrained('relations');
            $table->timestamps();
            $table->softDeletes();
            $table->primary(['prop_one', 'prop_two']);
        });
    }

    /**
     * @return void
     */
    protected function initializeModel(): void
    {
        $this->model = new class extends Model {
            use SoftDeletes;

            public $incrementing = false;
            protected $primaryKey = ['prop_one', 'prop_two'];
            protected $keyType = 'array';
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

            protected function setKeysForSaveQuery($query)
            {
                return $query->where('prop_one', $this->getAttribute('prop_one'))
                    ->where('prop_two', $this->getAttribute('prop_two'));
            }
            public function getKey()
            {
                /** @var array $keys */
                if (!is_array($keys = $this->getKeyName())) {
                    return $this->getAttribute($this->primaryKey);
                }

                $attributes = [];

                foreach ($keys as $key) {
                    $attributes[$key] = $this->getAttribute($key);
                }

                return $attributes;
            }
        };
    }
}
