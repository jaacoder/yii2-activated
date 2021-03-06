<?php

namespace Jaacoder\Yii2Activated\Models;

trait QueryExtraTrait
{
    /**
     * Returns a single active record model instance by a primary key or an array of column values.
     * 
     * @param mixed $condition
     * @return static ActiveRecord instance matching the condition, or new instance if no matches.
     */
    public static function findOneOrNew($condition)
    {
        $model = parent::findOne($condition);
        return $model ? $model : (new static());
    }
}
