<?php

namespace Jaacoder\Yii2Activated\Controllers;

use AdBar\Dot;
use Yii;

/**
 * Trait RequestTrait.
 *
 * @author jaacoder
 */
trait RequestTrait
{
    /**
     * Helper method to access post param.
     * 
     * @staticvar type $postDotNotation
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed
     */
    public function post($name = null, $defaultValue = null)
    {
        static $postDotNotation = null;

        if ($postDotNotation === null) {
            $post = Yii::$app->request->post(null, []);
            $postDotNotation = new Dot($post);
        }

        return $postDotNotation->get($name, $defaultValue);
    }
}
