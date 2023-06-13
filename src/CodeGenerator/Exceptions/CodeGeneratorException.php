<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\CodeGenerator\Exceptions;

use Exception;

/**
 * Class CodeGeneratorException
 */
class CodeGeneratorException extends Exception
{
    /**
     * @param string $message
     * @param int $code
     */
    public function __construct($message = "Code generator exception.", $code = 400)
    {
        parent::__construct($message, $code);
    }
}
