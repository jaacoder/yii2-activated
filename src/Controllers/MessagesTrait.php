<?php

namespace Jaacoder\Yii2Activated\Controllers;

use AdBar\Dot;
use yii\base\ActionEvent;
use yii\web\Controller;

/**
 * @property array $messages
 */
trait MessagesTrait
{
    /**
     * @var string
     */
    private $_messages;

    /**
     * @var boolean
     */
    private $_messagesOn = null;

    /**
     * @var Dot
     */
    private $_dotMessages;
    
    /**
     * Init messages as an empty array if it is null.
     */
    public function initMessages()
    {
        $this->setMessages([]);

        // create event handler to deal with messages after action
        $this->on(Controller::EVENT_AFTER_ACTION, function(ActionEvent $event) {
            
            if (!$this->_messagesOn // skip if user don't want messages
                || !is_array($event->result)  // skip is result is not array
                || (!empty($event->result) && is_numeric(array_keys($event->result)[0])) // skip if result is numeric key array
                || isset($event->result['messages'])) // skip if key 'messages' already setted up

                return;

            $event->result['messages'] = (object) $this->_messages;
        });
    }

    /**
     * Enable automatic response with messages.
     * @return void
     */
    public function messagesOn($force = true)
    {
        if (!$force && $this->_messagesOn === false)
            return;

        $this->_messagesOn = true;
    }

    /**
     * Disable automatic response with messages.
     * @return void
     */
    public function messagesOff()
    {
        $this->_messagesOn = false;
    }
    
    /**
     * Add success message.
     * 
     * @param string $message
     * @param string $key
     */
    public function addSuccessMessage($message, $key = '')
    {
        $this->messagesOn(false);

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
        $this->messagesOn(false);

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
        $this->messagesOn(false);

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
        $this->messagesOn(false);

        $key = 'error' . (empty($key) ? '' : '.' . $key);
        $this->_dotMessages->add($key, $message);
    }
    
    /**
     * Return messages.
     * 
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }
    
    /**
     * Setter for messages.
     * 
     * @return $this
     */
    public function setMessages($messages)
    {
        $this->_messages = $messages;
        
        $this->_dotMessages = new Dot();
        $this->_dotMessages->setDataAsRef($this->_messages);

        return $this;
    }
}
