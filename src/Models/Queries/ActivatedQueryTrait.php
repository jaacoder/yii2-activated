<?php

namespace Jaacoder\Yii2Activated\Models\Queries;

use function Stringy\create as s;
use Jaacoder\Yii2Activated\Helpers\Meta;
use phpDocumentor\Reflection\Types\This;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

trait ActivatedQueryTrait
{
    protected $addOperations = ['select', 'orderBy', 'groupBy'];
    protected $andOrOperations = ['where', 'onCondition', 'having'];

    protected $_aliasesModels = [];
    protected $_clause = 'andWhere';
    protected $_defaultOperation = 'andWhere';

    /**
     * @return $this
     */
    public function setAliasesModels($aliasesModels)
    {
        $this->_aliasesModels = $aliasesModels;
        return $this;
    }

    /**
     * @param string|array $columns
     * @return array
     */
    protected function adjustSelectGroupByColumns($columns)
    {
        if (is_string($columns) || $columns instanceof Meta) {
            $columns = $this->mapToColumnInExpression('' . $columns);

        } else if (is_array($columns)) {
            $newColumns = [];
            foreach ($columns as $i => $column) {
                if (!is_string($column)) {
                    $newColumns = $columns;
                    break;
                }

                $newColumns[$i] = $this->mapToColumnInExpression($column);
            }

            $columns = $newColumns;
        }

        return $columns;
    }

    /**
     * @return $this
     */
    public function select($columns = null, $option = null)
    {
        return $this->checkArgSaveOperation($columns) ?? parent::select($this->adjustSelectGroupByColumns($columns), $option);
    }

    /**
     * {@inheritdoc}
     */
    public function addSelect($columns = null)
    {
        return $this->checkArgSaveOperation($columns) ?? parent::addSelect($this->adjustSelectGroupByColumns($columns));
    }

    /**
     * @return $this
     */
    public function  and (...$args) {
        $sOperation = s($this->_clause);

        if (!$sOperation->startsWith('and') && !$sOperation->startsWith('add')) {

            if (in_array($this->_clause, $this->addOperations)) {
                $this->_clause = 'add' . $sOperation->upperCaseFirst();

            } else {
                $this->_clause = (string) $sOperation->removeLeft('or')
                    ->upperCaseFirst()
                    ->ensureLeft('and');
            }
        }

        if (!empty($args)) {
            call_user_func_array([$this, $this->_clause], $args);
            $this->saveNextClause();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function  or (...$args) {
        $sOperation = s($this->_clause);

        if (!$sOperation->startsWith('or')) {
            $this->_clause = (string) $sOperation->removeLeft('and')
                ->upperCaseFirst()
                ->ensureLeft('or');
        }

        if (!empty($args)) {
            call_user_func_array([$this, $this->_clause], $args);
            $this->saveNextClause();
        }

        return $this;
    }

    /**
     * @param array $args
     * return array
     */
    protected function adjustJoinWithArgs($with, $eagerLoading = true, $joinType = null)
    {
        // convert to string if needed
        if ($with instanceof Meta) {
            $with .= '';

        } else if (is_array($with)) {
            $newWith = [];
            foreach($with as $key => $value) {
                $newWith['' . $key] = $value;
            }

            $with = $newWith;
        }

        // adjust query to callable
        if ($eagerLoading instanceof ActiveQuery) {
            $query = $eagerLoading;
            $args = func_get_args();
            $eagerLoading = isset($joinType) ? $joinType : true;
            if (isset($args[3])) {
                $joinType = $args[3];
            }

            $with = [$with => $this->queryToFunction($query)];
        }

        $response = [$with, $eagerLoading];
        if ($joinType !== null) {
            array_push($response, $joinType);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function innerJoinWith($with = null, $eagerLoading = true)
    {
        return $this->checkArgSaveOperation($with) ?? parent::innerJoinWith(...$this->adjustJoinWithArgs($with, $eagerLoading));
    }

    /**
     * {@inheritdoc}
     */
    public function joinWith($with = null, $eagerLoading = true, $joinType = 'LEFT JOIN')
    {
        return $this->checkArgSaveOperation($with) ?? parent::joinWith(...$this->adjustJoinWithArgs(...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function with()
    {
        $args = func_get_args();

        $this->saveClause();

        if (empty($args)) {
            return $this;    
        }

        // adjust query to callable
        if (isset($args[1]) && $args[1] instanceof ActiveQuery) {
            $args[0] = [$args[0] => $this->queryToFunction($args[1])];
            array_pop($args);
        }

        $this->saveNextClause();

        return parent::with(...$args);
    }

    /**
     * @param boolean $clause
     * @param string $condition
     * @param array $params
     * @return
     */
    protected function adjustWhereArgs($clause, $condition, $params = [])
    {
        // check if it is and / or ( ... ) condition with parentheses
        if ($clause !== null && $condition instanceof ActiveQuery) {
            $condition = $condition->{$clause};
            return [$condition, $params];
        }

        $args = func_get_args();
        array_shift($args);

        // adjust implicit operator
        if (count($args) == 2 && !is_array($args[1])) {
            $value = array_pop($args);
            array_push($args, '=', $value);
        }

        // adjust operator order
        if (count($args) >= 3) {
            $params = [];
            $condition = [$args[1], $this->mapToColumnInExpression('' . $args[0]), $args[2]];

            if (isset($args[3])) {
                array_push($condition, $args[3]);
            }

        } else if (is_string($condition) || $condition instanceof Meta) {
            $condition = $this->mapToColumnInExpression('' . $condition);

        } else if (is_array($condition)) {

            $newColumns = [];
            foreach ($condition as $column => $value) {
                if (!is_string($column) && !($column instanceof Meta)) {
                    $newColumns = $condition;
                    break;
                }

                $newColumns[$this->mapToColumnInExpression('' . $column)] = $value;
            }

            $condition = $newColumns;
        }

        if ($condition instanceof Meta) {
            $condition .= ''; // convert to string
        }

        return [$condition, $params];
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::where(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::andWhere(...$this->adjustWhereArgs('where', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::orWhere(...$this->adjustWhereArgs('where', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function filterWhere(array $condition)
    {
        return parent::filterWhere(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andFilterWhere(array $condition)
    {
        return parent::andFilterWhere(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orFilterWhere(array $condition)
    {
        return parent::orFilterWhere(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function onCondition($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::onCondition(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andOnCondition($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::andOnCondition(...$this->adjustWhereArgs('on', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orOnCondition($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::orOnCondition(...$this->adjustWhereArgs('on', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($columns = null)
    {
        return $this->checkArgSaveOperation($columns) ?? parent::groupBy($this->adjustSelectGroupByColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy($columns = null)
    {
        return $this->checkArgSaveOperation($columns) ?? parent::addGroupBy($this->adjustSelectGroupByColumns($columns));
    }

    /**
     * @param string|array $columns
     * @param string $order
     * @return array
     */
    protected function adjustOrderByColumns($columns, $order = null)
    {
        $orders = ['asc' => SORT_ASC, 'desc' => SORT_DESC];

        // convert to string if needed
        if ($columns instanceof Meta) {
            $columns .= '';
        }

        if (is_string($columns) && $order !== null) {
            $lowercaseOrder = strtolower($order);
            $columns = [$columns => isset($orders[$lowercaseOrder]) ? $orders[$lowercaseOrder] : $order];
        }

        if (is_string($columns)) {
            $columns = $this->mapToColumnInExpression($columns);

        } else if (is_array($columns)) {
            $newColumns = [];
            foreach ($columns as $columns => $order) {
                $newColumns[$this->mapToColumnInExpression('' . $columns)] = $order;
            }

            $columns = $newColumns;
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function having($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::having(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andHaving($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::andHaving(...$this->adjustWhereArgs('having', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orHaving($condition = null, $params = [])
    {
        return $this->checkArgSaveOperation($condition) ?? parent::orHaving(...$this->adjustWhereArgs('having', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function filterHaving(array $condition)
    {
        return parent::filterHaving(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andFilterHaving(array $condition)
    {
        return parent::andFilterHaving(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orFilterHaving(array $condition)
    {
        return parent::orFilterHaving(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($columns = null)
    {
        return $this->checkArgSaveOperation($columns) ?? parent::orderBy($this->adjustOrderByColumns(...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function addOrderBy($columns = null)
    {
        return $this->checkArgSaveOperation($columns) ?? parent::addOrderBy($this->adjustOrderByColumns(...func_get_args()));
    }

    /**
     * @return array
     */
    protected function getColumnNames()
    {
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;

        return $modelClass::getTableSchema()->getColumnNames();
    }

    /**
     * @return boolean
     */
    protected function isColumn($column)
    {
        return in_array($column, $this->getColumnNames());
    }

    /**
     * @property string $property
     * @property string $modelClass
     */
    protected function mapToColumn(string $property, $modelClass = null)
    {
        // return $property;

        $property .= ''; // convert to string

        if ($modelClass === null) {
            $modelClass = $this->modelClass;
        }

        $mapping = [];

        if ($modelClass && method_exists($modelClass, 'mapping')) {
            $mapping = $modelClass::mapping();

            // avoid wrong mapping
            if (!is_array($mapping)) {
                $mapping = [];
            }
        }

        // rename property to column name
        return isset($mapping[$property]) ? $mapping[$property] : $property;
    }

    /**
     * @param string $expression
     */
    protected function mapToColumnInExpression(string $expression)
    {
        // return $expression;

        // rename column and prefix table
        if (preg_match('/^[a-zA-Z_][a-zA-Z_0-9]*$/', $expression)) { // $expression is just column name

            $column = $this->mapToColumn($expression);

            if ($this->isColumn($column)) {
                // add alias as prefix
                $expression = $this->getTableNameAndAlias()[1] . '.' . $column;
            }

        } else {

            // is an expression? try to find columns prefixed with alias

            // find property inside expression like [[property]]
            $expression = preg_replace_callback('/(.?)\[\[([a-zA-Z_][a-zA-Z_0-9]*)\]\]/', function ($matches) {

                $column = $this->mapToColumn($matches[2]);

                if ($matches[1] === '.' || !$this->isColumn($column)) {
                    return $matches[0];
                }

                // rename property to column name
                return $matches[1] . '{{' . $this->getTableNameAndAlias()[1] . '}}.[[' . $column . ']]';
            }, $expression);

            $expression = preg_replace_callback('/(\w+)\.([a-zA-Z_][a-zA-Z_0-9]*)(.?)/', function ($matches) use ($expression) {

                if (in_array($matches[3], ['}', ']'])) {
                    return $matches[0];
                }

                $modelClass = null;

                if ($this->getTableNameAndAlias()[1] === $matches[1]) {
                    $modelClass = $this->modelClass;
                } else if (isset($this->_aliasesModels[$matches[1]])) {
                    $modelClass = $this->_aliasesModels[$matches[1]];
                }

                $column = $this->mapToColumn($matches[2], $modelClass);

                if ($modelClass && $this->isColumn($column)) {
                    // rename property to column name
                    return '{{' . $matches[1] . '}}.[[' . $column . ']]' . $matches[3];
                }

                return $matches[0];
            }, $expression);
        }

        return $expression;
    }

    /**
     * @return callable
     */
    protected function queryToFunction(ActiveQuery $query)
    {
        return function (ActiveQuery $newQuery) use ($query) {

            $varsToBeCopied = ['select', 'distinct', 'from', 'join', 'joinWith', 'with', 'where', 'on', 'groupBy', 'having', 'orderBy', 'params', 'limit', 'offset'];

            foreach ($varsToBeCopied as $var) {
                $newQuery->{$var} = $query->{$var};
            }
        };
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param array $params
     */
    public function __call($name, $params)
    {
        $args = array_merge([$name], $params);
        call_user_func_array([$this, $this->_clause], $args);

        $this->saveNextClause();

        return $this;
    }

    /**
     * Check args and save operation.
     * 
     * @param mixed $arg
     * @return $this|null
     */
    protected function checkArgSaveOperation($arg)
    {
        $this->saveClause(4);

        if ($arg === null) {
            return $this;
        }

        $this->saveNextClause();
    }

    /**
     * Save current operation.
     */
    protected function saveClause($level = 3)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level);

        // change operation if not called inside this object
        // ex.: select() called by addSelect() internally
        if (!is_a($this, $backtrace[$level - 1]['class'] ?? '')) {
            $this->_clause = $backtrace[$level - 2]['function'];
        }
    }

    /**
     * Save next operation.
     */
    protected function saveNextClause()
    {
        $sOperation = s($this->_clause);

        if (in_array($this->_clause, $this->addOperations)) {
            $this->_clause = 'add' . $sOperation->upperCaseFirst();

        } else if (in_array($this->_clause, $this->andOrOperations)) {
            $this->_clause = 'and' . $sOperation->upperCaseFirst();
        }
    }
}