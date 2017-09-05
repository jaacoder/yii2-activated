<?php

namespace Jaacoder\Yii2Activated\Controllers;

use ReflectionClass;
use yii\base\ActionEvent;
use yii\base\UnknownPropertyException;
use yii\web\Controller;
use function mb_substr;

/**
 * Trait AutoResponseTrait.
 *
 * @author jaacoder
 */
trait AutoResponseTrait
{

    private $_dynamicProperties = [];

    /**
     * Init auto response trait.
     */
    public function initAutoResponse()
    {
        $this->on(static::EVENT_AFTER_ACTION, function(ActionEvent $event) {

            if ($event->result !== null) {
                return;
            }

            // build result based on response attributes
            $newResult = [];
            foreach ($this->getResponseAttributes() as $responseAttribute) {
                if (isset($this->_dynamicProperties[$responseAttribute])) {
                    $newResult[$responseAttribute] = $this->$responseAttribute;
                }
            }

            $event->result = (object) $newResult;
        });
    }

    /**
     * Magic setter.
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        try {
            $this->_dynamicProperties[$name] = true;
            parent::__set($name, $value);
            //
        } catch (UnknownPropertyException $e) {
            $this->$name = $value;
        }
    }

    /**
     *
     * @staticvar array $docProperties
     * @param ReflectionClass $reflectionClass
     */
    private function getDocProperties(ReflectionClass $reflectionClass)
    {
        static $docProperties = [];
        $className = $reflectionClass->name;

        if (!isset($docProperties[$className])) {
            $matches = [];
            preg_match_all('/\s+@property\s+(\w+\[?\]?)\s+\$(\w+)\s*/', $reflectionClass->getDocComment(), $matches, PREG_SET_ORDER);
            $docProperties[$className] = array_column($matches, 1, 2);
        }

        return $docProperties[$className];
    }

    /**
     *
     * @staticvar array $propertiesTypes
     * @param type $object
     * @param type $property
     * @param type $removeBrackets
     * @return type
     */
    private function getDocPropertyType($object, $property, $removeBrackets = false)
    {
        $docProperties = $this->getDocProperties(new ReflectionClass($object));
        $type = $this->getDocProperties(isset($docProperties[$property]) ? $docProperties[$property] : null);

        if ($removeBrackets && $type !== null) {
            if (mb_substr($type, -2) === '[]') {
                $type = mb_substr($type, 0, -2);
            }
        }

        return $type;
    }

    /**
     * Retorna os atributos de resposta.
     *
     * @return array
     */
    public function getResponseAttributes()
    {
        $responseAttributes = [];

        // find base parent class
        $selfReflectionClass = new ReflectionClass(Controller::className());
        $baseParentClass = $selfReflectionClass->getParentClass()->getName();

        // loop until base parent class reading all doc properties
        $reflectionClass = new ReflectionClass(get_called_class());

        while ($reflectionClass->getName() !== $baseParentClass) {

            // save doc properties
            $responseAttributes = array_merge($responseAttributes, array_keys($this->getDocProperties($reflectionClass)));

            // go to parent class
            $reflectionClass = $reflectionClass->getParentClass();
        }

        return $responseAttributes;
    }

}
