<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Exception;
use Throwable;

/**
 * Class for custom exceptions.
 */
class AppException extends Exception
{
    const DEFAULT_ERROR_MSG = 'Internal Error';
    
    /**
     * @var AppMessage[]
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
     * @param string|AppMessage[] $message
     * @return static
     */
    public static function create($message = self::DEFAULT_ERROR_MSG)
    {
        $appException = new static();
        
        if (is_array($message)) {
            $appException->messages = $message;
            
        } else {
            $appException->messages = [AppMessage::error($message)];
        }
        
        return $appException;
    }
    
    /**
     * @param array $messages
     * @return static
     */
    public function addMessages()
    {
        $messages = func_get_args();
        
        foreach ($messages as $message) {
           $this->messages = array_merge($this->messages, $message);
        }
        
        return $this;
    }
}