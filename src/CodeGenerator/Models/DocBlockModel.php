<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models;

use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Formatters\Contracts\FormatterInterface;
use Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Models\Contracts\ModelInterface;

/**
 * Class DocBlockModel
 */
class DocBlockModel implements ModelInterface
{
    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var FormatterInterface|null
     */
    protected $formatter;

    /**
     * {@inheritDoc}
     */
    public function render(array $params = []): string
    {
        $format = !is_null($this->formatter) ? $this->formatter->getFormat() : '';

        $lines = [];
        $lines[] = "$format/**";
        if ($this->content) {
            foreach ($this->content as $item) {
                $lines[] = sprintf("$format * %s", $item);
            }
        } else {
            $lines[] = "$format *";
        }
        $lines[] = "$format */";

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array $content
     * @return DocBlockModel
     */
    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param FormatterInterface|null $formatter
     * @return DocBlockModel
     */
    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }
}
