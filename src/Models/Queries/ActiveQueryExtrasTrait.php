<?php

namespace Jaacoder\Yii2Activated\Models\Queries;

use Jaacoder\Yii2Activated\Helpers\Meta;
use ReflectionClass;
use Sesgo\CoreYii\Models\ActiveRecordPro;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Connection;

/**
 * Trait ActiveQueryExtrasTrait.
 * 
 * @author jaacoder
 */
trait ActiveQueryExtrasTrait
{
    public $_relationAliases = [];
    protected $_filterType = 'where';
    protected $_operators = ['=', '<', '<=', '>', '>=', '<>', '!=', 'in', 'not in', 'like', 'ilike', 'not like', 'not ilike', 'is', 'is not', '&'];
    protected $_operation = 'where';
    private $alias;
    private $_defaultJoinType = 'LEFT JOIN';

    /**
     * @var ActiveQuery
     */
    protected $originalQuery = null; // if this is a clone whitin a parenthesis
    protected $originalQueryOperator = null; // 'and' or 'or' relational operator

    public function initActiveQueryExtras()
    {
        // set default alias for this query
        if (empty($this->alias)) {
            $modelClass = $this->modelClass;
            $this->alias = $modelClass::tableName();
        }
    }

    /**
     * @return $this
     */
    public function select($columns = null, $option = null)
    {
        $this->_operation = 'select';

        if ($columns === null) {
            return $this;
        }

        return parent::select($columns, $option);
    }

    /**
     * @return $this
     */
    public function with()
    {
        $args = func_get_args();

        $this->_operation = 'with';
        
        if (count($args) === 0) {
            return $this;
        }

        // convert to string if needed
        if (isset($args[0]) && $args[0] instanceof \Jaacoder\Yii2Activated\Helpers\Meta) {
            $args[0] = (string) $args[0];

            // prepare query callback if needed
            if (isset($args[1]) && $args[1] instanceof ActiveQuery) {
                $args = [[$args[0] => $this->queryToFunction($args[1])]];
            }
        }

        return parent::with(...$args);
    }

    /**
     * @return $this
     */
    public function joinWith($with = null, $eagerLoading = true, $joinType = 'LEFT JOIN')
    {
        $this->_operation = 'joinWith';

        if ($with === null) {
            return $this;
        }

        // check if Meta was passed
        if ($with instanceof Meta) {
            $with = (string) $with;
        }

        // check if ActiveQuery was passed as secong argument
        if ($eagerLoading instanceof ActiveQuery) {
            $with = [$with => $this->queryToFunction($eagerLoading)];

            if (is_bool($joinType)) {
                $eagerLoading = $joinType;
                $joinType = func_get_args()[3] ?? $this->_defaultJoinType;
            }
        }

        return parent::joinWith($with, $eagerLoading, $joinType);
    }

    /**
     * @return $this
     */
    public function join($type, $table = null, $on = '', $params = [])
    {
        if ($type instanceof Meta || $table instanceof ActiveQuery) {
            $args = [$type];
            if ($this instanceof ActiveQuery) {
                $args[] = $table;
            }
            $args[] = false;

            return $this->joinWith(...$args);
        }

        return parent::join(...func_get_args());
    }

    /**
     * @return $this
     */
    public function innerJoinWith($with = null, $eagerLoading = true)
    {
        $this->_operation = 'innerJoinWith';

        if ($with === null) {
            return $this;
        }

        $args = [$with];

        if ($eagerLoading instanceof ActiveQuery) {
            $args[] = $eagerLoading;
        }

        $args[] = is_bool($eagerLoading) ? $eagerLoading : true;
        $args[] = 'INNER JOIN';

        return $this->joinWith(...$args);
    }

    /**
     * @return $this
     */
    public function innerJoin($type = null, $table = null, $on = '', $params = [])
    {
        if ($type instanceof Meta || $table instanceof ActiveQuery) {
            $args = [$type];
            if ($this instanceof ActiveQuery) {
                $args[] = $table;
            }
            $args[] = false;
            $args[] = 'INNER JOIN';

            return $this->joinWith(...$args);
        }

        return parent::innerJoin(...func_get_args());
    }

    /**
     * @return $this
     */
    public function where($condition = null, $params = array())
    {
        $this->_operation = 'where';

        if ($condition === null) {
            return $this;
        }

        $args = func_get_args();

        if (count($args) == 3 && is_string($args[0]) && is_string($args[1]) && in_array($args[1], $this->_operators)) {
            $args = [[$args[1], $args[0], $args[1]]];
        }

        return parent::where(...$args);
    }

    /**
     * @return $this
     */
    public function orderBy($columns = null)
    {
        $this->_operation = 'orderBy';

        if ($columns === null) {
            return $this;
        }

        // check if order was passed as second argument
        if (is_int(func_get_args()[1] ?? null)) {
            $columns = [$columns => func_get_arg(1)];
        }

        return parent::addOrderBy($columns);
    }

    /**
     * @return $this
     */
    public function and()
    {
        return $this;
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
        $tableSchema = $modelClass::getTableSchema();

        $mapping = [];
        $reflectionClass = new ReflectionClass($modelClass);
        if ($reflectionClass->hasMethod('mapping')) {
            $mapping = $modelClass::mapping();
        }

        // check if method is the name of property or relation
        $columnOrRelation = isset($mapping[$name]) ? $mapping[$name] : $tableSchema->getColumn($name);
        if (!$columnOrRelation && $reflectionClass->hasMethod('get' . ucfirst($name))) {
            $columnOrRelation = $name;
        }

        if ($columnOrRelation) {
            return $this->__callColumnOrRelation($columnOrRelation, $params);
        }

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

    public function __callColumnOrRelation($name, $params = [])
    {

        if ($this->_operation === 'select') {
            return parent::addSelect("$this->escapedAlias.$name");
        }

        if (in_array($this->_operation, ['innerJoinWith', 'joinWith', 'with'])) {
            $fn = function () { };

            if (!empty($params)) {
                if ($params[0] instanceof ActiveQuery) {
                    $fn = $this->queryToFunction($params[0]);
                } elseif (is_callable($params[0])) {
                    $fn = $params[0];
                }
            }

            return parent::{$this->_operation}([$name => $fn]);
        }

        if ($this->_operation === 'where') {
            $args = array_merge([$name], $params);
            return $this->doFilter(...$args);
        }

        if ($this->_operation === 'orderBy') {
            $order = $params[0] ?? SORT_ASC;
            return parent::addOrderBy(["$this->escapedAlias.$name" => $order]);
        }

        return parent::__call($name, $params);
    }

    /**
     * @return callable
     */
    public function queryToFunction(ActiveQuery $query)
    {
        return function (ActiveQuery $newQuery) use ($query) {

            $varsToBeCopied = ['alias', 'select', 'distinct', 'from', 'join', 'joinWith', 'with', 'where', 'groupBy', 'having', 'orderBy', 'params', 'limit', 'offset'];

            foreach ($varsToBeCopied as $var) {
                $newQuery->{$var} = $query->{$var};
            }
        };
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
        $joinType = isset($params[2]) ? $params[2] : $this->_defaultJoinType;
        $options = isset($params[3]) ? $params[3] : [];

        // check if options is before
        if (is_array($callback) && !is_callable($callback)) {
            $options = $callback;
            $callback = null;
            $eagerLoading = true;
            $joinType = $this->_defaultJoinType;
            //
        } elseif (is_array($eagerLoading)) {
            $options = $eagerLoading;
            $eagerLoading = true;
            $joinType = $this->_defaultJoinType;
            //
        } elseif (is_array($joinType)) {
            $options = $joinType;
            $joinType = $this->_defaultJoinType;
        }

        if (isset($options['alias'])) {
            $this->_relationAliases[$relation] = $options['alias'];
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
            $operator = in_array($params[0], $this->_operators, true) ? array_shift($params) : null;
            $value = array_shift($params);
        }

        $options = array_shift($params) ?: [];
        $methodPrefix = in_array('or', $options) ? 'or' : 'and';
        $method = $methodPrefix . ($this->_filterType === 'where' ? 'Where' : 'OnCondition');

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

        $sort = isset($params[0]) ? $params[0] : SORT_ASC;
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

        // check if order was passed as second argument
        if (is_int(func_get_args()[1] ?? null)) {
            $columns = [$columns => func_get_arg(1)];
        }

        return parent::addOrderBy($columns);
    }

    /**
     * @inheritdoc
     * @return static
     */
    public function groupBy($columns)
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
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getEscapedAlias()
    {
        return "{{{$this->alias}}}";
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
        $this->alias = $alias;

        return parent::alias($alias);
    }

    /**
     * Change filter method to join filter.
     * 
     * @return static
     */
    public function joinFilter()
    {
        $this->_filterType = 'join';
        return $this;
    }

    /**
     * Change filter method to where filter.
     * 
     * @return static
     */
    public function whereFilter()
    {
        $this->_filterType = 'where';
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
