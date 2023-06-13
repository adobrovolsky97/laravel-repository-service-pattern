<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\CodeGeneratorServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Request\RequestTemplate;

/**
 * RequestGeneratorCommand
 */
class RequestGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:request {namespace} {modelNamespace?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Request generator command';

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
        $exploded = explode('\\', $this->argument('namespace'));
        $className = last($exploded);

        unset($exploded[count($exploded) - 1]);

        $namespace = implode('\\', $exploded);

        $this->codeGeneratorService->generate(
            new RequestTemplate($className, $namespace, $this->argument('modelNamespace'))
        );

        $this->info('Request generated');
    }
}
