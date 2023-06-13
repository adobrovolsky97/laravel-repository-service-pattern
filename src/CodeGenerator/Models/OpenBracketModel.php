<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;

/**
 * Class OpenBracketModel
 */
class OpenBracketModel implements ModelInterface
{
    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        return '{';
    }
}
