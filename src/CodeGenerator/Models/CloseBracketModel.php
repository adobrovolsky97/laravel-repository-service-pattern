<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;

/**
 * CloseBracketModel
 */
class CloseBracketModel implements ModelInterface
{
    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        return '}';
    }
}
