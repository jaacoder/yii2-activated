<?php

namespace Jaacoder\Yii2Activated\Controllers\Rest;

use AdBar\Dot;
use Jaacoder\Yii2Activated\Helpers\Messages;
use Jaacoder\Yii2Activated\Helpers\MessagesTrait;
use Jaacoder\Yii2Activated\Helpers\TransactionHelper;
use ReflectionClass;
use ReflectionParameter;
use stdClass;
use Yii;
use yii\base\UnknownPropertyException;
use yii\filters\AccessControl;
use yii\rest\Action;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Description of ControllerPro
 * 
 * @property string $view
 * @property string $_redirect
 * 
 */
class Controller extends \yii\rest\Controller
{

    use MessagesTrait;

    private $dynamicProperties = [];

    /**
     * @var array
     */
    public $defaultActions = [];

    /**
     * Initializes the object.
     */
    public function init()
    {
        // default actions
        if (empty($this->defaultActions)) {
            $this->defaultActions = [
                'GET' => $this->defaultAction,
                'POST' => $this->defaultAction,
                'PUT' => $this->defaultAction,
                'PATCH' => $this->defaultAction,
                'DELETE' => $this->defaultAction,
            ];
        }

        // call parent method
        parent::init();
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [],
                        'roles' => ['@'],
                        'matchCallback' => function($rule, $action) {
                            return $this->matchCallback($rule, $action);
                        },
                    ],
                    empty($this->safeActions()) ? [] : [
                        'allow' => true,
                        'actions' => $this->safeActions(),
                        'roles' => ['?'],
                        'matchCallback' => function($rule, $action) {
                            return $this->matchCallback($rule, $action);
                        },
                    ],
                ],
                'denyCallback' => function($rule, $action) {
                    return $this->denyCallback($rule, $action);
                },
            ],
        ]);
    }

    /**
     * Magic setter.
     * 
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        try {
            $this->dynamicProperties[$name] = true;
            parent::__set($name, $value);
            //
        } catch (UnknownPropertyException $e) {
            $this->$name = $value;
        }
    }

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
     * Helper method to access post param and return as object.
     * 
     * @deprecated since version 0.9.5
     * @param string $name
     * @param string $className
     * @return object
     */
    public function postObject($name = null, $className = null, $returnEmptyInstance = true)
    {
        if ($className === null) {
            $className = get_class(new stdClass());
        }

        $value = $this->post($name, null);
        $object = new $className();

        if (!is_array($value)) {
            if ($returnEmptyInstance) {
                return $object;
                //
            } else {
                return null;
            }
        }

        return $this->populateObject($object, $value);
    }

    /**
     * @deprecated since version 0.9.5
     * @param object $object
     * @param array $data
     */
    private function populateObject($object, $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $propertyType = $this->getDocPropertyType($object, $key, /* $removeBrackets = */ true);
                if ($propertyType === null) {
                    $propertyType = get_class(new stdClass());
                }

                $isNumericArray = is_int(key($value));

                if ($isNumericArray) {
                    foreach ($value as $i => $innerValue) {
                        $innerObject = new $propertyType;
                        $value[$i] = $this->populateObject($innerObject, $value);
                    }
                } else {
                    $innerObject = new $propertyType;
                    $value = $this->populateObject($innerObject, $value);
                }
            }

            $object->$key = $value;
        }

        return $object;
    }

    /**
     * 
     * @staticvar array $docProperties
     * @param ReflectionClass $reflectionClass
     */
    private function getDocProperties(ReflectionClass $reflectionClass)
    {
        static $docProperties = [];
        $className = $reflectionClass->name;

        if (!isset($docProperties[$className])) {
            $matches = [];
            preg_match_all('/\s+@property\s+(\w+\[?\]?)\s+\$(\w+)\s*/', $reflectionClass->getDocComment(), $matches, PREG_SET_ORDER);
            $docProperties[$className] = array_column($matches, 1, 2);
        }

        return $docProperties[$className];
    }

    /**
     * 
     * @staticvar array $propertiesTypes
     * @param type $object
     * @param type $property
     * @param type $removeBrackets
     * @return type
     */
    private function getDocPropertyType($object, $property, $removeBrackets = false)
    {
        $docProperties = $this->getDocProperties(new ReflectionClass($object));
        $type = $this->getDocProperties(isset($docProperties[$property]) ? $docProperties[$property] : null);

        if ($removeBrackets && $type !== null) {
            if (mb_substr($type, -2) === '[]') {
                $type = mb_substr($type, 0, -2);
            }
        }

        return $type;
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

        return parent::runAction($id, $params);
    }

    /**
     * @inheritdoc
     * 
     * @param Action $action the action just executed.
     * @param mixed $result the action return result.
     * @return mixed
     */
    public function afterAction($action, $result)
    {
        $serializedResult = parent::afterAction($action, $result);

        // commit all db active transactions
        TransactionHelper::commitTransactions();

        if ($serializedResult === null) {

            // build result based on response attributes
            $newResult = [];
            foreach ($this->getResponseAttributes() as $responseAttribute) {
                if (isset($this->dynamicProperties[$responseAttribute])) {
                    $newResult[$responseAttribute] = $this->$responseAttribute;
                }
            }

            // add messages to result
            $newResult['messages'] = Messages::$messages;

            return (object) $newResult;

            //
        } else {
            return $serializedResult;
        }
    }

    /**
     * Retorna os atributos de resposta.
     * 
     * @return array
     */
    public function getResponseAttributes()
    {
        $responseAttributes = [];

        // find base parent class
        $selfReflectionClass = new ReflectionClass(Controller::className());
        $baseParentClass = $selfReflectionClass->getParentClass()->getName();
        
        // loop until base parent class reading all doc properties
        $reflectionClass = new ReflectionClass(get_called_class());
        
        while ($reflectionClass->getName() !== $baseParentClass) {
            
            // save doc properties
            $responseAttributes = array_merge($responseAttributes, array_keys($this->getDocProperties($reflectionClass)));
            
            // go to parent class
            $reflectionClass = $reflectionClass->getParentClass();
        }

        return $responseAttributes;
    }

    /**
     * Redirect to other view route or url.
     * 
     * @param mixed $url
     * @return null
     */
    public function redirectView($url)
    {
        $this->_redirect = $url;
        return;
    }

    /**
     * Default method index .
     * 
     * @param mixed $id
     */
    public function actionIndex()
    {
        
    }

    /**
     * Return actions with public permission.
     * 
     * @return array
     */
    public function safeActions()
    {
        return [];
    }

    /**
     * Called to determine if a rule should be applied.
     * 
     * @param type $rule
     * @param type $action
     */
    public function matchCallback($rule, $action)
    {
        return true;
    }

    /**
     * Called when a rule is denied.
     * 
     * @param string rule
     * @param string $action
     */
    public function denyCallback($rule, $action)
    {
        if (\Yii::$app->user->identity === null) {
            throw new UnauthorizedHttpException();
            //
        } else {
            throw new ForbiddenHttpException();
        }
    }

}
