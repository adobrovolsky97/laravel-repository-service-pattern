<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\Contracts;

/**
 * FormatterInterface
 */
interface FormatterInterface
{
    /**
     * Get format
     *
     * @return string|null
     */
    public function getFormat(): ?string;
}
