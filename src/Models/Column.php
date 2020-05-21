<?php

namespace Jaacoder\Yii2Activated\Models;

use Exception;
use JsonSerializable;
use Sesgo\CoreYii\Models\ActiveRecordPro;
use yii\base\Object;
use yii\db\ActiveRecord;

/**
 * Class to return table (or alias) and column from ActiveRecord instance.
 */
class Column implements JsonSerializable {

    /** @var ActiveRecord|Object */
    public $className;
    public $alias;

    public function __construct($className, $alias = null) {
        $this->className = $className;
        $this->alias = $alias;
    }

    public function __get($name) {
        $alias = (string) $this;

        $modelClass = $this->className;
        if (method_exists($modelClass, 'mapping')) {
            $mapping = $modelClass::mapping();

            if (is_array($mapping))
                $name = $mapping[$name] ?? $name;
        }

        return empty($alias) ? $name : "$alias.$name";
    }

    public function __toString() {
        return $this->alias === null ? $this->className::tableName() : $this->alias;
    }

    public function jsonSerialize() {
        return $this->__toString();
    }
}
