<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Jaacoder\Yii2Activated\Helpers\AppMessage;
use Jaacoder\Yii2Activated\Helpers\Messages;


/**
 * Class MessagesTrait.
 */
trait MessagesTrait
{
    /**
     * Clear messages as an empty array.
     */
    public function clearMessages()
    {
        Messages::$messages = [];
    }
    
    /**
     * Init messages as an empty array if it is null.
     */
    public function initMessages()
    {
        if (Messages::$messages === null) {
            $this->clearMessages();
        }
    }
    
    /**
     * Add success message.
     * 
     * @param string $message
     */
    public function addSuccessMessage($message)
    {
        $this->initMessages();
        Messages::$messages[] = AppMessage::success($message);
    }
    
    /**
     * Add info message.
     * 
     * @param string $message
     */
    public function addInfoMessage($message)
    {
        $this->initMessages();
        Messages::$messages[] = AppMessage::info($message);
    }
    
    /**
     * Add warning message.
     * 
     * @param string $message
     */
    public function addWarningMessage($message)
    {
        $this->initMessages();
        Messages::$messages[] = AppMessage::warning($message);
    }
    
}
