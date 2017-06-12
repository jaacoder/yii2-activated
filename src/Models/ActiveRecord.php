<?php

namespace Jaacoder\Yii2Activated\Models;

use Carbon\Carbon;
use Jaacoder\Yii2Activated\Helpers\AppException;
use Jaacoder\Yii2Activated\Helpers\AppMessage;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Description of ActiveRecordPro
 */
class ActiveRecord extends ActiveRecord
{
//    use MetaclassTrait;
    
    /**
     * property path in view
     * managed by controller
     * 
     * @var string
     */
    public $propertyPath = 'model';
    
    /**
     * indicate if an exception
     * should be thrown if it has errors
     * 
     * @var bool
     */
    public $throwExceptionAfterValidate = true;
    
    /**
     * save exception to be thrown lately
     * 
     * @var AppMessages
     */
    public $messages = [];
    
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
     * @inheritdoc
     *
     * The default implementation returns the names of the columns whose values have been populated into this record.
     */
    public function fields()
    {
        return array_merge(static::mapToProperties(parent::fields()), parent::extraFields());
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
     * Map array keys to columns.
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
     * 
     * @param bool $insert
     */
    public function beforeSave($insert)
    {
        $result = parent::beforeSave($insert);
        
        if ($result) {
            $this->requireTransaction();
        }
        
        return $result;
    }
    
    public function afterValidate()
    {
        parent::afterValidate();
        
        // if has erros, throw exception
        if ($this->hasErrors()) {
            
            $messages = [];
            
            $propertyPathPrefix = $this->propertyPath;
            if (!empty($propertyPathPrefix)) {
                $propertyPathPrefix .= '.';
            }
            
            $flippedMapping = static::flippedMapping();
            
            foreach ($this->errors as $field => $errors) {
                foreach ($errors as $error) {
                    $messages[] = AppMessage::error($error, $propertyPathPrefix . (isset($flippedMapping[$field]) ? $flippedMapping[$field] : $field));
                }
            }
            
            $this->messages = $messages;
            
            if ($this->throwExceptionAfterValidate) {
                throw AppException::create($messages);
            }
        }
    }
    
    /**
     * Return created_at attribute.
     * 
     * @staticvar string $createdAtAttribute
     * @staticvar boolean $createdAtAttributeRead
     * @return string
     */
    public static function getCreatedAtAttribute()
    {
        static $createdAtAttribute = null;
        static $createdAtAttributeRead = false;
        
        if (!$createdAtAttributeRead) {
            
            $createdAtSuffix = 'criado_em';
            $createdAtLength = mb_strlen($createdAtSuffix);
            
            // find the name of updatedAt and createdAt attributes
            foreach (static::mapping() as $key => $value) {
                if (mb_substr($value, -$createdAtLength) === $createdAtSuffix) {

                    $createdAtAttribute = $value;
                    break;
                }
            }
            
            $createdAtAttributeRead = true;
        }
        
        return $createdAtAttribute;
    }
    
    /**
     * Return updated_at attribute.
     * 
     * @staticvar string $updatedAtAttribute
     * @staticvar boolean $updatedAtAttributeRead
     * @return string
     */
    public static function getUpdatedAtAttribute()
    {
        static $updatedAtAttribute = null;
        static $updatedAtAttributeRead = false;
        
        if (!$updatedAtAttributeRead) {
            
            $updatedAtSuffix = 'alterado_em';
            $updatedAtLength = mb_strlen($updatedAtSuffix);
            
            // find the name of updatedAt and updatedAt attributes
            foreach (static::mapping() as $key => $value) {
                if (mb_substr($value, -$updatedAtLength) === $updatedAtSuffix) {

                    $updatedAtAttribute = $value;
                    break;
                }
            }
            
            $updatedAtAttributeRead = true;
        }
        
        return $updatedAtAttribute;
    }
    
    /**
     * Behaviors.
     * 
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        $behaviors['timestamp'] = [
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => static::getCreatedAtAttribute(),
            'updatedAtAttribute' => static::getUpdatedAtAttribute(),
            'value' => function() {
                return Carbon::now()->toDateTimeString();
            },
        ];
        
        return $behaviors;
    }

    /**
     * Ensure a transaction is active.
     */
    public function requireTransaction()
    {
        if ($this->getDb()->transaction === null || !$this->getDb()->transaction->isActive) {
            $this->getDb()->beginTransaction();
        }
    }
    
    /**
     * Returns a single active record model instance by a primary key or an array of column values.
     * 
     * @param mixed $condition
     * @return static ActiveRecord instance matching the condition, or new instance if nothing matches.
     */
    public static function findOneOrNew($condition)
    {
        $model = parent::findOne($condition);
        return $model ? $model : (new static());
    }
    
    /**
     * Return the custom alias or the relation name itself if none is configured.
     * 
     * @param string $relation
     * @param bool $returnRelation
     * @return string
     */
    protected function getRelationAlias($relation, $returnRelation = true)
    {
        $backtraces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);
        $alias = null;
        
        foreach ($backtraces as $backtrace) {
            if ($backtrace['object'] == $this) {
                continue;
            }
            
            if (get_class($backtrace['object']) !== get_class(static::find())) {
                break;
            }
            
            $alias = isset($backtrace['object']->relationAliases[$relation]) ? $backtrace['object']->relationAliases[$relation] : null;
        }
        
        if ($alias) {
            return $alias;
        } else {
            return $returnRelation ? $relation : null;
        }
    }

}
