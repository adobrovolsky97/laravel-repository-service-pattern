<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Resource;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\TemplateException;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\TabFormatter;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\EntityNameModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\MethodModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\PropertyModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ClassTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResourceTemplate
 */
class ResourceTemplate extends ClassTemplate implements TemplateInterface
{
    /**
     * @var string|null
     */
    protected $modelWithNamespace;

    /**
     * @var string
     */
    protected $modelName;

    /**
     * @param string $modelName
     * @throws TemplateException
     */
    public function __construct(string $modelName)
    {
        parent::__construct();

        $this->modelName = $modelName;
        $this->modelWithNamespace = config('repository-service-pattern.model.is_create_entity_folder')
            ? config('repository-service-pattern.model.namespace') . "\\$modelName\\$modelName"
            : config('repository-service-pattern.model.namespace')."\\$modelName";

        if (!class_exists($this->modelWithNamespace)) {
            throw new TemplateException("Model '$this->modelWithNamespace' not found.");
        }

        $this->setName($modelName)->setNamespace($modelName);
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function render(array $params = []): string
    {
        $this
            ->addUse(JsonResponse::class)
            ->addUse(Response::class)
            ->setType(EntityNameModel::TYPE_CLASS)
            ->setExtends(JsonResource::class)
            ->addProperty(
                'statusCode',
                'Response::HTTP_OK',
                PropertyModel::ACCESS_PROTECTED,
                ['@var integer']
            )
            ->addMethod(
                '__construct',
                [
                    ['name' => 'resource'],
                    ['type' => 'int', 'name' => 'statusCode', 'defaultValue' => 'Response::HTTP_OK']
                ],
                MethodModel::ACCESS_PUBLIC,
                null,
                null,
                ['$this->statusCode = $statusCode;', '', 'parent::__construct($resource);']
            )
            ->addMethod(
                'toArray',
                [
                    ['name' => 'request']
                ],
                MethodModel::ACCESS_PUBLIC,
                null,
                'array',
                $this->getResourceBody()
            )
            ->addMethod(
                'toResponse',
                [
                    ['name' => 'request']
                ],
                MethodModel::ACCESS_PUBLIC,
                null,
                'JsonResponse',
                ['return parent::toResponse($request)->setStatusCode($this->statusCode);']
            );

        return parent::render($params);
    }

    /**
     * @param string $name
     * @return ClassTemplate
     */
    public function setName(string $name): ClassTemplate
    {
        return parent::setName($name.'Resource');
    }

    /**
     * @param string $namespace
     * @return ClassTemplate
     */
    public function setNamespace(string $namespace): ClassTemplate
    {
        if (config('repository-service-pattern.resource.is_create_entity_folder')) {
            return parent::setNamespace(
                config('repository-service-pattern.resource.namespace') . "\\$namespace"
            );
        }

        return parent::setNamespace(
            config('repository-service-pattern.resource.namespace')
        );
    }

    /**
     * Get resource body
     *
     * @return array
     * @throws TemplateException
     */
    protected function getResourceBody(): array
    {
        if (is_null($this->modelWithNamespace)) {
            return ['return [', '', '];'];
        }

        /** @var Model $model */
        $model = resolve($this->modelWithNamespace);

        if (!$model instanceof Model) {
            throw new TemplateException('Model must be an instance of ' . Model::class);
        }

        $this->addUse($this->modelWithNamespace);

        $this->setDocBlockContent(['Class ' . $this->getName(), '', '@mixin ' . $this->modelName]);

        $body = ['return ['];

        $tabFormatter = resolve(TabFormatter::class);

        foreach (array_unique(array_merge(Arr::wrap($model->getKeyName()), $model->getFillable())) as $fillable) {
            $body[] = $tabFormatter->getFormat() . "'$fillable'" . ' => $this->' . $fillable . ',';
        }

        $body[] = '];';

        return $body;
    }

}
