<?php

namespace Jaacoder\Yii2Activated\Models;

use Jaacoder\Yii2Activated\Models\Column;

/**
 * Add the possibility of using meta inside any class.
 */
trait ColumnTrait {

    /**
     * @return $this
     */
    public static function col($alias = null) {
        return new Column(get_class(new static()), $alias);
    }

}