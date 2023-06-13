<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Exceptions\Service;

use Exception;
use Illuminate\Http\Response;

/**
 * Class ServiceException
 */
class ServiceException extends Exception
{
    /**
     * ServiceException constructor.
     * @param string $message
     */
    public function __construct($message = "Service Exception")
    {
        parent::__construct($message, Response::HTTP_BAD_REQUEST);
    }
}
