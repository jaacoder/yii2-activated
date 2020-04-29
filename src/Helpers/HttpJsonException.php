<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Exception;
use yii\web\HttpException;

class HttpJsonException extends HttpException
{
    /**
     * Constructor.
     * @param int $status HTTP status code, such as 404, 500, etc.
     * @param mixed $message error message
     * @param int $code error code
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($status, $message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($status, json_encode($message), $code, $previous);
    }
}