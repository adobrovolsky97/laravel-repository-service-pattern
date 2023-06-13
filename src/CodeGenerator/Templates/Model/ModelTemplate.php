<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Model;

use Adobrovolsky97\LaravelRepositoryServicePattern\Models\BaseModel;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\TemplateException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\MethodModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\PropertyModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ClassTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\DbManagerService;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\DbManagerServiceInterface;

/**
 * ModelTemplate
 */
class ModelTemplate extends ClassTemplate implements TemplateInterface
{
    /**
     * @const
     */
    protected const DEFAULT_PRIMARY_KEY = 'id';

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var bool
     */
    protected $isCustomModelName = false;

    /**
     * @var string|array
     */
    protected $primaryKey;

    /**
     * @var string
     */
    protected $keyType;

    /**
     * @var bool
     */
    protected $isTimestamps = false;

    /**
     * @var bool
     */
    protected $isUseCarbon = false;

    /**
     * @var array
     */
    protected $docBlockContent = [];

    /**
     * @var DbManagerServiceInterface
     */
    protected $dbManager;

    /**
     * @var array
     */
    protected $modelProperties = [];

    /**
     * @param string $tableName
     * @param string $modelName
     * @throws Exception
     */
    public function __construct(string $tableName, string $modelName)
    {
        parent::__construct();

        if (!Schema::hasTable($tableName)) {
            throw new TemplateException("Table '$tableName' not found");
        }

        $this->dbManager = resolve(DbManagerServiceInterface::class);

        $this->tableName = $tableName;
        $this->isCustomModelName = strtolower(Str::snake(Str::plural($modelName))) !== $this->tableName;

        $this->setName($modelName)->setNamespace($modelName);
        $this->parseModelProperties();
        $this->parsePrimaryKey();
        $this->collectDocBlockContent();
        $this->parseRelations();
    }

    /**
     * @param string $namespace
     * @return ClassTemplate
     */
    public function setNamespace(string $namespace): ClassTemplate
    {
        if (config('repository-service-pattern.model.is_create_entity_folder')) {
            return parent::setNamespace(
                config('repository-service-pattern.model.namespace') . "\\$namespace"
            );
        }

        return parent::setNamespace(
            config('repository-service-pattern.model.namespace')
        );
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function render(array $params = []): string
    {
        $this
            ->setExtends(BaseModel::class)
            ->setDocBlockContent($this->docBlockContent);

        if ($this->isUseCarbon || $this->isTimestamps) {
            $this->addUse(Carbon::class);
        }

        if (!$this->isTimestamps) {
            $this->addProperty('timestamps', 'false', PropertyModel::ACCESS_PUBLIC);
        }

        if ($this->isCustomModelName) {
            $this->addProperty('table', $this->tableName, PropertyModel::ACCESS_PROTECTED);
        }

        if ($this->primaryKey !== self::DEFAULT_PRIMARY_KEY) {
            $this
                ->addProperty('primaryKey', $this->primaryKey, PropertyModel::ACCESS_PROTECTED)
                ->addProperty('keyType', $this->keyType, PropertyModel::ACCESS_PROTECTED)
                ->addProperty('incrementing', false, PropertyModel::ACCESS_PUBLIC);
        }

        $this->addProperty('fillable',
            collect($this->modelProperties)
                ->keys()
                ->filter(function ($propertyName) {
                    if ($this->primaryKey === self::DEFAULT_PRIMARY_KEY) {
                        return !in_array($propertyName, Arr::wrap($this->primaryKey));
                    }

                    return true;
                })
                ->toArray(),
            PropertyModel::ACCESS_PROTECTED
        );

        return parent::render($params);
    }

    /**
     * Parse primaryKey
     *
     * @return void
     */
    protected function parsePrimaryKey(): void
    {
        $this->primaryKey = $this->dbManager->getPrimaryKey($this->tableName);
        $this->keyType = is_array($this->primaryKey) ? 'array' : $this->modelProperties[$this->primaryKey];
    }

    /**
     * Collect doc block
     *
     * @return void
     */
    protected function collectDocBlockContent(): void
    {
        $this->addDocBlockContent("Class " . $this->getName())->addDocBlockContent('');

        foreach ($this->modelProperties as $name => $type) {
            $this->addDocBlockContent("@property $type $" . $name);
        }
    }

    /**
     * Parse table relations
     *
     * @return void
     * @throws Exception
     */
    protected function parseRelations(): void
    {
        foreach ($this->dbManager->getRelations($this->tableName) as $relation) {
            $this->addRelation(
                $relation['type'],
                $relation['relatedTable'],
                $relation['foreignKey'],
                $relation['localKey'],
                $relation['joinTable'] ?? null
            );
        }
    }

    /**
     * Parse table properties
     *
     * @return void
     */
    protected function parseModelProperties(): void
    {
        $this->modelProperties = $this->dbManager->getTableProperties($this->tableName);
        $this->isTimestamps = !empty(array_intersect(array_keys($this->modelProperties), ['created_at', 'updated_at']));
        $this->isUseCarbon = $this->isTimestamps || in_array('Carbon', $this->modelProperties);
    }

    /**
     * Add relation to a model methods
     *
     * @param string $relationType
     * @param string $tableName
     * @param string $foreignKeyName
     * @param string $localKeyName
     * @param string|null $joinTableName
     *
     * @return void
     * @throws Exception
     */
    protected function addRelation(
        string $relationType,
        string $tableName,
        string $foreignKeyName,
        string $localKeyName,
        string $joinTableName = null
    ): void
    {
        $tableName = ucfirst(Str::singular($tableName));
        $className = Str::ucfirst(Str::camel($tableName));

        $this->addUse(config('repository-service-pattern.model.namespace') . "\\$className\\$className");

        switch ($relationType) {
            case DbManagerService::RELATION_BELONGS_TO:
                $this
                    ->addUse(BelongsTo::class)
                    ->addDocBlockContent("@property $className $" . Str::camel($tableName))
                    ->addMethod(
                        Str::camel($tableName),
                        [],
                        MethodModel::ACCESS_PUBLIC,
                        null,
                        'BelongsTo',
                        ["return " . '$' . "this->belongsTo($className::class, '$foreignKeyName', '$localKeyName');"]
                    );
                break;
            case DbManagerService::RELATION_BELONGS_TO_MANY:
                $this
                    ->addUse(BelongsToMany::class)
                    ->addUse(Collection::class)
                    ->addDocBlockContent("@property " . $className . "[]|Collection $" . Str::camel(Str::plural($tableName)))
                    ->addMethod(
                        Str::camel(Str::plural($tableName)),
                        [],
                        MethodModel::ACCESS_PUBLIC,
                        null,
                        'BelongsToMany',
                        ["return " . '$' . "this->belongsToMany($className::class, '$joinTableName', '$foreignKeyName', '$localKeyName');"]
                    );
                break;
            case DbManagerService::RELATION_HAS_MANY:
                $this
                    ->addUse(HasMany::class)
                    ->addUse(Collection::class)
                    ->addDocBlockContent("@property " . $className . "[]|Collection $" . Str::camel(Str::plural($tableName)))
                    ->addMethod(
                        Str::camel(Str::plural($tableName)),
                        [],
                        MethodModel::ACCESS_PUBLIC,
                        null,
                        'HasMany',
                        ["return " . '$' . "this->hasMany($className::class, '$foreignKeyName', '$localKeyName');"]
                    );
                break;
            case DbManagerService::RELATION_HAS_ONE:
                $this
                    ->addUse(HasOne::class)
                    ->addDocBlockContent("@property $className $" . Str::camel($tableName))
                    ->addMethod(
                        Str::camel($tableName),
                        [],
                        MethodModel::ACCESS_PUBLIC,
                        null,
                        'HasOne',
                        ["return " . '$' . "this->hasOne($className::class, '$foreignKeyName', '$localKeyName');"]
                    );
                break;
            default:
                throw new Exception('Invalid relation type');
        }
    }

    /**
     * Add docBlock content
     *
     * @param string $content
     *
     * @return self
     */
    protected function addDocBlockContent(string $content): self
    {
        $this->docBlockContent[] = $content;

        return $this;
    }
}
