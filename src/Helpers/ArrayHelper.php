<?php

namespace Jaacoder\Yii2Activated\Helpers;

/**
 * Class ArrayHelper.
 */
class ArrayHelper
{
    /**
     * Flatten array.
     * Based on: http://sandbox.onlinephpfunctions.com/code/8228ea592eb006903b29e87fa4cd7d55e3103dfc
     * @param array $array
     * @param type $childPrefix
     * @param type $root
     * @param type $result
     * @return array
     */
    public static function flattenWithStringKeys(array $array, $childPrefix = '.', $root = '', $result = array())
    {
        // redundant with type hint
        //if(!is_array($array)) return $result;

        ### print_r(array(__LINE__, 'arr' => $array, 'prefix' => $childPrefix, 'root' => $root, 'result' => $result));

        foreach($array as $k => $v) {
            if((is_array($v) && !is_int(key($v))) || is_object($v)) $result = static::flattenWithStringKeys( (array) $v, $childPrefix, $root . $k . $childPrefix, $result);
            else $result[ $root . $k ] = $v;
        }
        return $result;
    }
}
