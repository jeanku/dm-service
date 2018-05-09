<?php

namespace Jeanku\Database\Query\Grammars;

use Jeanku\Database\Support\Str;
use Jeanku\Database\Query\Builder;
use Jeanku\Database\Query\Expression;

class MySqlGrammar extends Grammar
{

    /**
     * Compile a select query into SQL.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        $sql = parent::compileSelect($query);
        if ($query->unions) {
            $sql = '('.$sql.') '.$this->compileUnions($query);
        }
        return $sql;
    }

    /**
     * Compile a single union statement.
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $joiner = $union['all'] ? ' union all ' : ' union ';
        return $joiner.'('.$union['query']->toSql().')';
    }

    /**
     * Compile the random statement into SQL.
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RAND('.$seed.')';
    }

    /**
     * Compile the lock into SQL.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(Builder $query, $value)
    {
        if (is_string($value)) {
            return $value;
        }
        return $value ? 'for update' : 'lock in share mode';
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
            if ($this->isJsonSelector($key)) {
                $columns[] = $this->compileJsonUpdateColumn(
                    $key, new Expression($value)
                );
            } else {
                $columns[] = $this->wrap($key).' = '.$this->parameter($value);
            }
        }
        $columns = implode(', ', $columns);
        if (isset($query->joins)) {
            $joins = ' '.$this->compileJoins($query, $query->joins);
        } else {
            $joins = '';
        }
        $where = $this->compileWheres($query);
        $sql = rtrim("update {$table}{$joins} set $columns $where");
        if (isset($query->orders)) {
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }
        if (isset($query->limit)) {
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }
        return rtrim($sql);
    }

    /**
     * Prepares a JSON column being updated using the JSON_SET function.
     * @param  string $key
     * @param \Jeanku\Database\Expression $value
     * @return string
     */
    protected function compileJsonUpdateColumn($key, Expression $value)
    {
        $path = explode('->', $key);
        $field = $this->wrapValue(array_shift($path));
        $accessor = '"$.'.implode('.', $path).'"';
        return "{$field} = json_set({$field}, {$accessor}, {$value->getValue()})";
    }

    /**
     * Prepare the bindings for an update statement.
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        $index = 0;
        foreach ($values as $column => $value) {
            if ($this->isJsonSelector($column) && is_bool($value)) {
                unset($bindings[$index]);
            }
            $index++;
        }
        return $bindings;
    }

    /**
     * Compile a delete statement into SQL.
     * @param  \Jeanku\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        $table = $this->wrapTable($query->from);
        $where = is_array($query->wheres) ? $this->compileWheres($query) : '';
        if (isset($query->joins)) {
            $joins = ' '.$this->compileJoins($query, $query->joins);
            $sql = trim("delete $table from {$table}{$joins} $where");
        } else {
            $sql = trim("delete from $table $where");
            if (isset($query->orders)) {
                $sql .= ' '.$this->compileOrders($query, $query->orders);
            }
            if (isset($query->limit)) {
                $sql .= ' '.$this->compileLimit($query, $query->limit);
            }
        }
        return $sql;
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
        if ($this->isJsonSelector($value)) {
            return $this->wrapJsonSelector($value);
        }
        return '`'.str_replace('`', '``', $value).'`';
    }

    /**
     * Wrap the given JSON selector.
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        $path = explode('->', $value);
        $field = $this->wrapValue(array_shift($path));
        return $field.'->'.'"$.'.implode('.', $path).'"';
    }

    /**
     * Determine if the given string is a JSON selector.
     * @param  string  $value
     * @return bool
     */
    protected function isJsonSelector($value)
    {
        return Str::contains($value, '->');
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
