<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Illuminate\Support\Arr;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\CodeGeneratorException;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;

/**
 * Class ClassNameModel
 */
class EntityNameModel implements ModelInterface
{
    /**
     * Class types
     *
     * @const
     */
    const CLASS_TYPE_FINAL = 'final';
    const CLASS_TYPE_ABSTRACT = 'abstract';

    /**
     * Types
     *
     * @const
     */
    const TYPE_CLASS = 'class';
    const TYPE_INTERFACE = 'interface';
    const TYPE_TRAIT = 'trait';

    /**
     * Possible class types
     *
     * @const
     */
    const CLASS_TYPES = [
        self::CLASS_TYPE_FINAL, self::CLASS_TYPE_ABSTRACT
    ];

    /**
     * Possible types
     *
     * @const
     */
    const TYPES = [
        self::TYPE_CLASS, self::TYPE_INTERFACE, self::TYPE_TRAIT
    ];

    /**
     * Type of generated file (class/interface/trait)
     *
     * @var null|string
     */
    protected $type = self::TYPE_CLASS;

    /**
     * Class/interface extends
     *
     * @var string|array
     */
    protected $extends = [];

    /**
     * Class implements
     *
     * @var array
     */
    protected $implements = [];

    /**
     * Class/interface name
     *
     * @var string
     */
    protected $name;

    /**
     * Class doc block
     *
     * @var DocBlockModel|null
     */
    protected $docBlockModel;

    /**
     * Class type (abstract/final)
     *
     * @var string
     */
    protected $classType;

    /**
     * Set type (class/interface)
     *
     * @param string $type
     * @return $this
     * @throws CodeGeneratorException
     */
    public function setType(string $type): self
    {
        if (in_array($this->type, [self::TYPE_INTERFACE, self::TYPE_TRAIT])) {
            throw new CodeGeneratorException("$this->type could not be final or abstract");
        }

        if (!in_array($this->type, self::TYPES)) {
            throw new CodeGeneratorException('Invalid type given');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Set class type (final, abstract)
     *
     * @param string $classType
     * @return EntityNameModel
     * @throws CodeGeneratorException
     */
    public function setClassType(string $classType): self
    {
        $this->classType = $classType;

        if (!in_array($this->classType, self::CLASS_TYPES)) {
            throw new CodeGeneratorException('Invalid class type given');
        }

        return $this;
    }

    /**
     * Set class/interface name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set extends of interface/class
     *
     * @param string|array $extends
     * @return EntityNameModel
     * @throws CodeGeneratorException
     */
    public function setExtends($extends): self
    {
        if ($this->type === self::TYPE_CLASS && count($extends) > 1) {
            throw new CodeGeneratorException('Class can not extend more that 1 class');
        }

        if ($this->type === self::TYPE_TRAIT) {
            throw new CodeGeneratorException('Trait could not extend any classes');
        }

        $this->extends = Arr::wrap($extends);

        return $this;
    }

    /**
     * @param array $implements
     * @return EntityNameModel
     * @throws CodeGeneratorException
     */
    public function setImplements(array $implements): self
    {
        if ($this->type === self::TYPE_TRAIT) {
            throw new CodeGeneratorException('Trait could not implement any interfaces');
        }

        $this->implements = $implements;

        return $this;
    }

    /**
     * @param DocBlockModel|null $docBlockModel
     * @return EntityNameModel
     */
    public function setDocBlockModel(DocBlockModel $docBlockModel): self
    {
        $this->docBlockModel = $docBlockModel;

        return $this;
    }

    /**
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        $result = !is_null($this->docBlockModel) ? $this->docBlockModel->render() . PHP_EOL : '';

        if (!is_null($this->classType)) {
            $result .= "$this->classType ";
        }

        $result .= "$this->type $this->name";

        if (!empty($this->extends)) {
            $result .= " extends " . implode(', ', $this->extends);
        }

        if (!empty($this->implements)) {
            $result .= " implements " . implode(', ', $this->implements);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array|string
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * @return array
     */
    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * @return string
     */
    public function getClassType(): ?string
    {
        return $this->classType;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }
}
