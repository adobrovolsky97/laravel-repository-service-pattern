<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\CodeGeneratorException;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\TabFormatter;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits\ValueTrait;

/**
 * Class Constant Model
 */
class ConstantModel implements ModelInterface
{
    use ValueTrait;

    /**
     * @const
     */
    const ACCESS_PUBLIC = 'public';
    const ACCESS_PROTECTED = 'protected';
    const ACCESS_PRIVATE = 'private';

    /**
     * @var string
     */
    protected $access = self::ACCESS_PUBLIC;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var null|DocBlockModel
     */
    protected $docBlock = null;

    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        $lines = [
            !is_null($this->docBlock) ? $this->docBlock->render() . PHP_EOL : null,
            $this->access ?? null,
            resolve(TabFormatter::class)->getFormat(),
            'const',
            ' ',
            $this->name,
            ' ',
            '=',
            ' ',
            $this->renderTyped($this->value),
            ';'
        ];

        return implode('', $lines);
    }

    /**
     * @return string
     */
    public function getAccess(): string
    {
        return $this->access;
    }

    /**
     * Set constant access
     *
     * @param string|null $access
     * @return ConstantModel
     * @throws CodeGeneratorException
     */
    public function setAccess(string $access = null): self
    {
        $access = $access ?? self::ACCESS_PUBLIC;

        if (!in_array($access, [self::ACCESS_PUBLIC, self::ACCESS_PROTECTED, self::ACCESS_PRIVATE])) {
            throw new CodeGeneratorException('Invalid access');
        }

        $this->access = $access === self::ACCESS_PUBLIC ? null : $access;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ConstantModel
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param DocBlockModel|null $docBlock
     * @return ConstantModel
     */
    public function setDocBlock(DocBlockModel $docBlock): self
    {
        $this->docBlock = $docBlock;

        return $this;
    }
}
