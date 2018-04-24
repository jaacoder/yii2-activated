<?php

namespace Jaacoder\Yii2Activated\Models\Generators;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\gii\generators\model\Generator;
use function mb_strpos;
use function mb_substr;
use function Stringy\create as s;

set_time_limit(1800); // 30 min

/**
 * Description of ModelGenerator
 */
class ModelGenerator extends Generator
{

    /**
     * remove table prefix from class name
     * @var boolean
     */
    public $removeTablePrefixFromClassName = true;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['removeTablePrefixFromClassName', 'safe'];
        
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->baseClass = ActiveRecord::className();
        $this->queryBaseClass = ActiveQuery::className();
        $this->queryNs = $this->ns . '\queries';
        $this->generateQuery = true;
    }

    /**
     * @inheritodc
     * 
     * @param string $tableName
     * @param boolean $useSchemaName
     * @return string
     */
    protected function generateClassName($tableName, $useSchemaName = null)
    {
        $className = parent::generateClassName($tableName, $useSchemaName);

        // remove table prefix if present
        if ($this->removeTablePrefixFromClassName && (((string) s($tableName)->upperCamelize()) === $className)) {

            // find first underscore in table name
            $underscorePosition = mb_strpos($tableName, '_');

            // remove prefix before this position
            if ($underscorePosition) {
                return mb_substr($className, $underscorePosition);
            }
        }

        return $className;
    }

}
