<?php

namespace Jaacoder\Yii2Activated\Controllers;

use AdBar\Dot;
use Yii;
use yii\web\Request;
use yii\web\Response;

/**
 * @property Request $request
 * @property Response $response
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

    /**
     * @return Request
     */
    public function getRequest()
    {
        return Yii::$app->request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return Yii::$app->response;
    }
}
