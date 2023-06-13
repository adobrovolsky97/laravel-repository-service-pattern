<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern;

use Illuminate\Support\ServiceProvider;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\CodeGeneratorService;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\CodeGeneratorServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts\DbManagerServiceInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\DbManagerService;
use Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands\CrudGeneratorCommand;
use Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands\ModelGeneratorCommand;
use Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands\RepositoryAndServiceCodeGeneratorCommand;
use Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands\RequestGeneratorCommand;
use Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands\ResourceControllerGeneratorCommand;
use Adobrovolsky97\LaravelRepositoryServicePattern\Console\Commands\ResourceGeneratorCommand;

/**
 * Class LaravelRepositoryServicePatternServiceProvider
 */
class LaravelRepositoryServicePatternServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {

    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/config/repository-service-pattern.php' => config_path('repository-service-pattern.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__.'/config/repository-service-pattern.php',
            'repository-service-pattern'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                RepositoryAndServiceCodeGeneratorCommand::class,
                ResourceControllerGeneratorCommand::class,
                RequestGeneratorCommand::class,
                ModelGeneratorCommand::class,
                ResourceGeneratorCommand::class,
                CrudGeneratorCommand::class
            ]);
        }

        $this->app->singleton(CodeGeneratorServiceInterface::class, CodeGeneratorService::class);
        $this->app->singleton(DbManagerServiceInterface::class, DbManagerService::class);
    }
}
