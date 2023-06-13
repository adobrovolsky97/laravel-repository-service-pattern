<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\CodeGeneratorServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\ApiResourceController\ApiResourceControllerTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits\CodeGeneratorTrait;

/**
 * ResourceControllerGeneratorCommand
 */
class ResourceControllerGeneratorCommand extends Command
{
    use CodeGeneratorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:resource-controller {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resource Controller generator command';

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
        $entityName = $this->getEntityNameFromTableName($this->argument('table'));

        $serviceInterfaceNamespace = config('repository-service-pattern.service.is_create_entity_folder')
            ? config('repository-service-pattern.service.namespace') . "\\$entityName\\Contracts\\{$entityName}ServiceInterface"
            : config('repository-service-pattern.service.namespace') . "\\Contracts\\{$entityName}ServiceInterface";

        $template = new ApiResourceControllerTemplate($entityName, $serviceInterfaceNamespace);

        $this->codeGeneratorService->generate($template);

        $this->info('Api Resource Controller generated');
    }
}
