<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Repository;

use Exception;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\EntityNameModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\PropertyModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ClassTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\BaseRepository;

/**
 * RepositoryTemplate
 */
class RepositoryTemplate extends ClassTemplate implements TemplateInterface
{
    /**
     * @var string
     */
    protected $modelName;

    /**
     * @param string $modelName
     */
    public function __construct(string $modelName)
    {
        parent::__construct();

        $this->modelName = $modelName;

        $this->setNamespace($modelName)->setName($modelName);
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function render(array $params = []): string
    {
        $repositoryInterfaceNamespace = config('repository-service-pattern.repository.is_create_entity_folder')
            ? config('repository-service-pattern.repository.namespace') . "\\$this->modelName\\Contracts\\{$this->modelName}RepositoryInterface"
            : config('repository-service-pattern.repository.namespace') . "\\Contracts\\{$this->modelName}RepositoryInterface";

        $this
            ->setType(EntityNameModel::TYPE_CLASS)
            ->addUse($this->getModelNameWithNamespace())
            ->setExtends(BaseRepository::class)
            ->setImplements($repositoryInterfaceNamespace)
            ->addMethod(
                'getModelClass',
                [],
                PropertyModel::ACCESS_PROTECTED,
                null,
                'string',
                ['return '. $this->modelName . '::class;']
            );

        return parent::render($params);
    }

    /**
     * @param string $name
     * @return ClassTemplate
     */
    public function setName(string $name): ClassTemplate
    {
        return parent::setName("{$name}Repository");
    }

    /**
     * @param string $namespace
     * @return ClassTemplate
     */
    public function setNamespace(string $namespace): ClassTemplate
    {
        if (config('repository-service-pattern.repository.is_create_entity_folder')) {
            return parent::setNamespace(
                config('repository-service-pattern.repository.namespace') . "\\$namespace"
            );
        }

        return parent::setNamespace(
            config('repository-service-pattern.repository.namespace')
        );
    }

    /**
     * @return string
     */
    public function getModelNameWithNamespace(): string
    {
        if (config('repository-service-pattern.model.is_create_entity_folder')) {
            return config('repository-service-pattern.model.namespace') . "\\$this->modelName\\$this->modelName";
        }

        return config('repository-service-pattern.model.namespace') . "\\$this->modelName";
    }
}
