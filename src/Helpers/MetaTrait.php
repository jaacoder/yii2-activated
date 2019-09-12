<?php

namespace Jaacoder\Yii2Activated\Helpers;

/**
 * Add the possibility of using meta inside any class.
 */
trait MetaTrait {

    /**
     * @return static
     */
    public static function m() {
        return new Meta();
    }

}