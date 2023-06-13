<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\CodeGeneratorServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Resource\ResourceTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits\CodeGeneratorTrait;

/**
 * ResourceGeneratorCommand
 */
class ResourceGeneratorCommand extends Command
{
    use CodeGeneratorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:resource {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resource generator command';

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
        $this->codeGeneratorService->generate(
            new ResourceTemplate($this->getEntityNameFromTableName($this->argument('table')))
        );

        $this->info('Resource generated');
    }
}
