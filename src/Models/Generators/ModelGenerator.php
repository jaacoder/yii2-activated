<?php

namespace Jaacoder\Yii2Activated\Models\Generators;

use Jaacoder\Yii2Activated\Models\ActiveRecord;
use Jaacoder\Yii2Activated\Models\Queries\ActiveQuery;
use yii\gii\generators\model\Generator;

set_time_limit(1800); // 30 min

/**
 * Description of ModelGenerator
 */
class ModelGenerator extends Generator
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->baseClass = ActiveRecord::className();
        $this->queryBaseClass = ActiveQuery::className();
        $this->queryNs = $this->ns . '\queries';
        $this->generateQuery = true;
        
        parent::init();
    }
}
