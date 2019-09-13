<?php

namespace Jaacoder\Yii2Activated\Models;

use Jaacoder\Yii2Activated\Helpers\Column;

/**
 * Add the possibility of using meta inside any class.
 */
trait ColumnTrait {

    /**
     * @return static
     */
    public static function col($alias = '') {
        return new Column(get_class(new static()), $alias);
    }

}