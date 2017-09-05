<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Exception;
use Throwable;

/**
 * Class for Exception with an array of messages.
 */
class ArrayException extends Exception
{
    const DEFAULT_ERROR_MSG = 'Internal Error';
    
    /**
     * @var array
     */
    public $messages = [];
    
    /**
     * Constructor.
     * 
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     */
    public function __construct($message = self::DEFAULT_ERROR_MSG, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Factory.
     * 
     * @param array $messages
     * @return static
     */
    public static function create($messages)
    {
        $arrayException = new static();
        $arrayException->messages = $messages;
        
        return $arrayException;
    }
}