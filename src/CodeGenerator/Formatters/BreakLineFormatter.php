<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\Contracts\FormatterInterface;

/**
 * Class BreakLineFormatter
 */
class BreakLineFormatter implements FormatterInterface
{
    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return "\n";
    }
}
