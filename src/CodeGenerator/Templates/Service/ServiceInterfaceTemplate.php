<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Service;

use Exception;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\EntityNameModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ClassTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Services\Contracts\BaseCrudServiceInterface;

/**
 * ServiceInterfaceTemplate
 */
class ServiceInterfaceTemplate extends ClassTemplate implements TemplateInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @param string $entityName
     */
    public function __construct(string $entityName)
    {
        parent::__construct();

        $this->entityName = $entityName;

        $this->setNamespace($entityName)->setName($entityName);
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function render(array $params = []): string
    {
        $this->setType(EntityNameModel::TYPE_INTERFACE)->setExtends(BaseCrudServiceInterface::class);

        return parent::render($params);
    }

    /**
     * @param string|null $name
     * @return void
     */
    public function setName(string $name): ClassTemplate
    {
        return parent::setName("{$name}ServiceInterface");
    }

    /**
     * @param string|null $namespace
     * @return void
     */
    public function setNamespace(string $namespace): ClassTemplate
    {
        if (config('repository-service-pattern.service.is_create_entity_folder')) {
            return parent::setNamespace(
                config('repository-service-pattern.service.namespace') . "\\$namespace\\Contracts"
            );
        }

        return parent::setNamespace(
            config('repository-service-pattern.service.namespace') . "\\Contracts"
        );
    }
}
