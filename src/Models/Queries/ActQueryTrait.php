<?php

namespace Jaacoder\Yii2Activated\Models\Queries;

use Exception;
use function Stringy\create as s;
use Jaacoder\Yii2Activated\Helpers\Meta;
use SqlFormatter;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

trait ActQueryTrait
{
    protected $_addOperations = ['select', 'orderBy', 'groupBy'];
    protected $_andOrOperations = ['where', 'onCondition', 'having'];

    protected $_aliasesModels = [];
    protected $_operation = [];
    protected $_lastOperation = '';
    protected $_defaultOperation = 'andWhere';

    protected $_ignoreEmpty = false;
    protected $_ignoreNull = false;
    protected $_ignoreEmptyOn = false;
    protected $_ignoreNullOn = false;

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
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($columns, $option) {
            parent::select($this->adjustSelectGroupByColumns($columns), $option);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function addSelect($columns = null)
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($columns) {
            parent::addSelect($this->adjustSelectGroupByColumns($columns));
        });
    }

    /**
     * @return $this
     */
    public function  and (...$args) {
        $sClause = s($this->_operation ?: $this->_lastOperation)
            ->removeLeft('or')
            ->lowerCaseFirst();

        $operation = (string) $sClause;

        if (in_array($operation, $this->_addOperations)) {
            $operation = 'add' . $sClause->upperCaseFirst();

        } else if (in_array($operation, $this->_andOrOperations)) {
            $operation = 'and' . $sClause->upperCaseFirst();
        }

        $this->saveOperation($operation, true);

        if (!empty($args)) {
            call_user_func_array([$this, $operation], $args);
            $this->resetOperation();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function  or (...$args) {
        $sOperation = s($this->_operation ?: $this->_lastOperation)
            ->removeLeft('and')
            ->lowerCaseFirst();

        $operation = (string) $sOperation;

        if (in_array($operation, $this->_andOrOperations)) {
            $operation = 'or' . $sOperation->upperCaseFirst();
        }

        $this->saveOperation($operation, true);

        if (!empty($args)) {
            call_user_func_array([$this, $operation], $args);
            $this->resetOperation();
        }

        return $this;
    }

    /**
     * Ignore next parameter if empty.
     * return $this
     */
    function ignoreEmpty()
    {
        $this->_ignoreEmpty = true;
        return $this;
    }

    /**
     * Ignore next parameter if null.
     * return $this
     */
    function ignoreNull()
    {
        $this->_ignoreNull = true;
        return $this;
    }

    /**
     * Ignore parameter if empty until method 'ignoreEmptyOff()'.
     * return $this
     */
    function ignoreEmptyOn()
    {
        $this->_ignoreEmptyOn = true;
        return $this;
    }

    /**
     * Ignore parameter if null until method 'ignoreNullOff()'.
     * return $this
     */
    function ignoreNullOn()
    {
        $this->_ignoreNullOn = true;
        return $this;
    }

    /**
     * Turn off skipping empty parameter.
     * return $this
     */
    function ignoreEmptyOff()
    {
        $this->_ignoreEmptyOn = false;
        return $this;
    }

    /**
     * Turn off skipping null parameter.
     * return $this
     */
    function ignoreNullOff()
    {
        $this->_ignoreNullOn = false;
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
            foreach ($with as $key => $value) {
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
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            parent::innerJoinWith(...$this->adjustJoinWithArgs(...$args));
        });
    }

    /**
     * @see self::innerJoinWith
     *
     * @param mixed $with
     * @param ActiveQuery $activeQuery
     * @return $this
     */
    public function innerJoinRelation($with = null, ActiveQuery $activeQuery = null)
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $args[] = false;    // $eagerLoading = false
            parent::innerJoinWith(...$this->adjustJoinWithArgs(...$args));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function joinWith($with = null, $eagerLoading = true, $joinType = 'LEFT JOIN')
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            parent::joinWith(...$this->adjustJoinWithArgs(...$args));
        });
    }

    /**
     * @see self::joinWith
     *
     * @param mixed $with
     * @param ActiveQuery $activeQuery
     * @return $this
     */
    public function joinRelation($with = null, ActiveQuery $activeQuery = null)
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $args[] = false;    // $eagerLoading = false
            parent::joinWith(...$this->adjustJoinWithArgs(...$args));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function with()
    {
        $args = func_get_args();

        $this->saveOperation(__FUNCTION__, true);

        if (empty($args)) {
            return $this;
        }

        // adjust query to callable
        if (isset($args[1]) && $args[1] instanceof ActiveQuery) {
            $args[0] = [$args[0] => $this->queryToFunction($args[1])];
            array_pop($args);
        }
        
        parent::with(...$args);
        $this->resetOperation();

        return $this;
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

            $operatorIs = in_array($args[1], ['is', 'is not']);

            // check if it should ignore empty value
            if (($this->_ignoreEmpty || $this->_ignoreEmptyOn) && $this->isEmpty($args[2]) && !$operatorIs)
                return;
            
            // check if it should ignore null value
            if (($this->_ignoreNull || $this->_ignoreNullOn) && $args[2] === null && !$operatorIs)
                return;

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
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs(null, ...$args);

            if ($newArgs)
                parent::where(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere($condition = null, $params = [])
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs('where', ...$args);

            if ($newArgs)
                parent::andWhere(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($condition = null, $params = [])
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs('where', ...$args);
            
            if ($newArgs)
                parent::orWhere(...$newArgs);
        });
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
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs(null, ...$args);

            if ($newArgs)
                parent::onCondition(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function andOnCondition($condition = null, $params = [])
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs('on', ...$args);
            
            if ($newArgs)
                parent::andOnCondition(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function orOnCondition($condition = null, $params = [])
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs('on', ...$args);

            if ($newArgs)
                parent::orOnCondition(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($columns = null)
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($columns) {
            parent::groupBy($this->adjustSelectGroupByColumns($columns));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy($columns = null)
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($columns) {
            parent::addGroupBy($this->adjustSelectGroupByColumns($columns));
        });
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
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs(null, ...$args);

            if ($newArgs)
                parent::having(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function andHaving($condition = null, $params = [])
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs('having', ...$args);

            if ($newArgs)
                parent::andHaving(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function orHaving($condition = null, $params = [])
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            $newArgs = $this->adjustWhereArgs('having', ...$args);

            if ($newArgs)
                parent::orHaving(...$newArgs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function filterHaving(array $condition)
    {
        $args = func_get_args();
        return parent::filterHaving(...$this->adjustWhereArgs(null, ...$args));
    }

    /**
     * {@inheritdoc}
     */
    public function andFilterHaving(array $condition)
    {
        $args = func_get_args();
        return parent::andFilterHaving(...$this->adjustWhereArgs(null, ...$args));
    }

    /**
     * {@inheritdoc}
     */
    public function orFilterHaving(array $condition)
    {
        $args = func_get_args();
        return parent::orFilterHaving(...$this->adjustWhereArgs(null, ...$args));
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($columns = null)
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            parent::orderBy($this->adjustOrderByColumns(...$args));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function addOrderBy($columns = null)
    {
        $args = func_get_args();

        return $this->checkArgAndSaveOperation(count($args), __FUNCTION__, function() use ($args) {
            parent::addOrderBy($this->adjustOrderByColumns(...$args));
        });
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
                
                if ($modelClass && $modelClass::find()->isColumn($column)) {
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
        if (empty($this->_operation)) {
            $this->_operation = $this->_lastOperation ?: $this->_defaultOperation;
        }

        $sClause = s($this->_operation);

        if (!$sClause->isBlank()) {

            $args = array_merge([$name], $params);

            if ($sClause->endsWith('Relation')) {
                $sClause = $sClause->removeRight('Relation')->ensureRight('With');
                $args[] = false;    // $eagerLoading = false
            }

            if (!method_exists($this, (string) $sClause)) {
                throw new Exception('Unexistent method: ' . self::class . '->' . $sClause);
            }

            call_user_func_array([$this, (string) $sClause], $args);
            $this->resetOperation();
        }

        return $this;
    }

    /**
     * Check args and save operation.
     *
     * @param int $argCount
     * @param string $operation
     * @param callable $callback
     * @return $this|mixed
     */
    protected function checkArgAndSaveOperation($argCount, $operation, $callback)
    {
        $this->saveOperation($operation);

        if ($argCount === 0) {
            return $this;
        }

        call_user_func($callback);
        $this->resetOperation();

        return $this;
    }

    /**
     * Save current operation.
     *
     * @param string $operation
     * @param boolean $force
     */
    protected function saveOperation($operation, $force = false)
    {
        if ($force || empty($this->operation))
            $this->_operation = $operation;
    }

    /**
     * Reset operation and save for next round.
     */
    protected function resetOperation()
    {
        // avoid many calls if this function has already been called
        if (empty($this->_operation))
            return;

        $sOperation = s($this->_operation);

        if (in_array($this->_operation, $this->_addOperations)) {
            $this->_lastOperation = 'add' . $sOperation->upperCaseFirst();

        } else if (in_array($this->_operation, $this->_andOrOperations)) {
            $this->_lastOperation = 'and' . $sOperation->upperCaseFirst();

        } else {
            $this->_lastOperation = $this->_operation;
        }

        $this->_operation = '';
        $this->_ignoreEmpty = false;
        $this->_ignoreNull = false;
    }

    /**
     * Return raw sql.
     * 
     * @param boolean $formatted
     * @return string
     */
    public function getRawSql($formatted = false)
    {
        $sql = $this->createCommand()->getRawSql();
        return $formatted ? SqlFormatter::format($sql) : $sql;
    }

    /**
     * Return sql.
     * 
     * @param boolean $formatted
     * @return string
     */
    public function getSql($formatted = false)
    {
        $sql = $this->createCommand()->getSql();
        return $formatted ? SqlFormatter::format($sql) : $sql;
    }

    /**
     * Return sql and params.
     * 
     * @param boolean $formatted
     * @return string
     */
    public function getSqlAndParams($formatted = false)
    {
        $command = $this->createCommand();
        $sql = $command->getSql();

        return [
            'sql' => $formatted ? SqlFormatter::format($sql) : $sql,
            'params' => $command->params
        ];
    }
}
