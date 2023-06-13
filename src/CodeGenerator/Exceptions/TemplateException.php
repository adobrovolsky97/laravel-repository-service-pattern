<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions;

use Exception;

/**
 * Class TemplateException
 */
class TemplateException extends Exception
{
    /**
     * @param string $message
     * @param int $code
     */
    public function __construct($message = "Template exception.", $code = 400)
    {
        parent::__construct($message, $code);
    }
}
