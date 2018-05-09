<?php

namespace Jeanku\Database\Query\Grammars;

use Jeanku\Database\Query\Builder;
use Jeanku\Database\Query\Expression;

class Grammar
{

    /**
     * The grammar table prefix.
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The grammar specific operators.
     * @var array
     */
    protected $operators = [];

    /**
     * The components that make up a select clause.
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'unions',
        'lock',
    ];

    /**
     * Compile a select query into SQL.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        $original = $query->columns;
        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }
        $sql = trim($this->concatenate($this->compileComponents($query)));
        $query->columns = $original;
        return $sql;
    }

    /**
     * Compile the components necessary for a select clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return array
     */
    protected function compileComponents(Builder $query)
    {
        $sql = [];
        foreach ($this->selectComponents as $component) {
            if (! is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }
        return $sql;
    }

    /**
     * Compile an aggregated select clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);
        if ($query->distinct && $column !== '*') {
            $column = 'distinct '.$column;
        }
        return 'select '.$aggregate['function'].'('.$column.') as aggregate';
    }

    /**
     * Compile the "select *" portion of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        if (! is_null($query->aggregate)) {
            return;
        }
        $select = $query->distinct ? 'select distinct ' : 'select ';
        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(Builder $query, $table)
    {
        return 'from '.$this->wrapTable($table);
    }

    /**
     * Compile the "join" portions of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(Builder $query, $joins)
    {
        $sql = [];
        foreach ($joins as $join) {
            $table = $this->wrapTable($join->table);
            $type = $join->type;
            if ($type === 'cross' &&  ! $join->clauses) {
                $sql[] = "cross join $table";

                continue;
            }
            $clauses = [];
            foreach ($join->clauses as $clause) {
                $clauses[] = $this->compileJoinConstraint($clause);
            }
            $clauses[0] = $this->removeLeadingBoolean($clauses[0]);
            $clauses = implode(' ', $clauses);
            $sql[] = "$type join $table on $clauses";
        }
        return implode(' ', $sql);
    }

    /**
     * Create a join clause constraint segment.
     * @param  array  $clause
     * @return string
     */
    protected function compileJoinConstraint(array $clause)
    {
        if ($clause['nested']) {
            return $this->compileNestedJoinConstraint($clause);
        }
        $first = $this->wrap($clause['first']);
        if ($clause['where']) {
            if ($clause['operator'] === 'in' || $clause['operator'] === 'not in') {
                $second = '('.implode(', ', array_fill(0, $clause['second'], '?')).')';
            } else {
                $second = '?';
            }
        } else {
            $second = $this->wrap($clause['second']);
        }
        return "{$clause['boolean']} $first {$clause['operator']} $second";
    }

    /**
     * Create a nested join clause constraint segment.
     * @param  array  $clause
     * @return string
     */
    protected function compileNestedJoinConstraint(array $clause)
    {
        $clauses = [];
        foreach ($clause['join']->clauses as $nestedClause) {
            $clauses[] = $this->compileJoinConstraint($nestedClause);
        }
        $clauses[0] = $this->removeLeadingBoolean($clauses[0]);
        $clauses = implode(' ', $clauses);
        return "{$clause['boolean']} ({$clauses})";
    }

    /**
     * Compile the "where" portions of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return string
     */
    protected function compileWheres(Builder $query)
    {
        $sql = [];
        if (is_null($query->wheres)) {
            return '';
        }
        foreach ($query->wheres as $where) {
            $method = "where{$where['type']}";

            $sql[] = $where['boolean'].' '.$this->$method($query, $where);
        }
        if (count($sql) > 0) {
            $sql = implode(' ', $sql);
            return 'where '.$this->removeLeadingBoolean($sql);
        }
        return '';
    }

    /**
     * Compile a nested where clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNested(Builder $query, $where)
    {
        $nested = $where['query'];
        return '('.substr($this->compileWheres($nested), 6).')';
    }

    /**
     * Compile a where condition with a sub-select.
     * @param  \Jeanku\Database\Query\Builder $query
     * @param  array   $where
     * @return string
     */
    protected function whereSub(Builder $query, $where)
    {
        $select = $this->compileSelect($where['query']);
        return $this->wrap($where['column']).' '.$where['operator']." ($select)";
    }

    /**
     * Compile a basic where clause.
     *
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
    }

    /**
     * Compile a where clause comparing two columns..
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereColumn(Builder $query, $where)
    {
        $second = $this->wrap($where['second']);

        return $this->wrap($where['first']).' '.$where['operator'].' '.$second;
    }

    /**
     * Compile a "between" where clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetween(Builder $query, $where)
    {
        $between = $where['not'] ? 'not between' : 'between';

        return $this->wrap($where['column']).' '.$between.' ? and ?';
    }

    /**
     * Compile a where exists clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereExists(Builder $query, $where)
    {
        return 'exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a where exists clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotExists(Builder $query, $where)
    {
        return 'not exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a "where in" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(Builder $query, $where)
    {
        if (empty($where['values'])) {
            return '0 = 1';
        }
        $values = $this->parameterize($where['values']);
        return $this->wrap($where['column']).' in ('.$values.')';
    }

    /**
     * Compile a "where not in" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(Builder $query, $where)
    {
        if (empty($where['values'])) {
            return '1 = 1';
        }
        $values = $this->parameterize($where['values']);
        return $this->wrap($where['column']).' not in ('.$values.')';
    }

    /**
     * Compile a where in sub-select clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInSub(Builder $query, $where)
    {
        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']).' in ('.$select.')';
    }

    /**
     * Compile a where not in sub-select clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInSub(Builder $query, $where)
    {
        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']).' not in ('.$select.')';
    }

    /**
     * Compile a "where null" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(Builder $query, $where)
    {
        return $this->wrap($where['column']).' is null';
    }

    /**
     * Compile a "where not null" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(Builder $query, $where)
    {
        return $this->wrap($where['column']).' is not null';
    }

    /**
     * Compile a "where date" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDate(Builder $query, $where)
    {
        return $this->dateBasedWhere('date', $query, $where);
    }

    /**
     * Compile a "where time" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereTime(Builder $query, $where)
    {
        return $this->dateBasedWhere('time', $query, $where);
    }

    /**
     * Compile a "where day" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDay(Builder $query, $where)
    {
        return $this->dateBasedWhere('day', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereMonth(Builder $query, $where)
    {
        return $this->dateBasedWhere('month', $query, $where);
    }

    /**
     * Compile a "where year" clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereYear(Builder $query, $where)
    {
        return $this->dateBasedWhere('year', $query, $where);
    }

    /**
     * Compile a date based where clause.
     * @param  string  $type
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function dateBasedWhere($type, Builder $query, $where)
    {
        $value = $this->parameter($where['value']);
        return $type.'('.$this->wrap($where['column']).') '.$where['operator'].' '.$value;
    }

    /**
     * Compile a raw where clause.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereRaw(Builder $query, $where)
    {
        return $where['sql'];
    }

    /**
     * Compile the "group by" portions of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $groups
     * @return string
     */
    protected function compileGroups(Builder $query, $groups)
    {
        return 'group by '.$this->columnize($groups);
    }

    /**
     * Compile the "having" portions of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $havings
     * @return string
     */
    protected function compileHavings(Builder $query, $havings)
    {
        $sql = implode(' ', array_map([$this, 'compileHaving'], $havings));
        return 'having '.$this->removeLeadingBoolean($sql);
    }

    /**
     * Compile a single having clause.
     * @param  array   $having
     * @return string
     */
    protected function compileHaving(array $having)
    {
        if ($having['type'] === 'raw') {
            return $having['boolean'].' '.$having['sql'];
        }
        return $this->compileBasicHaving($having);
    }

    /**
     * Compile a basic having clause.
     * @param  array   $having
     * @return string
     */
    protected function compileBasicHaving($having)
    {
        $column = $this->wrap($having['column']);
        $parameter = $this->parameter($having['value']);
        return $having['boolean'].' '.$column.' '.$having['operator'].' '.$parameter;
    }

    /**
     * Compile the "order by" portions of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(Builder $query, $orders)
    {
        return 'order by '.implode(', ', array_map(function ($order) {
            if (isset($order['sql'])) {
                return $order['sql'];
            }
            return $this->wrap($order['column']).' '.$order['direction'];
        }, $orders));
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RANDOM()';
    }

    /**
     * Compile the "limit" portions of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return 'limit '.(int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return 'offset '.(int) $offset;
    }

    /**
     * Compile the "union" queries attached to the main query.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return string
     */
    protected function compileUnions(Builder $query)
    {
        $sql = '';
        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }
        if (isset($query->unionOrders)) {
            $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
        }
        if (isset($query->unionLimit)) {
            $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
        }
        if (isset($query->unionOffset)) {
            $sql .= ' '.$this->compileOffset($query, $query->unionOffset);
        }
        return ltrim($sql);
    }

    /**
     * Compile a single union statement.
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $joiner = $union['all'] ? ' union all ' : ' union ';
        return $joiner.$union['query']->toSql();
    }

    /**
     * Compile an exists statement into SQL.
     *
     * @param \Jeanku\Database\Query\Builder $query
     * @return string
     */
    public function compileExists(Builder $query)
    {
        $select = $this->compileSelect($query);

        return "select exists($select) as {$this->wrap('exists')}";
    }

    /**
     * Compile an insert statement into SQL.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        $table = $this->wrapTable($query->from);
        if (! is_array(reset($values))) {
            $values = [$values];
        }
        $columns = $this->columnize(array_keys(reset($values)));
        $parameters = [];
        foreach ($values as $record) {
            $parameters[] = '('.$this->parameterize($record).')';
        }
        $parameters = implode(', ', $parameters);
        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile an insert and get ID statement into SQL.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array   $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile an update statement into SQL.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, $values)
    {
        $table = $this->wrapTable($query->from);
        $columns = [];
        foreach ($values as $key => $value) {
            $columns[] = $this->wrap($key).' = '.$this->parameter($value);
        }
        $columns = implode(', ', $columns);
        if (isset($query->joins)) {
            $joins = ' '.$this->compileJoins($query, $query->joins);
        } else {
            $joins = '';
        }
        $where = $this->compileWheres($query);
        return trim("update {$table}{$joins} set $columns $where");
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        return $bindings;
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        $table = $this->wrapTable($query->from);

        $where = is_array($query->wheres) ? $this->compileWheres($query) : '';

        return trim("delete from $table ".$where);
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        return ['truncate '.$this->wrapTable($query->from) => []];
    }

    /**
     * Compile the lock into SQL.
     *
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(Builder $query, $value)
    {
        return is_string($value) ? $value : '';
    }

    /**
     * Determine if the grammar supports savepoints.
     *
     * @return bool
     */
    public function supportsSavepoints()
    {
        return true;
    }

    /**
     * Compile the SQL statement to define a savepoint.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepoint($name)
    {
        return 'SAVEPOINT '.$name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT '.$name;
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param  array   $segments
     * @return string
     */
    protected function concatenate($segments)
    {
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param  string  $value
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Get the gramar specific operators.
     *
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }


    /**
     * Wrap an array of values.
     * @param  array  $values
     * @return array
     */
    public function wrapArray(array $values)
    {
        return array_map([$this, 'wrap'], $values);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  \Jeanku\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if ($this->isExpression($table)) {
            return $table->getValue();
        }
        return $this->wrap($this->tablePrefix.$table, true);
    }

    /**
     * Wrap a value in keyword identifiers.
     * @param  \Jeanku\Database\Query\Expression|string  $value
     * @param  bool    $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        if ($this->isExpression($value)) {
            return $value->getValue();
        }

        if (strpos(strtolower($value), ' as ') !== false) {
            $segments = explode(' ', $value);
            if ($prefixAlias) {
                $segments[2] = $this->tablePrefix.$segments[2];
            }
            return $this->wrap($segments[0]).' as '.$this->wrapValue($segments[2]);
        }

        $wrapped = [];
        $segments = explode('.', $value);
        foreach ($segments as $key => $segment) {
            if ($key == 0 && count($segments) > 1) {
                $wrapped[] = $this->wrapTable($segment);
            } else {
                $wrapped[] = $this->wrapValue($segment);
            }
        }
        return implode('.', $wrapped);
    }

    /**
     * Wrap a single string in keyword identifiers.
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }
        return '"'.str_replace('"', '""', $value).'"';
    }

    /**
     * Convert an array of column names into a delimited string.
     * @param  array   $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    /**
     * Create query parameter place-holders for an array.
     * @param  array   $values
     * @return string
     */
    public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /**
     * Get the appropriate query parameter place-holder for a value.
     * @param  mixed   $value
     * @return string
     */
    public function parameter($value)
    {
        return $this->isExpression($value) ? $value->getValue() : '?';
    }

    /**
     * Determine if the given value is a raw expression.
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    /**
     * Get the grammar's table prefix.
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
     * @param  string  $prefix
     * @return $this
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
        return $this;
    }
}
