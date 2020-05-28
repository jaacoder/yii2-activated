<?php

namespace Jaacoder\Yii2Activated\Models;

use AdBar\Dot;
use Jaacoder\Yii2Activated\Helpers\HttpJsonException;
use Jaacoder\Yii2Activated\Helpers\ActException;
use yii\base\UserException;
use yii\web\HttpException;

/**
 * Throw exception if model udpate fails.
 */
trait UpdateExceptionTrait {

    protected $exceptionOnUpdate = true;
    protected $errorPath = 'model';
    protected $errorStatus = 400;

    /**
     * {@inheritdoc}
     */
    public function insert($runValidation = true, $attributes = null)
    {
        $result = parent::insert($runValidation = true, $attributes = null);

        $this->throwExceptionIfNeeded($result);
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        $result = parent::update($runValidation = true, $attributeNames = null);

        $this->throwExceptionIfNeeded($result);
        
        return $result;
    }

    /**
     * Throw exception if needed.
     * 
     * @param boolean $result 
     * @return void 
     * @throws HttpJsonException 
     */
    protected function throwExceptionIfNeeded($result)
    {
        if ($result || !$this->exceptionOnUpdate || empty($this->errors))
            return;

        $errors = $this->errors;

        // add errors with path
        if ($this->errorPath) {
            $dotErrors = new Dot();
            $dotErrors->set($this->errorPath, $this->errors);

            $errors = $dotErrors->get();
        }

        throw new HttpException($this->errorStatus, json_encode($errors));
    }
}