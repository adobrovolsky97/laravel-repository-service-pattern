<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates;

use Illuminate\Support\Arr;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\CodeGeneratorException;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions\TemplateException;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\BreakLineFormatter;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\Contracts\FormatterInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\TabFormatter;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\EntityNameModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\CloseBracketModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\ConstantModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\DocBlockModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\MethodArgumentModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\MethodBodyModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\MethodModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\NamespaceModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\OpenBracketModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\PhpTagModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\PropertyModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\UseModel;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Templates\Contracts\TemplateInterface;

/**
 * ClassTemplate
 */
class ClassTemplate implements TemplateInterface
{
    /**
     * Class content
     *
     * @const
     */
    protected const CONTENT_PROPERTIES = 'properties';
    protected const CONTENT_METHODS = 'methods';
    protected const CONTENT_IMPLEMENTS = 'implements';
    protected const CONTENT_CLASS_TYPE = 'classType';
    protected const CONTENT_CONSTANTS = 'constants';
    protected const CONTENT_EXTENDS = 'extends';
    protected const CONTENT_METHOD_BODY = 'methodBody';

    /**
     * Content mapping [type => possibleContent]
     *
     * @const
     */
    protected const CONTENT_MAPPING = [
        EntityNameModel::TYPE_CLASS     => [
            self::CONTENT_PROPERTIES,
            self::CONTENT_METHODS,
            self::CONTENT_IMPLEMENTS,
            self::CONTENT_CLASS_TYPE,
            self::CONTENT_CONSTANTS,
            self::CONTENT_EXTENDS,
            self::CONTENT_METHOD_BODY,
        ],
        EntityNameModel::TYPE_INTERFACE => [
            self::CONTENT_EXTENDS,
            self::CONTENT_METHODS
        ],
        EntityNameModel::TYPE_TRAIT     => [
            self::CONTENT_METHODS,
            self::CONTENT_PROPERTIES,
            self::CONTENT_METHOD_BODY,
        ]
    ];

    /**
     * Class namespace
     *
     * @var ModelInterface|NamespaceModel
     */
    protected $namespace;

    /**
     * @var DocBlockModel|null
     */
    protected $classDocBlock;

    /**
     * Class name
     *
     * @var ModelInterface|EntityNameModel
     */
    protected $classNameModel;

    /**
     * Class uses
     *
     * @var array|ModelInterface[]
     */
    protected $uses = [];

    /**
     * Class constants
     *
     * @var array|ModelInterface[]
     */
    protected $constants = [];

    /**
     * Class properties
     *
     * @var array|ModelInterface[]
     */
    protected $properties = [];

    /**
     * Class methods
     *
     * @var array||ModelInterface[]
     */
    protected $methods = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->classNameModel = resolve(EntityNameModel::class);
    }

    /**
     * Set class/interface doc block content
     *
     * @param array $content
     * @return ClassTemplate
     */
    public function setDocBlockContent(array $content): ClassTemplate
    {
        $this->classDocBlock = resolve(DocBlockModel::class)->setContent($content);

        $this->classNameModel->setDocBlockModel($this->classDocBlock);

        return $this;
    }

    /**
     * Get type (interface/class)
     *
     * @return string
     */
    public function getType(): ?string
    {
        return $this->classNameModel->getType();
    }

    /**
     * Set class type
     *
     * @param string $type
     * @return ClassTemplate
     * @throws CodeGeneratorException
     */
    public function setType(string $type): ClassTemplate
    {
        $this->classNameModel->setType($type);

        return $this;
    }

    /**
     * Get className
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->classNameModel->getName();
    }

    /**
     * Set class name, string if default class, closure to customize
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): ClassTemplate
    {
        $this->classNameModel->setName($name);

        return $this;
    }

    /**
     * Get namespace
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace->getNamespace();
    }

    /**
     * Set class namespace
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace(string $namespace): ClassTemplate
    {
        $this->namespace = resolve(NamespaceModel::class)->setNamespace($namespace);

        return $this;
    }

    /**
     * Set use
     *
     * @param string $use
     * @return $this
     */
    public function addUse(string $use): ClassTemplate
    {
        $this->uses[$use] = resolve(UseModel::class)->setUse($use);

        return $this;
    }

    /**
     * Get class uses
     *
     * @return array|ModelInterface[]
     */
    public function getUses(): array
    {
        return collect($this->uses)->map(function (UseModel $model) {
            return $model->getUse();
        })->toArray();
    }

    /**
     * Get class extends
     *
     * @return array
     */
    public function getExtends(): array
    {
        return $this->classNameModel->getExtends();
    }

    /**
     * Set extends
     *
     * @param array|string $extends
     * @return ClassTemplate
     * @throws CodeGeneratorException
     */
    public function setExtends($extends): ClassTemplate
    {
        $result = [];

        foreach (Arr::wrap($extends) as $extendedNamespace) {
            $this->addUse($extendedNamespace);
            $result[] = $this->getClassNameFromNamespace($extendedNamespace);
        }

        $this->classNameModel->setExtends($result);

        return $this;
    }

    /**
     * Get implements
     *
     * @return array
     */
    public function getImplements(): array
    {
        return $this->classNameModel->getImplements();
    }

    /**
     * Set implements
     *
     * @param array|string $implements
     * @return ClassTemplate
     * @throws CodeGeneratorException|TemplateException
     */
    public function setImplements($implements): ClassTemplate
    {
        $this->validateTemplateContent(self::CONTENT_IMPLEMENTS);

        $result = [];

        foreach (Arr::wrap($implements) as $implementsNamespace) {
            $this->addUse($implementsNamespace);
            $result[] = $this->getClassNameFromNamespace($implementsNamespace);
        }

        $this->classNameModel->setImplements($result);

        return $this;
    }

    /**
     * Set class type
     *
     * @param string|null $classType
     * @return ClassTemplate
     * @throws CodeGeneratorException|TemplateException
     */
    public function setClassType(?string $classType): ClassTemplate
    {
        $this->validateTemplateContent(self::CONTENT_CLASS_TYPE);

        $this->classNameModel->setClassType($classType);

        return $this;
    }

    /**
     * Get class type (abstract/final)
     *
     * @return string|null
     */
    public function getClassType(): ?string
    {
        return $this->classNameModel->getClassType();
    }

    /**
     * Get class constants
     *
     * @return array
     */
    public function getConstants(): array
    {
        return collect($this->constants)
            ->map(function (ConstantModel $constantModel) {
                return [
                    'access' => $constantModel->getAccess(),
                    'name'   => $constantModel->getName(),
                    'value'  => $constantModel->getValue()
                ];
            })
            ->toArray();
    }

    /**
     * Add constant
     *
     * @param string $name
     * @param $value
     * @param string|null $access
     * @param string|null $docBlockContent
     * @return ClassTemplate
     * @throws TemplateException|CodeGeneratorException
     */
    public function addConstant(string $name, $value, string $access = null, string $docBlockContent = null): ClassTemplate
    {
        $this->validateTemplateContent(self::CONTENT_CONSTANTS);

        $docBlockContent = is_null($docBlockContent) ? ['@const'] : [$docBlockContent, '@const'];

        $this->constants[] = resolve(ConstantModel::class)
            ->setAccess($access)
            ->setName($name)
            ->setValue($value)
            ->setDocBlock(
                resolve(DocBlockModel::class)
                    ->setContent($docBlockContent)
                    ->setFormatter(resolve(TabFormatter::class))
            );

        return $this;
    }

    /**
     * Get properties
     *
     * @return array|ModelInterface[]
     */
    public function getProperties(): array
    {
        return collect($this->properties)
            ->map(function (PropertyModel $model) {
                return [
                    'access'          => $model->getAccess(),
                    'name'            => $model->getName(),
                    'value'           => $model->getValue(),
                    'docBlockComment' => optional($model->getDocBlockModel())->getContent()
                ];
            })
            ->toArray();
    }

    /**
     * Add property
     *
     * @param string $name
     * @param string|null|array $value
     * @param string|null $access
     * @param array $docBlockComments
     * @return ClassTemplate
     * @throws TemplateException|CodeGeneratorException
     */
    public function addProperty(
        string $name,
               $value = null,
        string $access = null,
        array  $docBlockComments = []
    ): ClassTemplate
    {
        $this->validateTemplateContent(self::CONTENT_PROPERTIES);

        $this->properties[] = resolve(PropertyModel::class)
            ->setAccess($access ?? PropertyModel::ACCESS_PUBLIC)
            ->setName($name)
            ->setFormatter(resolve(TabFormatter::class))
            ->setValue($value)
            ->setDocBlockModel(
                resolve(DocBlockModel::class)
                    ->setContent(
                        !empty($docBlockComments)
                            ? $docBlockComments
                            : [is_null($value) ? '@var null' : '@var ' . gettype($value)]
                    )
                    ->setFormatter(new TabFormatter())
            );

        return $this;
    }

    /**
     * Get methods
     *
     * @return array
     */
    public function getMethods(): array
    {
        return collect($this->methods)
            ->map(function (MethodModel $model) {

                $arguments = [];

                foreach ($model->getArguments() as $argument) {
                    $arguments[] = [
                        'type'         => $argument->getType(),
                        'name'         => $argument->getName(),
                        'defaultValue' => $argument->getDefaultValue()
                    ];
                }

                return [
                    'access'     => $model->getAccess(),
                    'name'       => $model->getName(),
                    'arguments'  => $arguments,
                    'returnType' => $model->getReturnType(),
                    'methodType' => $model->getMethodType(),
                    'body'       => $model->getBody()->getLines(),
                    'dobBlock'   => $model->getDocBlock()->getContent()
                ];
            })
            ->toArray();
    }

    /**
     * Add method
     *
     * @param string $name
     * @param array $arguments
     * @param string $access
     * @param string|null $methodType
     * @param string|null $returnType
     * @param array $body
     * @param array $docBlockComments
     * @return ClassTemplate
     * @throws CodeGeneratorException|TemplateException
     */
    public function addMethod(
        string $name,
        array  $arguments = [],
        string $access = MethodModel::ACCESS_PUBLIC,
        string $methodType = null,
        string $returnType = null,
        array  $body = [],
        array  $docBlockComments = []
    ): ClassTemplate
    {
        /** @var MethodModel $method */
        $method = resolve(MethodModel::class)
            ->setFormatter(resolve(TabFormatter::class))
            ->setAccess($access)
            ->setName($name);

        if (!is_null($methodType)) {

            if ($methodType === MethodModel::TYPE_ABSTRACT
                && $this->getClassType() !== EntityNameModel::CLASS_TYPE_ABSTRACT) {
                throw new TemplateException('Non abstract class could not have abstract methods;');
            }

            $method->setMethodType($methodType);
        }

        if (!empty($arguments)) {

            $argumentModels = [];

            foreach ($arguments as $argument) {

                $this->validateMethodArgument($argument);

                $argumentModel = resolve(MethodArgumentModel::class)
                    ->setType($argument['type'] ?? null)
                    ->setName($argument['name']);

                if (isset($argument['defaultValue'])) {
                    $argumentModel->setDefaultValue($argument['defaultValue']);
                }

                $argumentModels[] = $argumentModel;
            }

            $method->setArguments($argumentModels);
        }

        if (!is_null($returnType)) {
            $method->setReturnType($returnType);
        }

        if (!empty($body)) {
            $this->validateTemplateContent(self::CONTENT_METHOD_BODY);
            $method->setBody(resolve(MethodBodyModel::class)->setLines($body));
        }

        if (!empty($docBlockComments) || !is_null($returnType) || !empty($arguments)) {

            if (empty($docBlockComments)) {
                foreach ($arguments as $argument) {
                    $docBlockComments[] = '@param ' . (isset($argument['type']) ? $argument['type'] . ' ' : '') . '$' . $argument['name'];
                }

                if (!is_null($returnType)) {

                    if (count($docBlockComments) > 0) {
                        $docBlockComments[] = '';
                    }

                    $docBlockComments[] = "@return $returnType";
                }
            }

            $method->setDocBlock(
                resolve(DocBlockModel::class)
                    ->setFormatter(resolve(TabFormatter::class))
                    ->setContent($docBlockComments)
            );
        }

        $this->methods[] = $method;

        return $this;
    }

    /**
     * Render template content
     *
     * @param array $params
     * @return string
     */
    public function render(array $params = []): string
    {
        $breakLineFormatter = resolve(BreakLineFormatter::class);

        if (is_null($this->classDocBlock)) {
            $type = ucfirst($this->getType());
            $this->setDocBlockContent(["$type {$this->getName()}"]);
        }

        $content = [
            resolve(PhpTagModel::class)->render(),
            $breakLineFormatter->getFormat(),
            $breakLineFormatter->getFormat(),

            // Namespace
            $this->namespace->render(),
            $breakLineFormatter->getFormat(),
            $breakLineFormatter->getFormat(),

            // Uses
            $this->getRenderedUses($breakLineFormatter),
            $breakLineFormatter->getFormat(),
            $breakLineFormatter->getFormat(),

            // Class name
            $this->classNameModel->render(),
            $breakLineFormatter->getFormat(),
            resolve(OpenBracketModel::class)->render(),
            $breakLineFormatter->getFormat(),

            // Constants
            $this->getRenderedConstants($breakLineFormatter),

            // Properties
            !empty($this->properties) && !empty($this->constants)
                ? $breakLineFormatter->getFormat() . $breakLineFormatter->getFormat()
                : null,
            $this->getRenderedProperties($breakLineFormatter),

            // Methods
            !empty($this->methods) && !empty($this->properties)
                ? $breakLineFormatter->getFormat() . $breakLineFormatter->getFormat()
                : null,
            $this->getRenderedMethods($breakLineFormatter),
            $breakLineFormatter->getFormat(),

            resolve(CloseBracketModel::class)->render(),
        ];

        return implode('', $content);
    }

    /**
     * Get rendered uses
     *
     * @param FormatterInterface $formatter
     * @return string
     */
    protected function getRenderedUses(FormatterInterface $formatter): string
    {
        return collect($this->uses)
            ->map(function (UseModel $model) {
                return $model->render();
            })
            ->sortBy(function (string $use) {
                return strlen($use);
            })
            ->implode($formatter->getFormat());
    }

    /**
     * Get rendered constants
     *
     * @param FormatterInterface $formatter
     * @return string
     */
    protected function getRenderedConstants(FormatterInterface $formatter): string
    {
        return collect($this->constants)
            ->map(function (ConstantModel $model) {
                return $model->render();
            })
            ->implode($formatter->getFormat() . $formatter->getFormat());
    }

    /**
     * Get formatted and rendered properties
     *
     * @param FormatterInterface $formatter
     * @return string
     */
    protected function getRenderedProperties(FormatterInterface $formatter): string
    {
        return collect($this->properties)
            ->sortBy(function (PropertyModel $model) {
                return array_search(
                    $model->getAccess(),
                    [PropertyModel::ACCESS_PUBLIC, PropertyModel::ACCESS_PROTECTED, PropertyModel::ACCESS_PRIVATE]
                );
            })
            ->map(function (PropertyModel $model) {
                return $model->render();
            })
            ->implode($formatter->getFormat() . $formatter->getFormat());
    }

    /**
     * Get sorted and rendered methods
     *
     * @param FormatterInterface $formatter
     * @return string
     */
    protected function getRenderedMethods(FormatterInterface $formatter): string
    {
        return collect($this->methods)
            ->sortBy(function (MethodModel $model) {
                return array_search(
                    $model->getAccess(),
                    [PropertyModel::ACCESS_PUBLIC, PropertyModel::ACCESS_PROTECTED, PropertyModel::ACCESS_PRIVATE]
                );
            })
            ->map(function (MethodModel $model) {
                return $model->render();
            })
            ->implode($formatter->getFormat() . $formatter->getFormat());
    }

    /**
     * Validate template content
     *
     * @param string $contentType
     * @return void
     * @throws TemplateException
     */
    protected function validateTemplateContent(string $contentType): void
    {
        if (!isset(self::CONTENT_MAPPING[$this->getType()])) {
            throw new TemplateException('Invalid class type provided');
        }

        if (!in_array($contentType, self::CONTENT_MAPPING[$this->getType()])) {
            throw new TemplateException("Error while creating template, {$this->getType()} could not have $contentType");
        }
    }

    /**
     * Validate method argument
     *
     * @param $argument
     *
     * @return void
     * @throws TemplateException
     */
    protected function validateMethodArgument($argument): void
    {
        if (!is_array($argument)) {
            throw new TemplateException('Method argument must be an array with [type (optional), name, defaultValue (optional)] properties');
        }

        if (!isset($argument['name'])) {
            throw new TemplateException('Method argument name missing');
        }
    }

    /**
     * Get class name from full namespace
     *
     * @param string $namespace
     * @return string
     */
    protected function getClassNameFromNamespace(string $namespace): string
    {
        return last(explode('\\', $namespace));
    }
}
