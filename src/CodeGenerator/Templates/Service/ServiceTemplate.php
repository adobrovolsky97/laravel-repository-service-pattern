<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Service;

use Exception;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\EntityNameModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\PropertyModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ClassTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Services\BaseCrudService;

/**
 * ServiceTemplate
 */
class ServiceTemplate extends ClassTemplate implements TemplateInterface
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

        $serviceInterfaceNamespace = config('repository-service-pattern.service.is_create_entity_folder')
            ? config('repository-service-pattern.service.namespace') . "\\$this->modelName\\Contracts\\{$this->modelName}ServiceInterface"
            : config('repository-service-pattern.service.namespace') . "\\Contracts\\{$this->modelName}ServiceInterface";

        $this
            ->addUse($repositoryInterfaceNamespace)
            ->setType(EntityNameModel::TYPE_CLASS)
            ->setExtends(BaseCrudService::class)
            ->setImplements($serviceInterfaceNamespace)
            ->addMethod(
                'getRepositoryClass',
                [],
                PropertyModel::ACCESS_PROTECTED,
                null,
                'string',
                ['return '.$this->modelName . 'RepositoryInterface::class;']
            );

        return parent::render($params);
    }

    /**
     * @param string $name
     * @return ClassTemplate
     */
    public function setName(string $name): ClassTemplate
    {
        return parent::setName("{$name}Service");
    }

    /**
     * @param string $namespace
     * @return ClassTemplate
     */
    public function setNamespace(string $namespace): ClassTemplate
    {
        if (config('repository-service-pattern.service.is_create_entity_folder')) {
            return parent::setNamespace(
                config('repository-service-pattern.service.namespace') . "\\$namespace"
            );
        }

        return parent::setNamespace(
            config('repository-service-pattern.service.namespace') . ""
        );
    }
}
