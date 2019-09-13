<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Exception;
use JsonSerializable;
use Sesgo\CoreYii\Models\ActiveRecordPro;
use yii\db\ActiveRecord;

/**
 * Class to return table (or alias) and column from ActiveRecord instance.
 */
class Column implements JsonSerializable {

    /** @var ActiveRecord */
    public $className;
    public $alias;

    public function __construct($className, $alias = '') {
        $this->className = $className;
        $this->alias = $alias;
    }

    public function __get($name) {
        $alias = (string) $this;

        if (is_subclass_of($this->className, ActiveRecordPro::class)) {
            $name = $this->className::mapping()[$name] ?? $name;
        }

        return "{{{$alias}}}.$name";
    }

    public function __toString() {
        return $this->alias ?: $this->className::tableName();
    }

    public function jsonSerialize() {
        return $this->__toString();
    }
}
