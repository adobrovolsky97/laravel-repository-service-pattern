<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Services\Contracts;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;

/**
 * CodeGeneratorServiceInterface
 */
interface CodeGeneratorServiceInterface
{
    /**
     * Generate file by template
     *
     * @param TemplateInterface $template
     * @param array $params
     * @return void
     */
    public function generate(TemplateInterface $template, array $params = []): void;
}
