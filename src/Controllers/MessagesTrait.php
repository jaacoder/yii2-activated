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
    private $_messages = null;

    /**
     * @var boolean
     */
    private $_messagesOn = true;

    /**
     * @var Dot
     */
    private $_dotMessages;

    /**
     * Init messages as an empty array if it is null.
     */
    public function initMessages()
    {
        // create event handler to deal with messages after action
        $this->on(Controller::EVENT_AFTER_ACTION, function (ActionEvent $event) {

            $result = $event->result;

            if ($result === null)
                $result = [];

            if (!$this->_messagesOn// skip if user don't want messages
                 || !is_array($result)
                 || (!empty($result) && is_numeric(array_keys($result)[0])) // skip if result is numeric key array
                 || isset($result['messages']) // skip if key 'messages' already setted up
                 || is_null($this->messages))

                return;

            $result['messages'] = (object) $this->_messages;

            $event->result = $result;
        });
    }

    /**
     * Enable automatic response with messages.
     * @return void
     */
    public function clearMessages()
    {
        $this->setMessages([]);
    }

    /**
     * Enable automatic response with messages.
     * @return void
     */
    public function messagesOn($force = true)
    {
        if (!$force && $this->_messagesOn === false) {
            return;
        }

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
     * Init Dot if needed.
     * @return void 
     */
    public function initDotIfNeeded()
    {
        if (!$this->_dotMessages)
            $this->setMessages([]);
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
        $this->initDotIfNeeded();
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
        $this->initDotIfNeeded();
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
        $this->initDotIfNeeded();
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
        $this->initDotIfNeeded();
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
