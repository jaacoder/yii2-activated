<?php

namespace Jaacoder\Yii2Activated\Models\Queries;

use Jaacoder\Yii2Activated\Models\ActiveRecord;
use yii\db\Connection;

/**
 * Class ActiveQueryPro.
 * 
 * @property string $alias
 * @property string $escapedAlias
 * 
 * @method $this selectId($options = [])
 * @method $this id($operator = null, $value = null, $options = [])
 * @method $this orderById($sort = SORT_ASC, $options = [])
 * @method $this groupById($options = [])
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    const DEFAULT_JOIN_TYPE = 'LEFT JOIN';

    private $_alias;
    protected $filterType = 'where';
    public $relationAliases = [];
    
    protected $operators = ['=', '<', '<=', '>', '>=', '<>', '!=', 'in', 'not in', 'like', 'ilike', 'not like', 'not ilike', 'is', 'is not'];

    /**
     * @var ActiveQuery
     */
    protected $originalQuery = null; // if this is a clone whitin a parenthesis
    protected $originalQueryOperator = null; // 'and' or 'or' relational operator

    public function init()
    {
        parent::init();

        // set default alias for this query
        if (empty($this->_alias)) {
            $modelClass = $this->modelClass;
            $this->_alias = $modelClass::tableName();
        }
    }

    /**
     * Magic __call.
     * 
     * @param string $name
     * @param array $params
     */
    public function __call($name, $params)
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        $mapping = $modelClass::mapping();
        
        // select
        if (substr($name, 0, 6) === 'select') {
            $column = lcfirst(substr($name, 6));
            $realColumn = isset($mapping[$column]) ? $mapping[$column] : $column;
            return call_user_func_array([$this, 'doSelect'], array_merge([$realColumn], $params));
            //
            // orderBy
        } else if (($sevenFirstChars = substr($name, 0, 7)) === 'orderBy') {
            $column = lcfirst(substr($name, 7));
            $realColumn = isset($mapping[$column]) ? $mapping[$column] : $column;
            return call_user_func_array([$this, 'doOrderBy'], array_merge([$realColumn], $params));
            //
            // groupBy
        } else if ($sevenFirstChars === 'groupBy') {
            $column = lcfirst(substr($name, 7));
            $realColumn = isset($mapping[$column]) ? $mapping[$column] : $column;
            return call_user_func_array([$this, 'doGroupBy'], array_merge([$realColumn], $params));
            //
            // with
        } else if (substr($name, 0, 4) === 'with') {
            $relation = lcfirst(substr($name, 4));
            return call_user_func_array([$this, 'doWith'], array_merge([$relation], $params));
            //
            // joinWith
        } else if (substr($name, 0, 8) === 'joinWith') {
            $relation = lcfirst(substr($name, 8));
            return call_user_func_array([$this, 'doJoinWith'], array_merge([$relation], $params));
            //
            // filter
        } else if (isset($mapping[$name]) || in_array($name, $modelClass::getTableSchema()->columnNames)) {
            $realColumn = isset($mapping[$name]) ? $mapping[$name] : $name;
            return call_user_func_array([$this, 'doFilter'], array_merge([$realColumn], $params));
        }

        return parent::__call($name, $params);
    }

    /**
     * @return static
     */
    public function andOpen()
    {
        return $this->open('and');
    }

    /**
     * @return static
     */
    public function orOpen()
    {
        return $this->open('or');
    }

    /**
     * @return static
     */
    public function open($operator = 'and')
    {
        $clone = clone $this;

        $clone->originalQuery = $this;
        $clone->originalQueryOperator = $operator;
        $clone->where = null;
        $clone->join = null;

        return $clone;
    }

    /**
     * @return static
     */
    public function close()
    {
        $original = $this->originalQuery;

        if (!empty($this->where)) {
            $method = $this->originalQueryOperator . 'Where';
            $original->{$method}($this->where);
        }

        if (!empty($this->on)) {
            $method = $this->originalQueryOperator . 'OnCondition';
            $original->{$method}($this->on);
        }

        return $original;
    }

    /**
     * Select column
     * @param string $column
     * @param array $params
     * 
     * @return static
     */
    protected function doSelect($column)
    {
        $params = func_get_args();
        array_shift($params); // shift '$column'
        
        $options = array_merge(['table' => $this->alias], isset($params[0]) ? $params[0] : []);

        $columnExpression = "{{{$options['table']}}}.$column";
        $decoratedColumnExpression = $this->decorateColumnExpression($columnExpression, $options);

        if (key_exists('alias', $options)) {
            return $this->addSelect([$options['alias'] => $decoratedColumnExpression]);
            //
        } else {
            return $this->addSelect($decoratedColumnExpression);
        }
    }

    /**
     * Select column
     * @param string $relation
     * @param array $params
     * 
     * @return static
     */
    protected function doWith($relation)
    {
        $params = func_get_args();
        array_shift($params); // shift '$relation'
        
        $callback = isset($params[0]) ? $params[0] : null;

        return $this->with(is_callable($callback) ? [$relation => $callback] : $relation);
    }

    /**
     * Select column
     * @param string $relation
     * @param array $params
     * 
     * @return static
     */
    protected function doJoinWith($relation)
    {
        $params = func_get_args();
        array_shift($params); // shift '$relation'
        
        $callback = isset($params[0]) ? $params[0] : null;
        $eagerLoading = isset($params[1]) ? $params[1] : true;
        $joinType = isset($params[2]) ? $params[2] : self::DEFAULT_JOIN_TYPE;
        $options = isset($params[3]) ? $params[3] : [];
        
        if (isset($options['alias'])) {
            $this->relationAliases[$relation] = $options['alias'];
            $relation = "$relation $options[alias]";
        }
        
        return $this->joinWith(is_callable($callback) ? [$relation => $callback] : $relation, $eagerLoading, $joinType);
    }

    /**
     * Filter column
     * @param string $column
     * @param array $params
     * 
     * @return static
     */
    protected function doFilter($column)
    {
        $params = func_get_args();
        array_shift($params); // shift '$column'
        
        if (count($params) === 0) {
            $operator = '=';
            $value = true;
            //
        } else {
            $operator = in_array($params[0], $this->operators, true) ? array_shift($params) : null;
            $value = array_shift($params);
        }

        $options = array_shift($params) ?: [];
        $methodPrefix = in_array('or', $options) ? 'or' : 'and';
        $method = $methodPrefix . ($this->filterType === 'where' ? 'Where' : 'OnCondition');

        // ignore empty values ?
        if ($this->isEmpty($value) && !in_array('allowEmpty', $options) && !in_array($operator, ['is', 'is not'])) {
            return $this;
        }

        $columnExpression = "$this->escapedAlias.$column";
        $decoratedColumnExpression = $this->decorateColumnExpression($columnExpression, $options);

        if ($operator === null) {
            return $this->{$method}([$decoratedColumnExpression => $value]);
        } else {
            return $this->{$method}([$operator, $decoratedColumnExpression, $value]);
        }
    }

    /**
     * Select column
     * @param string $column
     * @param array $params
     * 
     * @return static
     */
    protected function doOrderBy($column)
    {
        $params = func_get_args();
        array_shift($params); // shift '$column'
        
        $sort = isset($params[0]) ? $params[1] : SORT_ASC;
        $options = array_merge(['table' => $this->alias], isset($params[1]) ? $params[1] : []);

        $columnExpression = "{{{$options['table']}}}.$column";
        $decoratedColumnExpression = $this->decorateColumnExpression($columnExpression, $options);

        return $this->addOrderBy([$decoratedColumnExpression => $sort]);
    }

    /**
     * Select column
     * @param string $column
     * @param array $params
     * 
     * @return static
     */
    protected function doGroupBy($column)
    {
        $params = func_get_args();
        array_shift($params); // shift '$column'
        
        $options = array_merge(['table' => $this->alias], isset($params[0]) ? $params[0] : []);

        $columnExpression = "{{{$options['table']}}}.$column";
        $decoratedColumnExpression = $this->decorateColumnExpression($columnExpression, $options);

        return $this->addGroupBy($decoratedColumnExpression);
    }

    /**
     * Decorate column expression.
     * 
     * @param string $expression
     * @param array $options
     * @return string
     */
    protected function decorateColumnExpression($expression, $options)
    {
        // extract year, month or day ?
        if (in_array('year', $options)) {
            $expression = "EXTRACT(YEAR FROM $expression)";
        } elseif (in_array('month', $options)) {
            $expression = "EXTRACT(YEAR FROM $expression)";
        } elseif (in_array('day', $options)) {
            $expression = "EXTRACT(DAY FROM $expression)";
        }

        return $expression;
    }

    /**
     * @inheritdoc
     * @return static
     */
    public function addOrderBy($columns)
    {
        if ($columns instanceof ActiveQuery) {
            if (!is_array($this->orderBy)) {
                $this->orderBy = [];
            }

            $this->orderBy = array_merge($this->orderBy, $columns->orderBy);
            return $this;
        }

        return parent::addOrderBy($columns);
    }

    /**
     * @inheritdoc
     * @return static
     */
    public function addGroupBy($columns)
    {
        if ($columns instanceof ActiveQuery) {
            if (!is_array($this->groupBy)) {
                $this->groupBy = [];
            }

            $this->groupBy = array_values(array_unique(array_merge($this->groupBy, $columns->groupBy)));
            return $this;
        }

        return parent::addGroupBy($columns);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->_alias;
    }

    /**
     * @return string
     */
    public function getEscapedAlias()
    {
        return "{{{$this->_alias}}}";
    }

    /**
     * @inheritdoc
     * 
     * @param string $alias
     * @return static
     */
    public function alias($alias)
    {
        // save current alias
        $this->_alias = $alias;

        return parent::alias($alias);
    }

    /**
     * Change filter method to join filter.
     * 
     * @return static
     */
    public function joinFilter()
    {
        $this->filterType = 'join';
        return $this;
    }

    /**
     * Change filter method to where filter.
     * 
     * @return static
     */
    public function whereFilter()
    {
        $this->filterType = 'where';
        return $this;
    }

    /**
     * @inheritdoc
     * Add limit 1 to method one() for performance.
     * 
     * @param Connection|null $db
     * @return ActiveRecord|array|null
     */
    public function one($db = null)
    {
        $this->limit(1);

        return parent::one($db);
    }

    /**
     * Return one or new if not found.
     * 
     * @param Connection|null $db
     * @return ActiveRecord|array|null
     */
    public function oneOrNew($db = null)
    {
        $modelClass = $this->modelClass;
        $model = parent::one($db);
        return $model ? $model : (new $modelClass());
    }
    
}
