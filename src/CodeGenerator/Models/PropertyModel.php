<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\CodeGeneratorException;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\Contracts\FormatterInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Traits\ValueTrait;

/**
 * Class PropertyModel
 */
class PropertyModel implements ModelInterface
{
    use ValueTrait;

    /**
     * @const
     */
    const VALUE_NON_INITIALIZED = 'nonInitialized';

    /**
     * @const
     */
    const ACCESS_PUBLIC = 'public';
    const ACCESS_PROTECTED = 'protected';
    const ACCESS_PRIVATE = 'private';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $access = self::ACCESS_PUBLIC;

    /**
     * @var null|mixed
     */
    protected $value = null;

    /**
     * @var DocBlockModel|null
     */
    protected $docBlockModel;

    /**
     * @var FormatterInterface|null
     */
    protected $formatter;

    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        $result = '';
        if (!is_null($this->docBlockModel)) {
            $result = $this->docBlockModel->render() . PHP_EOL;
        }

        $parts = [
            !is_null($this->formatter) ? $this->formatter->getFormat() : '',
            $this->access,
            ' ',
            '$',
            $this->name,
        ];

        if ($this->value !== self::VALUE_NON_INITIALIZED) {
            $parts = array_merge($parts, [
                ' ',
                '=',
                ' ',
                $this->renderTyped($this->value),
            ]);
        }

        $parts[] = ';';

        return $result . implode('', $parts);
    }

    /**
     * @param string $name
     * @return PropertyModel
     */
    public function setName(string $name): PropertyModel
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $access
     * @return PropertyModel
     * @throws CodeGeneratorException
     */
    public function setAccess(string $access): PropertyModel
    {
        $this->access = $access;

        if (!in_array($access, [self::ACCESS_PUBLIC, self::ACCESS_PRIVATE, self::ACCESS_PROTECTED])) {
            throw new CodeGeneratorException('Invalid access given');
        }

        return $this;
    }

    /**
     * @param mixed|null $value
     * @return PropertyModel
     */
    public function setValue($value = null): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param DocBlockModel|null $docBlockModel
     * @return PropertyModel
     */
    public function setDocBlockModel(DocBlockModel $docBlockModel): PropertyModel
    {
        $this->docBlockModel = $docBlockModel;

        return $this;
    }

    /**
     * @param FormatterInterface|null $formatter
     * @return PropertyModel
     */
    public function setFormatter(FormatterInterface $formatter): PropertyModel
    {
        $this->formatter = $formatter;

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
     * @return string
     */
    public function getAccess(): string
    {
        return $this->access;
    }

    /**
     * @return DocBlockModel|null
     */
    public function getDocBlockModel(): ?DocBlockModel
    {
        return $this->docBlockModel;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }
}
