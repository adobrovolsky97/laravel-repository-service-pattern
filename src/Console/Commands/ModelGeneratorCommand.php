<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\CodeGeneratorServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Model\ModelTemplate;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits\CodeGeneratorTrait;

/**
 * ModelGeneratorCommand
 */
class ModelGeneratorCommand extends Command
{
    use CodeGeneratorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:model {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model command';

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
            new ModelTemplate(
                $this->argument('table'),
                $this->getEntityNameFromTableName($this->argument('table'))
            )
        );

        $this->info('Model generated');
    }
}
