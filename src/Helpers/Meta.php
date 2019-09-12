<?php

namespace Jaacoder\Yii2Activated\Helpers;

/**
 * Class Meta to save the path navigated inside one class.
 */
class Meta implements \JsonSerializable {

    private $path = '';
    private $name = '';

    public function __construct($path = '') {
        $this->path = $path;
    }

    public function __get($name) {
        return new Meta($this->pathAndName($name));
    }

    public function __toString() {
        return $this->pathAndName($this->name);
    }

    private function pathAndName($name = '') {
        if (!$name) {
            return $this->path;
        }

        if ($this->path) {
            return $this->path . '.' . $name;
        }

        return $name;
    }

    public function jsonSerialize() {
        return $this->__toString();
    }

}
