<?php

namespace Jaacoder\Yii2Activated\Models\Queries;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

trait ActivatedQueryTrait
{
    protected $_aliasesModels = [];

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
        if (is_string($columns)) {
            $columns = $this->mapToColumnInExpression($columns);
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
    public function select($columns, $option = null)
    {
        return parent::select($this->adjustSelectGroupByColumns($columns), $option);
    }

    /**
     * {@inheritdoc}
     */
    public function addSelect($columns)
    {
        return parent::addSelect($this->adjustSelectGroupByColumns($columns));
    }

    /**
     * @param array $args
     * return array
     */
    protected function adjustJoinWithArgs($with, $eagerLoading = true, $joinType = null)
    {
        // adjust query to callable
        if ($eagerLoading instanceof ActiveQuery) {
            $query = $eagerLoading;
            $args = func_get_args();
            $eagerLoading = isset($joinType) ? $joinType : true;
            if (isset($args[3]))
                $joinType = $args[3];

            $with = [$with => $this->queryToFunction($query)];
        }

        $response = [$with, $eagerLoading];
        if ($joinType !== null)
            array_push($response, $joinType);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function innerJoinWith($with, $eagerLoading = true)
    {
        return parent::innerJoinWith(...$this->adjustJoinWithArgs($with, $eagerLoading));
    }

    /**
     * {@inheritdoc}
     */
    public function joinWith($with, $eagerLoading = true, $joinType = 'LEFT JOIN')
    {
        return parent::joinWith(...$this->adjustJoinWithArgs(...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function with()
    {
        $args = func_get_args();

        // adjust query to callable
        if (isset($args[1]) && $args[1] instanceof ActiveQuery) {
            $args[0] = [$args[0] => $this->queryToFunction($args[1])];
            array_pop($args);
        }

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
            $condition = [$args[1], $this->mapToColumnInExpression($args[0]), $args[2]];

            if (isset($args[3]))
                array_push($condition, $args[3]);
        } else if (is_string($condition)) {
            $condition = $this->mapToColumnInExpression($condition);
        } else if (is_array($condition)) {

            $newColumns = [];
            foreach ($condition as $column => $value) {
                if (!is_string($column)) {
                    $newColumns = $condition;
                    break;
                }

                $newColumns[$this->mapToColumnInExpression($column)] = $value;
            }

            $condition = $newColumns;
        }

        return [$condition, $params];
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $params = [])
    {
        return parent::where(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere($condition, $params = [])
    {
        return parent::andWhere(...$this->adjustWhereArgs('where', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($condition, $params = [])
    {
        return parent::orWhere(...$this->adjustWhereArgs('where', ...func_get_args()));
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
    public function onCondition($condition, $params = [])
    {
        return parent::onCondition(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andOnCondition($condition, $params = [])
    {
        return parent::andOnCondition(...$this->adjustWhereArgs('on', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orOnCondition($condition, $params = [])
    {
        return parent::orOnCondition(...$this->adjustWhereArgs('on', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($columns)
    {
        return parent::groupBy($this->adjustSelectGroupByColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy($columns)
    {
        return parent::addGroupBy($this->adjustSelectGroupByColumns($columns));
    }

    /**
     * @param string|array $columns
     * @param string $order
     * @return array
     */
    protected function adjustOrderByColumns($columns, $order = null)
    {
        $orders = ['asc' => SORT_ASC, 'desc' => SORT_DESC];

        if (is_string($columns) && $order !== null) {
            $lowercaseOrder = strtolower($order);
            $columns = [$columns => isset($orders[$lowercaseOrder]) ? $orders[$lowercaseOrder] : $order];
        }

        if (is_string($columns)) {
            $columns = $this->mapToColumnInExpression($columns);
        } else if (is_array($columns)) {
            $newColumns = [];
            foreach ($columns as $columns => $order) {
                $newColumns[$this->mapToColumnInExpression($columns)] = $order;
            }

            $columns = $newColumns;
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function having($condition, $params = [])
    {
        return parent::having(...$this->adjustWhereArgs(null, ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function andHaving($condition, $params = [])
    {
        return parent::andHaving(...$this->adjustWhereArgs('having', ...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function orHaving($condition, $params = [])
    {
        return parent::orHaving(...$this->adjustWhereArgs('having', ...func_get_args()));
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
    public function orderBy($columns)
    {
        return parent::orderBy($this->adjustOrderByColumns(...func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function addOrderBy($columns)
    {
        return parent::addOrderBy($this->adjustOrderByColumns(...func_get_args()));
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
     * @property string $modelClass
     * @property _ActiveRecord $property
     */
    protected function mapToColumn($property, $modelClass = null)
    {
        if ($modelClass === null)
            $modelClass = $this->modelClass;

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
    protected function mapToColumnInExpression($expression)
    {
        // rename column and prefix table
        if (preg_match('/^[a-zA-Z_][a-zA-Z_0-9]*$/', $expression)) {   // $expression is just column name

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

                if ($matches[1] === '.' || !$this->isColumn($column))
                    return $matches[0];

                // rename property to column name
                return $matches[1] . '{{' . $this->getTableNameAndAlias()[1] . '}}.[[' . $column . ']]';
            }, $expression);

            $expression = preg_replace_callback('/(\w+)\.([a-zA-Z_][a-zA-Z_0-9]*)(.?)/', function ($matches) use ($expression) {

                if (in_array($matches[3], ['}', ']']))
                    return $matches[0];

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
}


class _ActiveRecord
{
    public static function mapping()
    {
    }
}
