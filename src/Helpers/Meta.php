<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Exception;
use Sesgo\CoreYii\Models\ActiveRecordPro;

/**
 * Class Meta to save the path navigated inside one class.
 */
class Meta implements \JsonSerializable {

    public $path = '';
    public $name = '';

    public function __construct($path = '') {
        $this->path = $path;
    }

    public function __get($name) {
        if ($this->path) {
            $this->path .= '.' . $name;

        } else {
            $this->path = $name;
        }

        return $this;
    }

    public function __toString() {
        return $this->path;
    }

    public function jsonSerialize() {
        return $this->__toString();
    }

}
