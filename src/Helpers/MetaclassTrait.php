<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Jaacoder\Yii2Activated\Models\Metaclass;

/**
 * Description of MetaclassTrait
 */
trait MetaclassTrait
{
    /**
     * Alias 'p' means 'property'.
     * 
     * @return static
     */
    public static function p()
    {
        static $metaClass = null;
        
        if ($metaClass === null) {
            $metaClass = new Metaclass(get_called_class());
        }
        
        return $metaClass;
    }
}
