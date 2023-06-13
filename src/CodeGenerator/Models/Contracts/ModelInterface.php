<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts;

/**
 * ModelInterface
 */
interface ModelInterface
{
    /**
     * Render
     *
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string;
}
