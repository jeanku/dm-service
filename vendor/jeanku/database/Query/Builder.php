<?php

namespace Jeanku\Database\Query;

use Closure;
use Jeanku\Database\Support\Arr;
use Jeanku\Database\Support\Str;
use Jeanku\Database\Support\Collection;


class Builder
{

    /**
     * The database connection instance.
     * @var \Jeanku\Database\Connection
     */
    protected $connection;

    /**
     * The database query grammar instance.
     * @var \Jeanku\Database\Query\Grammars\Grammar
     */
    protected $grammar;

    /**
     * The current query value bindings.
     * @var array
     */
    protected $bindings = [
        'select' => [],
        'join'   => [],
        'where'  => [],
        'having' => [],
        'order'  => [],
        'union'  => [],
    ];

    /**
     * An aggregate function and column to be run.
     * @var array
     */
    public $aggregate;

    /**
     * The columns that should be returned.
     * @var array
     */
    public $columns;

    /**
     * Indicates if the query returns distinct results.
     * @var bool
     */
    public $distinct = false;

    /**
     * The table which the query is targeting.
     * @var string
     */
    public $from;

    /**
     * The table joins for the query.
     *
     * @var array
     */
    public $joins;

    /**
     * The where constraints for the query.
     * @var array
     */
    public $wheres;

    /**
     * The groupings for the query.
     * @var array
     */
    public $groups;

    /**
     * The having constraints for the query.
     * @var array
     */
    public $havings;

    /**
     * The orderings for the query.
     * @var array
     */
    public $orders;

    /**
     * The maximum number of records to return.
     * @var int
     */
    public $limit;

    /**
     * The number of records to skip.
     * @var int
     */
    public $offset;

    /**
     * The query union statements.
     * @var array
     */
    public $unions;

    /**
     * The maximum number of union records to return.
     * @var int
     */
    public $unionLimit;

    /**
     * The number of union records to skip.
     * @var int
     */
    public $unionOffset;

    /**
     * The orderings for the union query.
     * @var array
     */
    public $unionOrders;

    /**
     * Indicates whether row locking is being used.
     * @var string|bool
     */
    public $lock;

    /**
     * The field backups currently in use.
     * @var array
     */
    protected $backups = [];

    /**
     * The binding backups currently in use.
     * @var array
     */
    protected $bindingBackups = [];

    /**
     * All of the available clause operators.
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'like binary', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Whether use write pdo for select.
     * @var bool
     */
    protected $useWritePdo = false;

    /**
     * Create a new query builder instance.
     * @param  $connection
     * @param  \Jeanku\Database\Query\Grammars\Grammar  $grammar
     * @return void
     */
    public function __construct($connection = null, $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getQueryGrammar();
    }

    /**
     * Set the columns to be selected.
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a new "raw" select expression to the query.
     * @param  string  $expression
     * @param  array   $bindings
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function selectRaw($expression, array $bindings = [])
    {
        $this->addSelect(new Expression($expression));
        if ($bindings) {
            $this->addBinding($bindings, 'select');
        }
        return $this;
    }

    /**
     * Add a subselect expression to the query.
     * @param  \Closure|\Jeanku\Database\Query\Builder|string $query
     * @param  string  $as
     * @return \Jeanku\Database\Query\Builder|static
     * @throws \Exception
     */
    public function selectSub($query, $as)
    {
        if ($query instanceof Closure) {
            $callback = $query;

            $callback($query = $this->newQuery());
        }
        if ($query instanceof self) {
            $bindings = $query->getBindings();

            $query = $query->toSql();
        } elseif (is_string($query)) {
            $bindings = [];
        } else {
            throw new \Exception('error', -1);
        }

        return $this->selectRaw('('.$query.') as '.$this->grammar->wrap($as), $bindings);
    }

    /**
     * Add a new select column to the query.
     * @param  array|mixed  $column
     * @return $this
     */
    public function addSelect($column)
    {
        $column = is_array($column) ? $column : func_get_args();
        $this->columns = array_merge((array) $this->columns, $column);
        return $this;
    }

    /**
     * Force the query to only return distinct results.
     * @return $this
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Set the table which the query is targeting.
     * @param  string  $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = $table;
        return $this;
    }

    /**
     * Add a join clause to the query.
     * @param  string  $table
     * @param  string  $one
     * @param  string  $operator
     * @param  string  $two
     * @param  string  $type
     * @param  bool    $where
     * @return $this
     */
    public function join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
    {
        if ($one instanceof Closure) {
            $join = new JoinClause($type, $table);
            call_user_func($one, $join);
            $this->joins[] = $join;
            $this->addBinding($join->bindings, 'join');
        } else {
            $join = new JoinClause($type, $table);
            $this->joins[] = $join->on(
                $one, $operator, $two, 'and', $where
            );
            $this->addBinding($join->bindings, 'join');
        }
        return $this;
    }

    /**
     * Add a "join where" clause to the query.
     * @param  string  $table
     * @param  string  $one
     * @param  string  $operator
     * @param  string  $two
     * @param  string  $type
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function joinWhere($table, $one, $operator, $two, $type = 'inner')
    {
        return $this->join($table, $one, $operator, $two, $type, true);
    }

    /**
     * Add a left join to the query.
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a "join where" clause to the query.
     * @param  string  $table
     * @param  string  $one
     * @param  string  $operator
     * @param  string  $two
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function leftJoinWhere($table, $one, $operator, $two)
    {
        return $this->joinWhere($table, $one, $operator, $two, 'left');
    }

    /**
     * Add a right join to the query.
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a "right join where" clause to the query.
     *
     * @param  string  $table
     * @param  string  $one
     * @param  string  $operator
     * @param  string  $two
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function rightJoinWhere($table, $one, $operator, $two)
    {
        return $this->joinWhere($table, $one, $operator, $two, 'right');
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        if ($first) {
            return $this->join($table, $first, $operator, $second, 'cross');
        }

        $this->joins[] = new JoinClause('cross', $table);

        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param  bool  $value
     * @param  \Closure  $callback
     * @return \Jeanku\Database\Query\Builder
     */
    public function when($value, $callback)
    {
        $builder = $this;

        if ($value) {
            $builder = call_user_func($callback, $builder);
        }

        return $builder;
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     *
     * @throws \Exception
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }
        if (func_num_args() == 2) {
            list($value, $operator) = [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new \Exception('Illegal operator and value combination.', -1);
        }
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }
        if (! in_array(strtolower($operator), $this->operators, true) &&
            ! in_array(strtolower($operator), $this->grammar->getOperators(), true)) {
            list($value, $operator) = [$operator, '='];
        }
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator != '=');
        }
        $type = 'Basic';
        if (Str::contains($column, '->') && is_bool($value)) {
            $value = new Expression($value ? 'true' : 'false');
        }
        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
        if (! $value instanceof Expression) {
            $this->addBinding($value, 'where');
        }
        return $this;
    }

    /**
     * Add an array of where clauses to the query.
     * @param  array  $column
     * @param  string  $boolean
     * @param  string  $method
     * @return $this
     */
    protected function addArrayOfWheres($column, $boolean, $method = 'where')
    {
        return $this->whereNested(function ($query) use ($column, $method) {
            foreach ($column as $key => $value) {
                if (is_numeric($key) && is_array($value)) {
                    call_user_func_array([$query, $method], $value);
                } else {
                    $query->$method($key, '=', $value);
                }
            }
        }, $boolean);
    }

    /**
     * Determine if the given operator and value combination is legal.
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        $isOperator = in_array($operator, $this->operators);
        return is_null($value) && $isOperator && ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Add an "or where" clause to the query.
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a "where" clause comparing two columns to the query.
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string|null  $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        if (is_array($first)) {
            return $this->addArrayOfWheres($first, $boolean, 'whereColumn');
        }
        if (! in_array(strtolower($operator), $this->operators, true) &&
            ! in_array(strtolower($operator), $this->grammar->getOperators(), true)) {
            list($second, $operator) = [$operator, '='];
        }
        $type = 'Column';
        $this->wheres[] = compact('type', 'first', 'operator', 'second', 'boolean');
        return $this;
    }

    /**
     * Add an "or where" clause comparing two columns to the query.
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereColumn($first, $operator = null, $second = null)
    {
        return $this->whereColumn($first, $operator, $second, 'or');
    }

    /**
     * Add a raw where clause to the query.
     * @param  string  $sql
     * @param  array   $bindings
     * @param  string  $boolean
     * @return $this
     */
    public function whereRaw($sql, array $bindings = [], $boolean = 'and')
    {
        $type = 'raw';
        $this->wheres[] = compact('type', 'sql', 'boolean');
        $this->addBinding($bindings, 'where');
        return $this;
    }

    /**
     * Add a raw or where clause to the query.
     * @param  string  $sql
     * @param  array   $bindings
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereRaw($sql, array $bindings = [])
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    /**
     * Add a where between statement to the query.
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';
        $this->wheres[] = compact('column', 'type', 'boolean', 'not');
        $this->addBinding($values, 'where');
        return $this;
    }

    /**
     * Add an or where between statement to the query.
     * @param  string  $column
     * @param  array   $values
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Add a where not between statement to the query.
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add an or where not between statement to the query.
     *
     * @param  string  $column
     * @param  array   $values
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereNotBetween($column, array $values)
    {
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Add a nested where statement to the query.
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        $query = $this->forNestedWhere();
        call_user_func($callback, $query);
        return $this->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Create a new query instance for nested where condition.
     * @return \Jeanku\Database\Query\Builder
     */
    public function forNestedWhere()
    {
        $query = $this->newQuery();

        return $query->from($this->from);
    }

    /**
     * Add another query builder as a nested where to the query builder.
     * @param  \Jeanku\Database\Query\Builder|static $query
     * @param  string  $boolean
     * @return $this
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        if (count($query->wheres)) {
            $type = 'Nested';
            $this->wheres[] = compact('type', 'query', 'boolean');
            $this->addBinding($query->getBindings(), 'where');
        }
        return $this;
    }

    /**
     * Add a full sub-select to the query.
     * @param  string   $column
     * @param  string   $operator
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return $this
     */
    protected function whereSub($column, $operator, Closure $callback, $boolean)
    {
        $type = 'Sub';
        $query = $this->newQuery();
        call_user_func($callback, $query);
        $this->wheres[] = compact('type', 'column', 'operator', 'query', 'boolean');
        $this->addBinding($query->getBindings(), 'where');
        return $this;
    }

    /**
     * Add an exists clause to the query.
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     * @return $this
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        $query = $this->newQuery();
        call_user_func($callback, $query);
        return $this->addWhereExistsQuery($query, $boolean, $not);
    }

    /**
     * Add an or exists clause to the query.
     * @param  \Closure $callback
     * @param  bool     $not
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereExists(Closure $callback, $not = false)
    {
        return $this->whereExists($callback, 'or', $not);
    }

    /**
     * Add a where not exists clause to the query.
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereNotExists(Closure $callback, $boolean = 'and')
    {
        return $this->whereExists($callback, $boolean, true);
    }

    /**
     * Add a where not exists clause to the query.
     * @param  \Closure  $callback
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereNotExists(Closure $callback)
    {
        return $this->orWhereExists($callback, true);
    }

    /**
     * Add an exists clause to the query.
     * @param  \Jeanku\Database\Query\Builder $query
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function addWhereExistsQuery(Builder $query, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotExists' : 'Exists';
        $this->wheres[] = compact('type', 'operator', 'query', 'boolean');
        $this->addBinding($query->getBindings(), 'where');
        return $this;
    }

    /**
     * Add a "where in" clause to the query.
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @param  bool    $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';
        if ($values instanceof static) {
            return $this->whereInExistingQuery(
                $column, $values, $boolean, $not
            );
        }
        if ($values instanceof Closure) {
            return $this->whereInSub($column, $values, $boolean, $not);
        }
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }
        $this->wheres[] = compact('type', 'column', 'values', 'boolean');
        $this->addBinding($values, 'where');
        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     * @param  string  $column
     * @param  mixed   $values
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
     * @param  string  $column
     * @param  mixed   $values
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereNotIn($column, $values)
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * Add a where in with a sub-select to the query.
     * @param  string   $column
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     * @return $this
     */
    protected function whereInSub($column, Closure $callback, $boolean, $not)
    {
        $type = $not ? 'NotInSub' : 'InSub';
        call_user_func($callback, $query = $this->newQuery());
        $this->wheres[] = compact('type', 'column', 'query', 'boolean');
        $this->addBinding($query->getBindings(), 'where');
        return $this;
    }

    /**
     * Add a external sub-select to the query.
     * @param  string   $column
     * @param  \Jeanku\Database\Query\Builder|static  $query
     * @param  string   $boolean
     * @param  bool     $not
     * @return $this
     */
    protected function whereInExistingQuery($column, $query, $boolean, $not)
    {
        $type = $not ? 'NotInSub' : 'InSub';
        $this->wheres[] = compact('type', 'column', 'query', 'boolean');
        $this->addBinding($query->getBindings(), 'where');
        return $this;
    }

    /**
     * Add a "where null" clause to the query.
     * @param  string  $column
     * @param  string  $boolean
     * @param  bool    $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';
        $this->wheres[] = compact('type', 'column', 'boolean');
        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     * @param  string  $column
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * Add a "where not null" clause to the query.
     * @param  string  $column
     * @param  string  $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * Add an "or where not null" clause to the query.
     * @param  string  $column
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add a "where date" statement to the query.
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereDate($column, $operator, $value, $boolean = 'and')
    {
        return $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where date" statement to the query.
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereDate($column, $operator, $value)
    {
        return $this->whereDate($column, $operator, $value, 'or');
    }

    /**
     * Add a "where time" statement to the query.
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereTime($column, $operator, $value, $boolean = 'and')
    {
        return $this->addDateBasedWhere('Time', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where time" statement to the query.
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orWhereTime($column, $operator, $value)
    {
        return $this->whereTime($column, $operator, $value, 'or');
    }

    /**
     * Add a "where day" statement to the query.
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereDay($column, $operator, $value, $boolean = 'and')
    {
        return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean);
    }

    /**
     * Add a "where month" statement to the query.
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereMonth($column, $operator, $value, $boolean = 'and')
    {
        return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean);
    }

    /**
     * Add a "where year" statement to the query.
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function whereYear($column, $operator, $value, $boolean = 'and')
    {
        return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean);
    }

    /**
     * Add a date based (year, month, day, time) statement to the query.
     * @param  string  $type
     * @param  string  $column
     * @param  string  $operator
     * @param  int  $value
     * @param  string  $boolean
     * @return $this
     */
    protected function addDateBasedWhere($type, $column, $operator, $value, $boolean = 'and')
    {
        $this->wheres[] = compact('column', 'type', 'boolean', 'operator', 'value');
        $this->addBinding($value, 'where');
        return $this;
    }

    /**
     * Handles dynamic "where" clauses to the query.
     * @param  string  $method
     * @param  string  $parameters
     * @return $this
     */
    public function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);
        $segments = preg_split('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE);
        $connector = 'and';
        $index = 0;
        foreach ($segments as $segment) {
            if ($segment != 'And' && $segment != 'Or') {
                $this->addDynamic($segment, $connector, $parameters, $index);
                $index++;
            }
            else {
                $connector = $segment;
            }
        }
        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     * @param  string  $segment
     * @param  string  $connector
     * @param  array   $parameters
     * @param  int     $index
     * @return void
     */
    protected function addDynamic($segment, $connector, $parameters, $index)
    {
        $bool = strtolower($connector);
        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }

    /**
     * Add a "group by" clause to the query.
     * @param  array|string  $column,...
     * @return $this
     */
    public function groupBy()
    {
        foreach (func_get_args() as $arg) {
            $this->groups = array_merge((array) $this->groups, is_array($arg) ? $arg : [$arg]);
        }
        return $this;
    }

    /**
     * Add a "having" clause to the query.
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @param  string  $boolean
     * @return $this
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'basic';
        $this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');
        if (! $value instanceof Expression) {
            $this->addBinding($value, 'having');
        }
        return $this;
    }

    /**
     * Add a "or having" clause to the query.
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Add a raw having clause to the query.
     * @param  string  $sql
     * @param  array   $bindings
     * @param  string  $boolean
     * @return $this
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and')
    {
        $type = 'raw';
        $this->havings[] = compact('type', 'sql', 'boolean');
        $this->addBinding($bindings, 'having');
        return $this;
    }

    /**
     * Add a raw or having clause to the query.
     * @param  string  $sql
     * @param  array   $bindings
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function orHavingRaw($sql, array $bindings = [])
    {
        return $this->havingRaw($sql, $bindings, 'or');
    }

    /**
     * Add an "order by" clause to the query.
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => strtolower($direction) == 'asc' ? 'asc' : 'desc',
        ];
        return $this;
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     * @param  string  $column
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function latest($column = 'created_at')
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     * @param  string  $column
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function oldest($column = 'created_at')
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * Put the query's results in random order.
     * @param  string  $seed
     * @return $this
     */
    public function inRandomOrder($seed = '')
    {
        return $this->orderByRaw($this->grammar->compileRandom($seed));
    }

    /**
     * Add a raw "order by" clause to the query.
     * @param  string  $sql
     * @param  array  $bindings
     * @return $this
     */
    public function orderByRaw($sql, $bindings = [])
    {
        $property = $this->unions ? 'unionOrders' : 'orders';
        $type = 'raw';
        $this->{$property}[] = compact('type', 'sql');
        $this->addBinding($bindings, 'order');
        return $this;
    }

    /**
     * Set the "offset" value of the query.
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $property = $this->unions ? 'unionOffset' : 'offset';
        $this->$property = max(0, $value);
        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     * @param  int  $value
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "limit" value of the query.
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        $property = $this->unions ? 'unionLimit' : 'limit';
        if ($value >= 0) {
            $this->$property = $value;
        }
        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     * @param  int  $value
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the limit and offset for a given page.
     * @param  int  $page
     * @param  int  $perPage
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Constrain the query to the next "page" of results after a given ID.
     * @param  int  $perPage
     * @param  int  $lastId
     * @param  string  $column
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id')
    {
        $this->orders = Collection::make($this->orders)
            ->reject(function ($order) use ($column) {
                return $order['column'] === $column;
            })->values()->all();
        return $this->where($column, '>', $lastId)
            ->orderBy($column, 'asc')
            ->take($perPage);
    }

    /**
     * Add a union statement to the query.
     * @param  \Jeanku\Database\Query\Builder|\Closure  $query
     * @param  bool  $all
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function union($query, $all = false)
    {
        if ($query instanceof Closure) {
            call_user_func($query, $query = $this->newQuery());
        }
        $this->unions[] = compact('query', 'all');
        $this->addBinding($query->getBindings(), 'union');
        return $this;
    }

    /**
     * Add a union all statement to the query.
     * @param  \Jeanku\Database\Query\Builder|\Closure  $query
     * @return \Jeanku\Database\Query\Builder|static
     */
    public function unionAll($query)
    {
        return $this->union($query, true);
    }

    /**
     * Lock the selected rows in the table.
     * @param  bool  $value
     * @return $this
     */
    public function lock($value = true)
    {
        $this->lock = $value;
        if ($this->lock) {
            $this->useWritePdo();
        }
        return $this;
    }

    /**
     * Lock the selected rows in the table for updating.
     * @return \Jeanku\Database\Query\Builder
     */
    public function lockForUpdate()
    {
        return $this->lock(true);
    }

    /**
     * Share lock the selected rows in the table.
     * @return \Jeanku\Database\Query\Builder
     */
    public function sharedLock()
    {
        return $this->lock(false);
    }

    /**
     * Get the SQL representation of the query.
     * @return string
     */
    public function toSql()
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Execute a query for a single record by ID.
     * @param  int    $id
     * @param  array  $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = (array) $this->first([$column]);
        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query and get the first result.
     * @param  array   $columns
     * @return mixed|static
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);
        return count($results) > 0 ? reset($results) : null;
    }

    /**
     * Execute the query as a "select" statement.
     * @param  array  $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        is_null($this->columns) && $this->columns = $columns;
        return $this->runSelect();

    }

    /**
     * Run the query as a "select" statement against the connection.
     * @return array
     */
    protected function runSelect()
    {
        return $this->connection->select($this->toSql(), $this->getBindings(), ! $this->useWritePdo);
    }

    /**
     * Backup some fields for the pagination count.
     *
     * @return void
     */
    protected function backupFieldsForCount()
    {
        foreach (['orders', 'limit', 'offset', 'columns'] as $field) {
            $this->backups[$field] = $this->{$field};
            $this->{$field} = null;
        }
        foreach (['order', 'select'] as $key) {
            $this->bindingBackups[$key] = $this->bindings[$key];
            $this->bindings[$key] = [];
        }
    }

    /**
     * Remove the column aliases since they will break count queries.
     * @param  array  $columns
     * @return array
     */
    protected function clearSelectAliases(array $columns)
    {
        return array_map(function ($column) {
            return is_string($column) && ($aliasPosition = strpos(strtolower($column), ' as ')) !== false
                ? substr($column, 0, $aliasPosition) : $column;
        }, $columns);
    }

    /**
     * Restore some fields after the pagination count.
     * @return void
     */
    protected function restoreFieldsForCount()
    {
        foreach (['orders', 'limit', 'offset', 'columns'] as $field) {
            $this->{$field} = $this->backups[$field];
        }
        foreach (['order', 'select'] as $key) {
            $this->bindings[$key] = $this->bindingBackups[$key];
        }
        $this->backups = [];
        $this->bindingBackups = [];
    }

    /**
     * Get a generator for the given query.
     * @return \Generator
     */
    public function cursor()
    {
        if (is_null($this->columns)) {
            $this->columns = ['*'];
        }
        return $this->connection->cursor($this->toSql(), $this->getBindings(), ! $this->useWritePdo);
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return  bool
     */
    public function chunk($count, callable $callback)
    {
        $results = $this->forPage($page = 1, $count)->get();
        while (count($results) > 0) {
            if (call_user_func($callback, $results) === false) {
                return false;
            }
            $page++;
            $results = $this->forPage($page, $count)->get();
        }
        return true;
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     * @param  int  $count
     * @param  callable  $callback
     * @param  string  $column
     * @param  string  $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = 'id', $alias = null)
    {
        $lastId = null;
        $alias = $alias ?: $column;
        $results = $this->forPageAfterId($count, 0, $column)->get();
        while (! empty($results)) {
            if (call_user_func($callback, $results) === false) {
                return false;
            }
            $lastId = last($results)->{$alias};
            $results = $this->forPageAfterId($count, $lastId, $column)->get();
        }
        return true;
    }

    /**
     * Execute a callback over each item while chunking.
     * @param  callable  $callback
     * @param  int  $count
     * @return bool
     * @throws \Exception
     */
    public function each(callable $callback, $count = 1000)
    {
        if (is_null($this->orders) && is_null($this->unionOrders)) {
            throw new \Exception('You must specify an orderBy clause when using the "each" function.');
        }
        return $this->chunk($count, function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Get an array with the values of a given column.
     * @param  string  $column
     * @param  string|null  $key
     * @return array
     */
    public function pluck($column, $key = null)
    {
        $results = $this->get(is_null($key) ? [$column] : [$column, $key]);
        return Arr::pluck(
            $results,
            $this->stripTableForPluck($column),
            $this->stripTableForPluck($key)
        );
    }

    /**
     * Alias for the "pluck" method.
     * @param  string  $column
     * @param  string|null  $key
     * @return array
     * @deprecated since version 5.2. Use the "pluck" method directly.
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key);
    }

    /**
     * Strip off the table name or alias from a column identifier.
     * @param  string  $column
     * @return string|null
     */
    protected function stripTableForPluck($column)
    {
        return is_null($column) ? $column : end(preg_split('~\.| ~', $column));
    }

    /**
     * Concatenate values of a given column as a string.
     * @param  string  $column
     * @param  string  $glue
     * @return string
     */
    public function implode($column, $glue = '')
    {
        return implode($glue, $this->pluck($column));
    }

    /**
     * Determine if any rows exist for the current query.
     * @return bool
     */
    public function exists()
    {
        $sql = $this->grammar->compileExists($this);
        $results = $this->connection->select($sql, $this->getBindings(), ! $this->useWritePdo);
        if (isset($results[0])) {
            $results = (array) $results[0];
            return (bool) $results['exists'];
        }
        return false;
    }

    /**
     * Retrieve the "count" result of the query.
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }
        return (int) $this->aggregate(__FUNCTION__, $columns);
    }

    /**
     * Retrieve the minimum value of a given column.
     * @param  string  $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     * @param  string  $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     * @param  string  $column
     * @return mixed
     */
    public function sum($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the average of the values of a given column.
     * @param  string  $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Alias for the "avg" method.
     * @param  string  $column
     * @return mixed
     */
    public function average($column)
    {
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database.
     * @param  string  $function
     * @param  array   $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $this->aggregate = compact('function', 'columns');
        $previousColumns = $this->columns;
        $previousSelectBindings = $this->bindings['select'];
        $this->bindings['select'] = [];
        $results = $this->get($columns);
        $this->aggregate = null;
        $this->columns = $previousColumns;
        $this->bindings['select'] = $previousSelectBindings;
        if (isset($results[0])) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }
    }

    /**
     * Execute a numeric aggregate function on the database.
     * @param  string  $function
     * @param  array   $columns
     * @return float|int
     */
    public function numericAggregate($function, $columns = ['*'])
    {
        $result = $this->aggregate($function, $columns);
        if (! $result) {
            return 0;
        }
        if (is_int($result) || is_float($result)) {
            return $result;
        }
        if (strpos((string) $result, '.') === false) {
            return (int) $result;
        }
        return (float) $result;
    }

    /**
     * Insert a new record into the database.
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }
        if (! is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }
        $bindings = [];
        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }
        $sql = $this->grammar->compileInsert($this, $values);
        $bindings = $this->cleanBindings($bindings);
        return $this->connection->insert($sql, $bindings);
    }

    /**
     * Insert a new record and get the value of the primary key.
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $sql = $this->grammar->compileInsertGetId($this, $values, $sequence);
        $values = $this->cleanBindings($values);
        return $this->processInsertGetId($this, $sql, $values, $sequence);
    }


    /**
     * Process an  "insert get ID" query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);
        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);
        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $bindings = array_values(array_merge($values, $this->getBindings()));
        $sql = $this->grammar->compileUpdate($this, $values);
        return $this->connection->update($sql, $this->cleanBindings($bindings));
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        if (! $this->where($attributes)->exists()) {
            return $this->insert(array_merge($attributes, $values));
        }
        return (bool) $this->where($attributes)->take(1)->update($values);
    }

    /**
     * Increment a column's value by a given amount.
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     * @throws \Exception
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        if (! is_numeric($amount)) {
            throw new \Exception('Non-numeric value passed to increment method.', -1);
        }
        $wrapped = $this->grammar->wrap($column);
        $columns = array_merge([$column => $this->raw("$wrapped + $amount")], $extra);
        return $this->update($columns);
    }

    /**
     * Decrement a column's value by a given amount.
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     * @throws \Exception
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        if (! is_numeric($amount)) {
            throw new \Exception('Non-numeric value passed to decrement method.', -1);
        }
        $wrapped = $this->grammar->wrap($column);
        $columns = array_merge([$column => $this->raw("$wrapped - $amount")], $extra);
        return $this->update($columns);
    }

    /**
     * Delete a record from the database.
     * @param  mixed  $id
     * @return int
     */
    public function delete($id = null)
    {
        if (! is_null($id)) {
            $this->where('id', '=', $id);
        }
        $sql = $this->grammar->compileDelete($this);
        return $this->connection->delete($sql, $this->getBindings());
    }

    /**
     * Run a truncate statement on the table.
     * @return void
     */
    public function truncate()
    {
        foreach ($this->grammar->compileTruncate($this) as $sql => $bindings) {
            $this->connection->statement($sql, $bindings);
        }
    }

    /**
     * Get a new instance of the query builder.
     * @return \Jeanku\Database\Query\Builder
     */
    public function newQuery()
    {
        return new static($this->connection, $this->grammar);
    }

    /**
     * Merge an array of where clauses and bindings.
     * @param  array  $wheres
     * @param  array  $bindings
     * @return void
     */
    public function mergeWheres($wheres, $bindings)
    {
        $this->wheres = array_merge((array) $this->wheres, (array) $wheres);
        $this->bindings['where'] = array_values(array_merge($this->bindings['where'], (array) $bindings));
    }

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * @param  array  $bindings
     * @return array
     */
    protected function cleanBindings(array $bindings)
    {
        return array_values(array_filter($bindings, function ($binding) {
            return ! $binding instanceof Expression;
        }));
    }

    /**
     * Create a raw database expression.
     * @param  mixed  $value
     * @return \Jeanku\Database\Query\Expression
     */
    public function raw($value)
    {
        return $this->connection->raw($value);
    }

    /**
     * Get the current query value bindings in a flattened array.
     * @return array
     */
    public function getBindings()
    {
        return Arr::flatten($this->bindings);
    }

    /**
     * Get the raw array of bindings.
     *
     * @return array
     */
    public function getRawBindings()
    {
        return $this->bindings;
    }

    /**
     * Set the bindings on the query builder.
     * @param  array   $bindings
     * @param  string  $type
     * @return $this
     *
     * @throws \Exception
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new \Exception("Invalid binding type: {$type}.", -1);
        }
        $this->bindings[$type] = $bindings;
        return $this;
    }

    /**
     * Add a binding to the query.
     * @param  mixed   $value
     * @param  string  $type
     * @return $this
     * @throws \Exception
     */
    public function addBinding($value, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new \Exception("Invalid binding type: {$type}.", -1);
        }
        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }
        return $this;
    }

    /**
     * Merge an array of bindings into our bindings.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return $this
     */
    public function mergeBindings(Builder $query)
    {
        $this->bindings = array_merge_recursive($this->bindings, $query->bindings);
        return $this;
    }

    /**
     * Get the database connection instance.
     * @return \Jeanku\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }


    /**
     * Get the query grammar instance.
     * @return \Jeanku\Database\Query\Grammars\Grammar
     */
    public function getGrammar()
    {
        return $this->grammar;
    }

    /**
     * Use the write pdo for query.
     *
     * @return $this
     */
    public function useWritePdo()
    {
        $this->useWritePdo = true;
        return $this;
    }

    /**
     * Handle dynamic method calls into the method.
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }
        if (Str::startsWith($method, 'where')) {
            return $this->dynamicWhere($method, $parameters);
        }
        $className = static::class;
        throw new \Exception("Call to undefined method {$className}::{$method}()");
    }
}
