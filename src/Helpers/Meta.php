<?php

namespace Jaacoder\Yii2Activated\Helpers;

use ArrayAccess;
use phpDocumentor\Reflection\Types\Boolean;
use yii\base\Arrayable;

/**
 * Class Meta to save the path navigated inside one class.
 */
class Meta implements \JsonSerializable, ArrayAccess {

    public $path = '';
    public $name = '';

    public function __construct($path = '') {
        $this->path = $path;
    }

    public function __get($name) {
        if ($this->path) {
            return new Meta($this->path . '.' . $name);
        }

        return new Meta($name);
    }

    public function __toString() {
        return $this->path;
    }

    public function jsonSerialize() {
        return $this->__toString();
    }

    public function offsetExists ($offset): bool
    {
        return true;
    }

    public function offsetGet ($offset)
    {
        return $this;
    }

    public function offsetSet ($offset, $value) : void
    {
    }

    public function offsetUnset ($offset) : void
    {
    }

}
