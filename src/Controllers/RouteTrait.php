<?php

namespace Jaacoder\Yii2Activated\Controllers;

use ReflectionClass;
use Yii;

/**
 * @property array $defaultActions
 */
trait RouteTrait
{
    /**
     * Set default actions for each method if not present in url.
     */
    public function getDefaultActions()
    {
        return [
            'GET' => $this->defaultAction,
            'POST' => $this->defaultAction,
            'PUT' => $this->defaultAction,
            'PATCH' => $this->defaultAction,
            'DELETE' => $this->defaultAction,
        ];
    }
    
    /**
     * @param string $id
     * @param array $params
     * @return mixed
     */
    public function runAction($id, $params = array())
    {
        $pathParams = [];
        if (isset($params['_pathParams'])) {
            $pathParams = mb_split('/', $params['_pathParams']);
            unset($params['_pathParams']);
        }

        if (is_numeric($id)) {
            array_unshift($pathParams, $id);
            $id = $this->defaultActions[Yii::$app->request->method];

            if (is_array($id)) {
                $id = $id[1]; // index 1 because $id is not empty
            }
            //
        } elseif (empty($id)) {
            $id = $this->defaultActions[Yii::$app->request->method];

            if (is_array($id)) {
                $id = $id[0]; // index 0 because $id is empty
            }
        }

        $method = 'action' . str_replace(' ', '', ucwords(preg_replace('/[-_]/', ' ', $id)));
        $reflectionClass = new ReflectionClass(get_called_class());

        foreach ($reflectionClass->getMethod($method)->getParameters() as $reflectionParameter) {
            /* @var $reflectionParameter ReflectionParameter */
            if (!isset($params[$reflectionParameter->name]) && !empty($pathParams)) {
                $params[$reflectionParameter->name] = array_shift($pathParams);
            }
        }

        // call parent only if this trait function was not renamed
        $thisFunctionsName = debug_backtrace(0, 1)[0]['function'];
        if ($thisFunctionsName === __FUNCTION__) {
            return parent::runAction($id, $params);
            //
        } else { // else return [$id, $params]
            return [$id, $params];
        }
    }
}
