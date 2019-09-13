<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Reflection;
use ReflectionClass;

/**
 * Add the possibility of using meta inside any class.
 */
trait MetaTrait {

    /**
     * @return static
     */
    public static function m($prefix = '') {
        return new Meta($prefix);
    }

}