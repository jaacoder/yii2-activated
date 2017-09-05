<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator Jaacoder\Yii2Activated\Models\Generators\ModelGenerator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

if (!function_exists('getPropertyName')) {
    function getPropertyName(yii\db\ColumnSchema $columnSchema)
    {
        $column = $columnSchema->name;

        // convert to camelcase

        // split column segments
        $parts = mb_split('_', $column);

        // glue each segment with proper case
        $property = array_shift($parts);
        foreach ($parts as $part) {
            $property .= ucfirst($part);
        }
        
        return $property;
        }
}

if (!function_exists('normalizeRelation')) {
    function normalizeRelation($relation)
    {
        $firstTwoLetters = mb_substr($relation, 0, 2);
        $thirdLetter = $relation[2];
        
        // starts with 'id' and the third letter is upper case? remove 'id'
        if (in_array($firstTwoLetters, ['id', 'Id']) && $thirdLetter === mb_strtoupper($thirdLetter)) {
            return mb_substr($relation, 2);
        }

        return $relation;
    }
}

echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;

/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($tableSchema->columns as $column):?>
 * @property <?= "{$column->phpType} \$" . getPropertyName($column) . "\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst(normalizeRelation($name)) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [<?= "\n            " . implode(",\n            ", $rules) . ",\n        " ?>];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
        ];
    }

    /**
     * @inheritdoc
     */
    public static function mapping()
    {
        return [
<?php foreach ($tableSchema->columns as $column):?>
            <?= "'" . getPropertyName($column) . "' => '$column->name',\n" ?>
<?php endforeach; ?>
        ];
    }
<?php foreach ($relations as $name => $relation): ?>

    /**
     * @return \yii\db\ActiveQuery
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>
<?php if ($queryClassName): ?>
<?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
?>
    /**
     * @inheritdoc
     * @return <?= $queryClassFullName ?> the active query used by this AR class.
     */
    public static function find()
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>
}
