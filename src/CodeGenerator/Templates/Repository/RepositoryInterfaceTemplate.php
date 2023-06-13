<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Repository;

use Exception;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\EntityNameModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ClassTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts\BaseRepositoryInterface;

/**
 * RepositoryInterfaceTemplate
 */
class RepositoryInterfaceTemplate extends ClassTemplate implements TemplateInterface
{
    /**
     * @param string $entityName
     */
    public function __construct(string $entityName)
    {
        parent::__construct();

        $this->setNamespace($entityName)->setName($entityName);
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function render(array $params = []): string
    {
        $this->setType(EntityNameModel::TYPE_INTERFACE)->setExtends(BaseRepositoryInterface::class);

        return parent::render($params);
    }

    /**
     * @param string $name
     * @return ClassTemplate
     */
    public function setName(string $name): ClassTemplate
    {
        return parent::setName("{$name}RepositoryInterface");
    }

    /**
     * @param string $namespace
     * @return ClassTemplate
     */
    public function setNamespace(string $namespace): ClassTemplate
    {
        if (config('repository-service-pattern.repository.is_create_entity_folder')) {
            return parent::setNamespace(
                config('repository-service-pattern.repository.namespace') . "\\$namespace\\Contracts"
            );
        }

        return parent::setNamespace(
            config('repository-service-pattern.repository.namespace') . "\\Contracts"
        );
    }
}
