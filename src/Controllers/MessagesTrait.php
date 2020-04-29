<?php

namespace Jaacoder\Yii2Activated\Controllers;

use AdBar\Dot;

trait MessagesTrait
{
    /**
     * @var Dot
     */
    private $_dotMessages;
    
    /**
     * Init messages as an empty array if it is null.
     */
    public function initMessages($property = 'messages')
    {
        $this->{$property} = [];
        
        $this->_dotMessages = new Dot();
        $this->_dotMessages->setDataAsRef($this->{$property});
    }
    
    /**
     * Add success message.
     * 
     * @param string $message
     * @param string $key
     */
    public function addSuccessMessage($message, $key = '')
    {
        $key = 'success' . (empty($key) ? '' : '.' . $key);
        $this->_dotMessages->add($key, $message);
    }
    
    /**
     * Add info message.
     * 
     * @param string $message
     * @param string $key
     */
    public function addInfoMessage($message, $key = '')
    {
        $key = 'info' . (empty($key) ? '' : '.' . $key);
        $this->_dotMessages->add($key, $message);
    }
    
    /**
     * Add warning message.
     * 
     * @param string $message
     * @param string $key
     */
    public function addWarningMessage($message, $key = '')
    {
        $key = 'warning' . (empty($key) ? '' : '.' . $key);
        $this->_dotMessages->add($key, $message);
    }
    
    /**
     * Add error message.
     * 
     * @param string $message
     * @param string $key
     */
    public function addErrorMessage($message, $key = '')
    {
        $key = 'error' . (empty($key) ? '' : '.' . $key);
        $this->_dotMessages->add($key, $message);
    }
}
