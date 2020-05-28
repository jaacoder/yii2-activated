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
            'GET' => ['index', 'edit'],
            'POST' => 'store',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
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

        // split pathParams at '/' to get an array of path params
        if (isset($params['pathParams'])) {
            $pathParams = mb_split('/', $params['pathParams']);
            unset($params['pathParams']);
        }

        // if $id is numeric, add as path params
        if (is_numeric($id)) {
            array_unshift($pathParams, $id);
            $id = '';
        }
        
        // if $id is empty, set a default action
        if (empty($id)) {
            $id = $this->defaultActions[Yii::$app->request->method] ?? 'index';

            if (is_array($id))
                $id = isset($pathParams[0]) ? end($id) : reset($id);
        }

        $method = 'action' . str_replace(' ', '', ucwords(preg_replace('/[-_]/', ' ', $id)));
        $reflectionClass = new ReflectionClass(get_called_class());

        if (method_exists($this, $method)) {
            foreach ($reflectionClass->getMethod($method)->getParameters() as $reflectionParameter) {
                /* @var $reflectionParameter ReflectionParameter */
                if (!isset($params[$reflectionParameter->name]) && !empty($pathParams)) {
                    $params[$reflectionParameter->name] = array_shift($pathParams);
                }
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
