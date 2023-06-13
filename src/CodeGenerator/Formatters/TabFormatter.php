<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\Contracts\FormatterInterface;

/**
 * Class TabFormatter
 */
class TabFormatter implements FormatterInterface
{
    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return "\t";
    }
}
