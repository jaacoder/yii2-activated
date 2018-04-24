<?php

namespace Jaacoder\Yii2Activated\Models\Generators;

use Stringy\Stringy;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\ColumnSchema;
use yii\gii\generators\model\Generator;

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
     * remove column prefix from property name
     * @var boolean
     */
    public $removeColumnPrefixFromPropertyName = true;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['removeTablePrefixFromClassName', 'safe'];
        $rules[] = ['removeColumnPrefixFromPropertyName', 'safe'];

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
        if ($this->removeTablePrefixFromClassName && (((string) Stringy::create($tableName)->upperCamelize()) === $className)) {

            // find first underscore in table name
            $underscorePosition = mb_strpos($tableName, '_');

            // remove prefix before this position
            if ($underscorePosition) {
                return mb_substr($className, $underscorePosition);
            }
        }

        return $className;
    }

    /**
     * 
     * @param \Jaacoder\Yii2Activated\Models\Generators\yii\db\ColumnSchema $columnSchema
     * @return type
     */
    function getPropertyName(ColumnSchema $columnSchema)
    {
        $column = $columnSchema->name;

        // convert to camelcase
        // split column segments
        $parts = mb_split('_', $column);

        // glue each segment with proper case
        $property = array_shift($parts);

        if ($this->removeColumnPrefixFromPropertyName) {
            $property = array_shift($parts);
        }

        foreach ($parts as $part) {
            $property .= ucfirst($part);
        }

        return $property;
    }

}
