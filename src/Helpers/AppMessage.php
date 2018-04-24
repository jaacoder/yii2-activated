<?php

namespace Jaacoder\Yii2Activated\Helpers;

/**
 * Class AppMessage.
 */
class AppMessage
{
    const TYPE_SUCCESS = 'success';
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    
    public $message;
    public $type;
    public $propertyPath;
    
    /**
     * Constructor method.
     * 
     * @param string $message
     * @param string $type
     * @param string $propertyPath
     */
    function __construct($message, $type = self::TYPE_INFO, $propertyPath = null)
    {
        $this->type = $type;
        $this->message = $message;
        $this->propertyPath = $propertyPath;
    }
    
    /**
     * Factory for success message.
     * 
     * @param string $message
     * @param string $propertyPath
     * 
     * @return \static
     */
    public static function success($message, $propertyPath = null)
    {
        return new static($message, self::TYPE_SUCCESS, $propertyPath);
    }
    
    /**
     * Factory for info message.
     * 
     * @param string $message
     * @param string $propertyPath
     * 
     * @return \static
     */
    public static function info($message, $propertyPath = null)
    {
        return new static($message, self::TYPE_INFO, $propertyPath);
    }
    
    /**
     * Factory for warning message.
     * 
     * @param string $message
     * @param string $propertyPath
     * 
     * @return \static
     */
    public static function warning($message, $propertyPath = null)
    {
        return new static($message, self::TYPE_WARNING, $propertyPath);
    }
    
    /**
     * Factory for error message.
     * 
     * @param string $message
     * @param string $propertyPath
     * 
     * @return \static
     */
    public static function error($message, $propertyPath = null)
    {
        return new static($message, self::TYPE_ERROR, $propertyPath);
    }
}
