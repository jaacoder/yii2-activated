<?php

namespace Jaacoder\Yii2Activated\Controllers;

use yii\web\UrlManager;

class ActUrlManager extends UrlManager
{
    public $modules = [];

    /**
     * {@inheritdoc}
    */
    public function init()
    {
        $this->generateRulesForModules();
        parent::init();
    }

    /**
     * Generate generic routes for defined modules.
     *
     * @return void
     */
    public function generateRulesForModules()
    {
        rsort($this->modules, SORT_STRING);

        foreach ($this->modules as $module) {
            $this->rules[] = [
                'pattern' => $module . '/<controller:[a-z][\w-]*>/<method:[a-z][\w-]*>/<pathParams:.*>',
                'route' => $module . '/<controller>/<method>',
                'defaults' => ['method' => null, 'pathParams' => null],
            ];
        }

        $this->rules[] = [
            'pattern' => '<controller:[a-z][\w-]*>/<method:[a-z][\w-]*>/<pathParams:.*>',
            'route' => '<controller>/<method>',
            'defaults' => ['method' => null, 'pathParams' => null],
        ];
    }
}
