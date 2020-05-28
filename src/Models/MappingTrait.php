<?php

namespace Jaacoder\Yii2Activated\Models;

use yii\db\ActiveRecord;

trait MappingTrait
{
    // whether or not to show extra fields with fields() method
    protected $autoExtraFields = true;

    /**
     * Property to column mapping.
     * 
     * @return array
     */
    public static function mapping()
    {
        return [];
    }
    
    /**
     * Flipped mapping.
     * @return array
     */
    public static function flippedMapping()
    {
        static $flippedMapping = null;
        
        if ($flippedMapping === null) {
            $flippedMapping = array_flip(static::mapping());
        }
        
        return $flippedMapping;
    }
    
    /**
     * Magic __get.
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $mapping = static::mapping();
        return parent::__get(isset($mapping[$name]) ? $mapping[$name] : $name);
    }
    
    /**
     * Magic __set.
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $mapping = static::mapping();
        parent::__set(isset($mapping[$name]) ? $mapping[$name] : $name, $value);
    }
    
    /**
     * Map array keys to columns.
     * 
     * @param array $values
     * @return array
     */
    public static function mapToColumns($values)
    {
        $mapping = static::mapping();
        
        if (!is_array($values)) {
            return $values;
        }
        
        $newValues = [];
        foreach ($values as $key => $value) {
            $newKey = isset($mapping[$key]) ? $mapping[$key] : $key;
            $newValues[$newKey] = $value;
        }
        
        return $newValues;
    }
    
    /**
     * Map array keys to properties.
     * 
     * @param array $values
     * @return array
     */
    public static function mapToProperties($values)
    {
        $flippedMapping = static::flippedMapping();
        
        if (!is_array($values)) {
            return $values;
        }
        
        $newValues = [];
        foreach ($values as $key => $value) {
            $newKey = isset($flippedMapping[$key]) ? $flippedMapping[$key] : $key;
            $newValues[$newKey] = $value;
        }
        
        return $newValues;
    }
    
    /**
     * @inheritdoc
     * 
     * @param mixed $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes(static::mapToColumns($values), $safeOnly);
    }
    
    /**
     * @inheritdoc
     * @return array
     */
    public function getErrors($attribute = null)
    {
        return static::mapToProperties(parent::getErrors());
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = static::mapToProperties(parent::fields());

        if ($this->autoExtraFields)
            return array_merge($fields, parent::extraFields());

        return $fields;
    }
}
