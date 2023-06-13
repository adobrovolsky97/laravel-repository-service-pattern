<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\CodeGeneratorException;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\Contracts\FormatterInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;

/**
 * Class MethodModel
 */
class MethodModel implements ModelInterface
{
    /**
     * @const
     */
    const ACCESS_PUBLIC = 'public';
    const ACCESS_PROTECTED = 'protected';
    const ACCESS_PRIVATE = 'private';

    /**
     * @const
     */
    const TYPE_FINAL = 'final';
    const TYPE_ABSTRACT = 'abstract';

    /**
     * @var string
     */
    protected $access = self::ACCESS_PUBLIC;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $methodType = null;

    /**
     * @var array|MethodArgumentModel[]
     */
    protected $arguments = [];

    /**
     * @var string
     */
    protected $returnType;

    /**
     * @var null|DocBlockModel
     */
    protected $docBlock = null;

    /**
     * @var null|MethodBodyModel
     */
    protected $body = null;

    /**
     * @var null|FormatterInterface
     */
    protected $formatter = null;

    /**
     * @param array $params
     * @return string
     * @throws CodeGeneratorException
     */
    public function render(array $params = []): string
    {
        $format = (!is_null($this->formatter) ? $this->formatter->getFormat() : '');
        $lines = [
            !is_null($this->docBlock) ? $this->docBlock->render() . PHP_EOL : null,
            $format . $this->access,
            ' ',
            $this->methodType ? $this->methodType . ' ' : '',
            'function',
            ' '
        ];

        $methodSignature = [];

        foreach ($this->arguments as $argument) {

            if (!$argument instanceof MethodArgumentModel) {
                throw new CodeGeneratorException('Method argument is invalid');
            }

            $methodSignature[] = $argument->render();
        }

        $lines[] = !empty($methodSignature)
            ? $this->name . '(' . implode(', ', $methodSignature) . ')'
            : $this->name . '()';

        if ($this->returnType) {
            $lines[] = ': ' . $this->returnType;
        }
        $lines[] = PHP_EOL . $format . '{' . PHP_EOL;

        if (!is_null($this->body)) {
            $lines[] = $this->body->render();
        }

        $lines[] = $format . '}';

        return implode('', array_filter($lines));
    }

    /**
     * @param string $access
     * @return MethodModel
     * @throws CodeGeneratorException
     */
    public function setAccess(string $access): MethodModel
    {
        if (!in_array($access, [self::ACCESS_PUBLIC, self::ACCESS_PROTECTED, self::ACCESS_PRIVATE])) {
            throw new CodeGeneratorException('Invalid access for method provided');
        }

        $this->access = $access;

        return $this;
    }

    /**
     * @param mixed $name
     * @return MethodModel
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param array|MethodArgumentModel[] $arguments
     * @return MethodModel
     */
    public function setArguments(array $arguments): MethodModel
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @param string $returnType
     * @return MethodModel
     */
    public function setReturnType(string $returnType): MethodModel
    {
        $this->returnType = $returnType;

        return $this;
    }

    /**
     * @param DocBlockModel $docBlock
     * @return MethodModel
     */
    public function setDocBlock(DocBlockModel $docBlock): MethodModel
    {
        $this->docBlock = $docBlock;

        return $this;
    }

    /**
     * @param string $methodType
     * @return MethodModel
     * @throws CodeGeneratorException
     */
    public function setMethodType(string $methodType): MethodModel
    {
        if (!in_array($methodType, [self::TYPE_FINAL, self::TYPE_ABSTRACT])) {
            throw new CodeGeneratorException('Invalid method type given');
        }

        $this->methodType = $methodType;

        return $this;
    }

    /**
     * @param FormatterInterface|null $formatter
     * @return MethodModel
     */
    public function setFormatter(FormatterInterface $formatter): MethodModel
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @param MethodBodyModel|null $body
     * @return MethodModel
     */
    public function setBody(MethodBodyModel $body): MethodModel
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return array|MethodArgumentModel[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getMethodType(): ?string
    {
        return $this->methodType;
    }

    /**
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->returnType;
    }

    /**
     * @return DocBlockModel|null
     */
    public function getDocBlock(): ?DocBlockModel
    {
        return $this->docBlock;
    }

    /**
     * @return MethodBodyModel|null
     */
    public function getBody(): ?MethodBodyModel
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getAccess(): string
    {
        return $this->access;
    }
}
