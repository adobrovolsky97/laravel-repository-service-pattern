<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;

/**
 * Class MethodArgumentModel
 */
class MethodArgumentModel implements ModelInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $defaultValue;

    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        $lines = [
            $this->type ? $this->type . ' ' : '',
            $this->name,
            isset($this->defaultValue) ? ' = ' . $this->defaultValue : ''
        ];

        return implode('', $lines);
    }

    /**
     * Set method argument type
     *
     * @param string|null $type
     * @return MethodArgumentModel
     */
    public function setType(string $type = null): MethodArgumentModel
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set method argument name
     *
     * @param string $name
     * @return MethodArgumentModel
     */
    public function setName(string $name): MethodArgumentModel
    {
        $this->name = '$'.$name;

        return $this;
    }

    /**
     * Set method argument default value
     *
     * @param mixed $defaultValue
     * @return MethodArgumentModel
     */
    public function setDefaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
