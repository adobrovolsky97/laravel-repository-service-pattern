<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\CodeGeneratorServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ApiResourceController\ApiResourceControllerTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Model\ModelTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Repository\RepositoryInterfaceTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Repository\RepositoryTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Request\RequestTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Resource\ResourceTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Service\ServiceInterfaceTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Service\ServiceTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits\CodeGeneratorTrait;

/**
 * CrudGeneratorCommand
 */
class CrudGeneratorCommand extends Command
{
    use CodeGeneratorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:crud {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Full CRUD generator command generator command';

    /**
     * @var CodeGeneratorServiceInterface
     */
    protected $codeGeneratorService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CodeGeneratorServiceInterface $codeGeneratorService)
    {
        parent::__construct();

        $this->codeGeneratorService = $codeGeneratorService;
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $modelName = $this->getEntityNameFromTableName($this->argument('table'));

        // Model generation
        $modelWithNamespace = config('repository-service-pattern.model.is_create_entity_folder')
            ? config('repository-service-pattern.model.namespace') . "\\$modelName\\$modelName"
            : config('repository-service-pattern.model.namespace') . "\\$modelName";

        if (!class_exists($modelWithNamespace)) {
            $modelTemplate = new ModelTemplate($this->argument('table'), $modelName);

            $this->codeGeneratorService->generate($modelTemplate);
            $this->info("Generated Model '$modelName'");
        }

        // Repository and RepositoryInterface generation
        $repositoryInterfaceTemplate = new RepositoryInterfaceTemplate($modelName);

        if (!interface_exists("{$repositoryInterfaceTemplate->getNamespace()}\\{$repositoryInterfaceTemplate->getName()}")) {
            $this->codeGeneratorService->generate($repositoryInterfaceTemplate);
            $this->info("Generated Repository Interface '{$repositoryInterfaceTemplate->getName()}'");
        }

        $repositoryTemplate = new RepositoryTemplate($modelName);

        if (!class_exists("{$repositoryTemplate->getNamespace()}\\{$repositoryTemplate->getName()}")) {
            $this->codeGeneratorService->generate($repositoryTemplate);
            $this->info("Generated Repository '{$repositoryTemplate->getName()}'");
        }

        // Service and ServiceInterface generation
        $serviceInterfaceTemplate = new ServiceInterfaceTemplate($modelName);

        if (!interface_exists("{$serviceInterfaceTemplate->getNamespace()}\\{$serviceInterfaceTemplate->getName()}")) {
            $this->codeGeneratorService->generate($serviceInterfaceTemplate);
            $this->info("Generated Service Interface '{$serviceInterfaceTemplate->getName()}'");
        }

        $serviceTemplate = new ServiceTemplate($modelName);

        if (!class_exists("{$serviceTemplate->getNamespace()}\\{$serviceTemplate->getName()}")) {
            $this->codeGeneratorService->generate($serviceTemplate);
            $this->info("Generated Service '{$serviceTemplate->getName()}'");
        }

        // Resource generation
        $resourceTemplate = new ResourceTemplate($modelName);

        if (!class_exists("{$resourceTemplate->getNamespace()}\\{$resourceTemplate->getName()}")) {
            $this->codeGeneratorService->generate($resourceTemplate);
            $this->info("Generated Resource '{$resourceTemplate->getName()}'");
        }

        // Requests generation
        $storeRequestTemplate = new RequestTemplate(
            config('repository-service-pattern.controller.store_request_name'),
            $modelName,
            $modelWithNamespace
        );

        if (!class_exists("{$storeRequestTemplate->getNamespace()}\\{$storeRequestTemplate->getName()}")) {
            $this->codeGeneratorService->generate($storeRequestTemplate);
            $this->info("Generated Store Request '{$storeRequestTemplate->getName()}'");
        }

        $updateRequestTemplate = new RequestTemplate(
            config('repository-service-pattern.controller.update_request_name'),
            $modelName,
            $modelWithNamespace
        );

        if (!class_exists("{$updateRequestTemplate->getNamespace()}\\{$updateRequestTemplate->getName()}")) {
            $this->codeGeneratorService->generate($updateRequestTemplate);
            $this->info("Generated Update Request '{$updateRequestTemplate->getName()}'");
        }

        // Controller generation
        $controllerTemplate = new ApiResourceControllerTemplate(
            $modelName,
            "{$serviceInterfaceTemplate->getNamespace()}\\{$serviceInterfaceTemplate->getName()}"
        );

        if (!class_exists("{$controllerTemplate->getNamespace()}\\{$controllerTemplate->getName()}")) {
            $this->codeGeneratorService->generate($controllerTemplate);
            $this->info("Generated Controller '{$controllerTemplate->getName()}'");
        }
    }
}
